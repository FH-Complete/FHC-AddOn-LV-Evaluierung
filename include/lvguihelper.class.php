<?php
/* Copyright (C) 2015 fhcomplete.org
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, USA.
 *
 * Authors: Harald Bamberger<ma0080@technikum-wien.at>
 			Manuela Thamer<manuela.thamer@technikum-wien.at>
 */
require_once('../../../config/cis.config.inc.php');
require_once('../../../config/global.config.inc.php');
require_once('../../../include/phrasen.class.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/datum.class.php');
require_once('../../../include/lehrveranstaltung.class.php');
require_once('../../../include/benutzer.class.php');
require_once('../../../include/lehreinheitmitarbeiter.class.php');
require_once('../include/lvevaluierung.class.php');
require_once('../../../include/lehrmodus.class.php');

class LvGuiHelper
{
/**
 * baut das Array für die Übersichtstabelle Evaluierung
 * @param obj $lv Lehrveranstaltungsobjekt.
 * @param object $stg Studiengangsobjekt.
 * @param string $p Phrasenobjekt.
 * @param object $db Datenbankobjekt.
 * @param object $lvevaluierung Evaluierungsobjekt.
 * @param string $sprache Sprache.
 * @param string $cssclass Gewünschte Tabellenformatierung.
 * @return table Tabelle im Ausgabeformat
 */
	public static function formatAsEvalTable($lv, $stg, $p, $db, $lvevaluierung, $sprache, $cssclass)
	{
		$tableArray = self::buildBaseArray($lv, $stg, $p, $db, $lvevaluierung, $sprache);
		$table_lv = self::formatAsTable($tableArray, $cssclass);
		return $table_lv;
	}

/**
 * baut das Array für die Übersichtstabelle Auswertung der LV-Evaluierung
 * @param obj $lv Lehrveranstaltungsobjekt.
 * @param object $stg Studiengangsobjekt.
 * @param string $p Phrasenobjekt.
 * @param object $db Datenbankobjekt.
 * @param object $lvevaluierung Evaluierungsobjekt.
 * @param string $sprache Sprache.
 * @param string $cssclass Gewünschte Tabellenformatierung.
 * @return table Tabelle im Ausgabeformat
 */
	public static function formatAsAuswertungTable($lv, $stg, $p, $db, $lvevaluierung, $sprache, $cssclass)
	{
		$tableArray = self::buildBaseArray($lv, $stg, $p, $db, $lvevaluierung, $sprache);

		$codes = new lvevaluierung_code();
		$codes->loadCodes($lvevaluierung->lvevaluierung_id);

		$teilnehmer = $lv->getStudentsOfLv($lv->lehrveranstaltung_id, $lvevaluierung->studiensemester_kurzbz);
		$anzahl_studierende = count($teilnehmer);

		$anzahl_codes_gesamt = 0;
		$anzahl_codes_gestartet = 0;
		$anzahl_codes_beendet = 0;
		$anzahl_codes_ausgegeben = $lvevaluierung->codes_gemailt ? $anzahl_studierende :
			$lvevaluierung->codes_ausgegeben;

		$gesamtsekunden = 0;
		foreach ($codes->result as $code)
		{
			if ($code->startzeit != '')
				$anzahl_codes_gestartet++;
			if ($code->endezeit != '')
				$anzahl_codes_beendet++;
			$anzahl_codes_gesamt++;
			if ($code->endezeit != '')
			{
				$dtende = new DateTime($code->endezeit);
				$dtstart = new DateTime($code->startzeit);
				$dauer = $dtende->diff($dtstart)->format('%H:%I:%S');
				$dauerinsekunden = (substr($dauer, 0, 2) * 60 * 60) + (substr($dauer, 3, 2) * 60) +
				(substr($dauer, 6, 2));
				$gesamtsekunden += $dauerinsekunden;
			}
		}
		if ($lvevaluierung->codes_ausgegeben != '')
			$anzahl_codes_gesamt = $lvevaluierung->codes_ausgegeben;

		if ($anzahl_codes_gesamt > 0)
			$prozent_abgeschlossen = (100 / $anzahl_codes_gesamt * $anzahl_codes_beendet);
		else
			$prozent_abgeschlossen = 0;

		$tableArray[$p->t('lvevaluierung/anzahlstudierende')] = $db->convert_html_chars($anzahl_studierende).
		' ( '.$p->t('lvevaluierung/anzahlausgegeben').' '. $anzahl_codes_ausgegeben. ' )';
		$tableArray[$p->t('lvevaluierung/abgeschlossen')] = $anzahl_codes_beendet.
		' / '.$anzahl_codes_gesamt.' ( '.number_format($prozent_abgeschlossen, 2).'% )';
		$tableArray[$p->t('lvevaluierung/durchschnittszeit')] =
		(($anzahl_codes_beendet > 0)?((int)(($gesamtsekunden / $anzahl_codes_beendet) / 60).
		':'.(($gesamtsekunden / $anzahl_codes_beendet) % 60)):'');

		$table_lv = self::formatAsTable($tableArray, $cssclass);
		return $table_lv;
	}

/**
 * baut die Basistabelle für die Übersichtstabellen LV-Evaluierung
 * @param obj $lv Lehrveranstaltungsobjekt.
 * @param object $stg Studiengangsobjekt.
 * @param string $p Phrasenobjekt.
 * @param object $db Datenbankobjekt.
 * @param object $lvevaluierung Evaluierungsobjekt.
 * @param string $sprache Sprache.
 * @return table Tabelle im Ausgabeformat
 */
	protected static function buildBaseArray($lv, $stg, $p, $db, $lvevaluierung, $sprache)
	{
		$leiter_uid = $lv->getLVLeitung($lv->lehrveranstaltung_id, $lvevaluierung->studiensemester_kurzbz);
		$benutzer = new benutzer();
		$benutzer->load($leiter_uid);

		$lvleitung = $benutzer->titelpre.' '.$benutzer->vorname.' '.$benutzer->nachname.' '.$benutzer->titelpost;

		$lem = new lehreinheitmitarbeiter();
		$lem->getMitarbeiterLV($lv->lehrveranstaltung_id, $lvevaluierung->studiensemester_kurzbz);
		$anzahl_lem = count($lem->result);

		$lektoren = '';
		foreach($lem->result as $row_lektoren)
			$lektoren .= $row_lektoren->titelpre.' '.$row_lektoren->vorname.' '.$row_lektoren->nachname.
			' '.$row_lektoren->titelpost.', ';
		$lektoren = mb_substr($lektoren, 0, -2);

		$studiengang_bezeichnung = $stg->bezeichnung;
		$studiensemester = $lvevaluierung->studiensemester_kurzbz;

		$teilnehmer = $lv->getStudentsOfLv($lv->lehrveranstaltung_id, $lvevaluierung->studiensemester_kurzbz);
		$anzahl_studierende = count($teilnehmer);
		$lehrform = $lv->lehrform_kurzbz;

		$lehrmodus = $lv->lehrmodus_kurzbz;
		$lm_beschr = new lehrmodus();
		$lm_beschr ->load($lehrmodus);
		$lm_beschr  = $lm_beschr->bezeichnung_mehrsprachig[$sprache];

		$lemtyp = ($anzahl_lem == 1)
				? $p->t('lvevaluierung/lvleitung')
				: $p->t('global/lektorInnen');

		$tableArray = array(
			$p->t('lvevaluierung/lvbezeichnung') => $db->convert_html_chars($sprache == 'English'
			?$lv->bezeichnung_english:$lv->bezeichnung.' ('.$lv->lehrveranstaltung_id.')'),
			$lemtyp => $db->convert_html_chars($lektoren),
			$p->t('global/studiengang') => $db->convert_html_chars($stg->studiengang_typ_arr[$stg->typ]).
			' '.$db->convert_html_chars($studiengang_bezeichnung),
			$p->t('lvevaluierung/organisationsform') => $db->convert_html_chars($lv->orgform_kurzbz),
			$p->t('lvevaluierung/lvtyp') => $db->convert_html_chars($lehrform),
			$p->t('lvevaluierung/lvmodus') => $db->convert_html_chars($lm_beschr),
			$p->t('lvevaluierung/ects') => $db->convert_html_chars($lv->ects),
			$p->t('global/sprache') => $db->convert_html_chars($lv->sprache),
			$p->t('global/studiensemester') => $db->convert_html_chars($studiensemester),
			$p->t('lvevaluierung/ausbildungssemester') => $db->convert_html_chars($lv->semester),
			$p->t('lvevaluierung/anzahlstudierende') => $db->convert_html_chars($anzahl_studierende)
		);
		return array_filter($tableArray);
	}

/**
 * Baut das Array zu einem html-Table-String zusammen und gibt diesen formatiert aus
 * @param array $tableArray Auszugebendes Array.
 * @param string $cssclass Gewünschte Tabellenformatierung.
 * @return Tabelle im Ausgabeformat
 */
	protected static function formatAsTable($tableArray, $cssclass)
	{
		$table = '';
		//$table .= '<pre>'.print_r($tableArray, true).'</pre>';
		$table .= '
			<table class="'.$cssclass.'">
			';
		foreach ($tableArray as $key => $value)
		{
			$table .= '
			<tr>
				<td>'.$key.'</td>
				<td>'.$value.'</td>
			</tr>
			';
		}
		$table .= '
			</table>
		';

		return $table;
	}
}
