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
 * Authors: Manfred Kindl <manfred.kindl@technikum-wien.at>
 */
require_once('../../../config/cis.config.inc.php');
require_once('../../../include/dokument_export.class.php');
require_once('../../../include/lehrveranstaltung.class.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/phrasen.class.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/lehreinheitmitarbeiter.class.php');
require_once('../../../include/benutzer.class.php');
require_once('../include/lvevaluierung.class.php');
require_once('../include/lvevaluierung_code.class.php');
require_once('../include/lvevaluierung_antwort.class.php');
require_once('../include/lvevaluierung_frage.class.php');

$uid = get_uid();

$sprache = getSprache();
$p = new phrasen($sprache);

$db = new basis_db();

if (!isset($_GET['lvevaluierung_id']))
	die('lvevaluierung_id muss uebergeben werden');
if (!is_numeric($_GET['lvevaluierung_id']))
	die('Id ist ungueltig');

$output = 'pdf';

if (isset($_GET['output']) && ($output = 'odt' || $output = 'doc'))
	$output = $_GET['output'];

$lvevaluierung_id = $_GET['lvevaluierung_id'];

$lvevaluierung = new lvevaluierung();
if (!$lvevaluierung->load($lvevaluierung_id))
	die($lvevaluierung->errormsg);


// Berechtigungen pruefen
$lem = new lehreinheitmitarbeiter();
if (!$lem->existsLV($lvevaluierung->lehrveranstaltung_id, $lvevaluierung->studiensemester_kurzbz, $uid))
{
	$rechte = new benutzerberechtigung();
	$rechte->getBerechtigungen($uid);

	$lva = new lehrveranstaltung();
	$lva->load($lvevaluierung->lehrveranstaltung_id);
	$oes = $lva->getAllOe();
	$oes[] = $lva->oe_kurzbz; // Institut
	if (!$rechte->isBerechtigt('admin') && !$rechte->isBerechtigtMultipleOe('addon/lvevaluierung', $oes, 's'))
	{
		die($rechte->errormsg);
	}
}

$lv = new lehrveranstaltung();
$lv->load($lvevaluierung->lehrveranstaltung_id);

$teilnehmer = $lv->getStudentsOfLv($lvevaluierung->lehrveranstaltung_id, $lvevaluierung->studiensemester_kurzbz);
$anzahl_studierende = count($teilnehmer);
$lehrform = $lv->lehrform_kurzbz;

$codes = new lvevaluierung_code();
$codes->loadCodes($lvevaluierung_id);

$anzahl_codes_gesamt = 0;
$anzahl_codes_gestartet = 0;
$anzahl_codes_beendet = 0;
$durchschnittszeit = 0;

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
		$dauerinsekunden = (substr($dauer, 0, 2) * 60 * 60) + (substr($dauer, 3, 2) * 60) + (substr($dauer, 6, 2));
		$gesamtsekunden += $dauerinsekunden;
	}
}
if ($lvevaluierung->codes_ausgegeben != '')
	$anzahl_codes_gesamt = $lvevaluierung->codes_ausgegeben;

if ($anzahl_codes_gesamt > 0)
	$prozent_abgeschlossen = (100 / $anzahl_codes_gesamt * $anzahl_codes_beendet);
else
	$prozent_abgeschlossen = 0;

if ($sprache == 'English')
	$doc = new dokument_export('LvEvaluierungAuswertungEng');
else
	$doc = new dokument_export('LvEvaluierungAuswertung');

$leiter_uid = $lv->getLVLeitung($lvevaluierung->lehrveranstaltung_id, $lvevaluierung->studiensemester_kurzbz);
$benutzer = new benutzer();
$benutzer->load($leiter_uid);

$lvleitung = $benutzer->titelpre.' '.$benutzer->vorname.' '.$benutzer->nachname.' '.$benutzer->titelpost;

$stg = new studiengang();
$stg->load($lv->studiengang_kz);

$studiengang_bezeichnung = $stg->bezeichnung_arr[$sprache];
$studiensemester = $lvevaluierung->studiensemester_kurzbz;

if ($anzahl_codes_beendet > 0)
	$durchschnittszeit = (int)(($gesamtsekunden / $anzahl_codes_beendet) / 60).':'.(($gesamtsekunden / $anzahl_codes_beendet) % 60);
else
	$durchschnittszeit = 0;


$teilnehmer = $lv->getStudentsOfLv($lvevaluierung->lehrveranstaltung_id, $lvevaluierung->studiensemester_kurzbz);
$anzahl_studierende = count($teilnehmer);
$lehrform = $lv->lehrform_kurzbz;

$stg->getAllTypes();

$data = array(
	'bezeichnung' => $lv->bezeichnung,
	'bezeichnung_englisch' => $lv->bezeichnung_english,
	'lehrveranstaltung_id' => $lv->lehrveranstaltung_id,
	'lvleitung' => $lvleitung,
	'studiengang' => $studiengang_bezeichnung,
	'studiengang_englisch' => $studiengang_bezeichnung,
	'typ' => $stg->studiengang_typ_arr[$stg->typ],
	'ects' => $lv->ects,
	'sprache' => $lv->sprache,
	'studiensemester' => $lvevaluierung->studiensemester_kurzbz,
	'semester' => $lv->semester,
	'anzahl' => $anzahl_studierende,
	'orgform' => $lv->orgform_kurzbz,
	'lehrform' => $lehrform,
	'lvevaluierung_id' => $lvevaluierung->lvevaluierung_id,
	'codes_ausgegeben' => $lvevaluierung->codes_ausgegeben,
	'codes_beendet' => $anzahl_codes_beendet,
	'codes_gesamt' => $anzahl_codes_gesamt,
	'prozent_abgeschlossen' => $prozent_abgeschlossen,
	'durchschnittszeit' => $durchschnittszeit
);

$lvevaluierung_antwort = new lvevaluierung_antwort();
$lvevaluierung_antwort->loadAntworten($lvevaluierung_id);

$sprache = getSprache();
$db = new basis_db();

foreach ($lvevaluierung_antwort->result as $lvevaluierung_frage_id => $antworten)
{
	$lvevaluierung_frage = new lvevaluierung_frage();
	if (!$lvevaluierung_frage->load($lvevaluierung_frage_id))
		die($lvevaluierung_frage->errormsg);

	$antworten_arr = array();

	switch($lvevaluierung_frage->typ)
	{
		case 'label':
			$data[]['frage'] = array(	'frage_typ' => 'label',
										'frage_text' => $lvevaluierung_frage->bezeichnung[$sprache]);
			break;
		case 'labelsub':
			$data[]['frage'] = array(	'frage_typ' => 'labelsub',
										'frage_text' => $lvevaluierung_frage->bezeichnung[$sprache]);
			break;

		case 'text':
			foreach ($antworten as $antwort)
			{
				if ($antwort->antwort != '')
					$antworten_arr[]['antwort'] = array('text' => $antwort->antwort);
			}
			$data[]['frage'] = array(	'frage_typ' => 'text',
										'frage_text' => $lvevaluierung_frage->bezeichnung[$sprache],
										'anzahl_alle' => $anzahl_antworten_all,
										'durchschnitt' => $durchschnitt, $antworten_arr);
			break;

		case 'singleresponse':

			// Alle moeglichen Antworten zu dieser Frage holen
			$lv_frage = new lvevaluierung_frage();
			$lv_frage->loadAntworten($lvevaluierung_frage_id);
			$antworten_array = array();
			$frage_minwert = null;
			$frage_maxwert = null;
			$awm = '';
			foreach ($lv_frage->result as $awmoeglichkeit)
			{
				if (is_null($frage_minwert) || $frage_minwert > $awmoeglichkeit->wert)
				{
					$antwort_min = $awmoeglichkeit->bezeichnung[$sprache];
					$frage_minwert = $awmoeglichkeit->wert;
				}
				if (is_null($frage_maxwert) || $frage_maxwert < $awmoeglichkeit->wert)
				{
					$antwort_max = $awmoeglichkeit->bezeichnung[$sprache];
					$frage_maxwert = $awmoeglichkeit->wert;
				}

				if ($awmoeglichkeit->bezeichnung[$sprache] != '')
					$awm .= $awmoeglichkeit->wert.'='.$awmoeglichkeit->bezeichnung[$sprache].'; ';

				$antworten_array[$awmoeglichkeit->lvevaluierung_frage_antwort_id]['bezeichnung'] = $awmoeglichkeit->bezeichnung[$sprache];
				$antworten_array[$awmoeglichkeit->lvevaluierung_frage_antwort_id]['anzahl'] = 0;
				$antworten_array[$awmoeglichkeit->lvevaluierung_frage_antwort_id]['wert'] = $awmoeglichkeit->wert;
			}
			$antworten_array['keineauswahl']['anzahl'] = 0;
			$antworten_array['keineauswahl']['bezeichnung'] = $p->t('lvevaluierung/keineAuswahl');
			$antworten_array['keineauswahl']['wert'] = '';

			// Antworten durchlaufen die auf diese Frage gegeben wurden
			$wertsumme = 0;
			$minwert = null;
			$maxwert = null;
			$anzahl_antworten = 0;
			$anzahl_keineangabe = 0; // keine Angabe angeklickt
			$anzahl_keineauswahl = 0; // nichts angeklickt
			$durchschnitt = 0;
			foreach ($antworten as $antwort)
			{
				if (is_null($antwort->lvevaluierung_frage_antwort_id))
				{
					$antworten_array['keineauswahl']['anzahl']++;
					$anzahl_keineauswahl++;
				}
				else
				{
					$antworten_array[$antwort->lvevaluierung_frage_antwort_id]['anzahl']++;
					if ($antwort->wert != 0)
					{
						$anzahl_antworten++;
						$wertsumme += $antwort->wert;
					}
					else
						$anzahl_keineangabe++;
				}
			}

			if ($anzahl_antworten != 0)
				$durchschnitt = $wertsumme / $anzahl_antworten;

			$anzahl_antworten_all = $anzahl_antworten + $anzahl_keineangabe + $anzahl_keineauswahl;

			foreach ($antworten_array as $id => $antworten_row)
			{
				$antworten_arr[]['antwort'] = array('bezeichnung' => $antworten_row['bezeichnung'],
													'wert' => $antworten_row['wert'],
													'anzahl' => $antworten_row['anzahl']);
			}
			$data[]['frage'] = array(	'frage_typ' => 'singleresponse',
										'frage_text' => $lvevaluierung_frage->bezeichnung[$sprache],
										'anzahl_alle' => $anzahl_antworten_all,
										'durchschnitt' => $durchschnitt, $antworten_arr);
			break;

		default:
			$antworten_arr[] = array('frage_typ' => $lvevaluierung_frage->typ);
			break;
	}
}

$doc->addDataArray($data, 'auswertungen');
//echo $doc->getXML();exit;
if (!$doc->create($output))
	die($doc->errormsg);
$doc->output();
$doc->close();
