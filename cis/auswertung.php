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
 * Authors: Andreas Österreicher <andreas.oesterreicher@technikum-wien.at>
 * Authors: Cristina Hainberger <cristina.hainberg@technikum-wien.at>
 */
require_once('../../../config/cis.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/phrasen.class.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/lehreinheitmitarbeiter.class.php');
require_once('../../../include/lehrveranstaltung.class.php');
require_once('../../../include/studiengang.class.php');
require_once('../../../include/benutzer.class.php');
require_once('../include/lvevaluierung.class.php');
require_once('../include/lvevaluierung_code.class.php');
require_once('../include/lvevaluierung_antwort.class.php');
require_once('../include/lvevaluierung_frage.class.php');

$uid = get_uid();
$sprache = getSprache();
$p = new phrasen($sprache);

$db = new basis_db();

echo '<!DOCTYPE html>
<html>
	<head>
		<title>'.$p->t('lvevaluierung/auswertung').'</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<link href="../../../skin/fhcomplete.css" rel="stylesheet" type="text/css">
		<link href="../../../skin/style.css.php" rel="stylesheet" type="text/css">
		<link href="../skin/lvevaluierung.css" rel="stylesheet" type="text/css">
		<link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css">
		<script type="text/javascript" src="../../../vendor/jquery/jqueryV1/jquery-1.12.4.min.js"></script>
		<script type="text/javascript" src="../../../vendor/christianbach/tablesorter/jquery.tablesorter.min.js"></script>
	</head>
<body>
';

if (isset($_REQUEST['lvevaluierung_id']) && is_numeric($_REQUEST['lvevaluierung_id']))
	$lvevaluierung_id = $_REQUEST['lvevaluierung_id'];
else
	die('lvevaluierung_id ungültig');

if (isset($_REQUEST['code_id']) && is_numeric($_REQUEST['code_id']))
	$code_id = $_REQUEST['code_id'];
else
	$code_id = '';

$lvevaluierung = new lvevaluierung();
if (!$lvevaluierung->load($lvevaluierung_id))
	die($lvevaluierung->errormsg);

$lehrveranstaltung_id = $lvevaluierung->lehrveranstaltung_id;
$studiensemester_kurzbz = $lvevaluierung->studiensemester_kurzbz;

if ($code_id != '')
	echo '<h1>'.$p->t('lvevaluierung/einzelAuswertung').'</h1>';
else
	echo '<h1>'.$p->t('lvevaluierung/auswertung').'</h1>';

// Details Anzeigen
$lv = new lehrveranstaltung();
$lv->load($lehrveranstaltung_id);

$leiter_uid = $lv->getLVLeitung($lehrveranstaltung_id, $studiensemester_kurzbz);
$benutzer = new benutzer();
$benutzer->load($leiter_uid);

$lvleitung = $benutzer->titelpre.' '.$benutzer->vorname.' '.$benutzer->nachname.' '.$benutzer->titelpost;

$stg = new studiengang();
$stg->getAllTypes();
$stg->load($lv->studiengang_kz);

// Berechtigungen pruefen
$lem = new lehreinheitmitarbeiter();
$lem->getMitarbeiterLV($lehrveranstaltung_id, $studiensemester_kurzbz);
$isLektor_lv_aufgeteilt = false;
$isStgl = false;
$isInstitutsleiter = false;

if (!$lem->existsLV($lvevaluierung->lehrveranstaltung_id, $lvevaluierung->studiensemester_kurzbz, $uid))
{
	$rechte = new benutzerberechtigung();
	$rechte->getBerechtigungen($uid);

	$lva = new lehrveranstaltung();
	$lva->load($lvevaluierung->lehrveranstaltung_id);
	$oes = $lva->getAllOe(); 
	$oes[] = $lva->oe_kurzbz; // Institut
	$oes[] = $stg->oe_kurzbz; // OE des Studiengangs der Lehrveranstaltung 
	if (!$rechte->isBerechtigtMultipleOe('addon/lvevaluierung', $oes, 's'))
	{
		die($p->t('global/keineBerechtigungFuerDieseSeite'));
	}
    else 
    {
        $isStgl = true;
        $isInstitutsleiter = true;
    }
} 
//User ist der LV + dem Studiensemester zugeordent
else
{
    //wenn Evaulierung für verschiedene Lektoren gewählt worden ist, check ob aktuelle uid einer von diesen ist
   if($lvevaluierung->lv_aufgeteilt)
   {       
       foreach($lem->result as $lektor)
       {
           if($uid == $lektor->uid)
               $isLektor_lv_aufgeteilt = true;
       }
   }
}
    
$studiengang_bezeichnung = $stg->bezeichnung_arr[$sprache];
$studiensemester = $studiensemester_kurzbz;

$teilnehmer = $lv->getStudentsOfLv($lehrveranstaltung_id, $studiensemester_kurzbz);
$anzahl_studierende = count($teilnehmer);
$lehrform = $lv->lehrform_kurzbz;

$codes = new lvevaluierung_code();
$codes->loadCodes($lvevaluierung_id);

$anzahl_codes_gesamt = 0;
$anzahl_codes_gestartet = 0;
$anzahl_codes_beendet = 0;

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

$auswertung = (isset($_POST['auswertung']) ? $_POST['auswertung'] : '');

//dropdown nur für lektoren, die persönliche Auswertung erhalten haben
//wahl: gesamtauswertung + persönliche auswertung
if($isLektor_lv_aufgeteilt)
{
    echo '<form method="POST" action="">';
    echo '<span>Bitte wählen Sie Ihre gewünschte Auswertung: </span>';          
    echo '          
    <select name="auswertung"">
        <option value="gesamt"' . (($auswertung == 'gesamt') ? "selected" : "") . '>Gesamtauswertung</option>
        <option value="persoenlich"' . (($auswertung == 'persoenlich') ? "selected" : "") . '>Persönliche Auswertung</option>         
    </select>
    <input type="submit" value="'.$p->t('global/auswaehlen').'" />
    </form></p>';           
}

//dropdown nur für studiengangs- und institutsleiter 
//wahl: gesamtauswertung + individuelle auswertungen der jeweiligen lektoren dieser lv
if($isStgl || $isInstitutsleiter)
{
    echo '<form method="POST" action="">';
    echo '<span>Bitte wählen Sie Ihre gewünschte Auswertung: </span>';          
    echo '          
    <select name="auswertung">
        <option value="gesamt"' . (($auswertung == 'gesamt') ? "selected" : "") . '>Gesamtauswertung</option>';
        foreach($lem->result as $row) 
        {  
            echo '<option value="' . $row->uid . '"' . (($auswertung == $row->uid) ? "selected" : "") . '>' . $row->titelpre . ' ' . $row->titelpost . ' ' . $row->vorname . ' ' . $row->nachname . '</option>';
        }
    echo '
    </select>
    <input type="submit" value="'.$p->t('global/auswaehlen').'" />
    </form></p>';           
}

if ($code_id != '')
	echo '<a href="auswertung.php?lvevaluierung_id='.$lvevaluierung_id.'">'.$p->t('lvevaluierung/alleAnzeigen').'</a>';
elseif ($auswertung != 'gesamt')
{
    $lektor_uid = ($auswertung == 'persoenlich') ? $uid : $auswertung;
    echo '<a href="auswertung_export.php?lvevaluierung_id='.$lvevaluierung_id.'&lektor_uid=' . $lektor_uid . '">'.$p->t('lvevaluierung/pdfExport').'</a>';
}
else
    echo '<a href="auswertung_export.php?lvevaluierung_id='.$lvevaluierung_id.'">'.$p->t('lvevaluierung/pdfExport').'</a>';

echo '
	<table class="tablesorter">
	<thead>
	</thead>
	<tbody>
	<tr>
		<td>'.$p->t('lvevaluierung/lvbezeichnung').'</td>
		<td>'.$db->convert_html_chars($sprache == 'English'?$lv->bezeichnung_english:$lv->bezeichnung.' ('.$lv->lehrveranstaltung_id.')').'</td>
	</tr>
	<tr>
		<td>'.$p->t('lvevaluierung/lvleitung').'</td>
		<td>'.$db->convert_html_chars($lvleitung).'</td>
	</tr>
	<tr>
		<td>'.$p->t('global/studiengang').'</td>
		<td>'.$db->convert_html_chars($stg->studiengang_typ_arr[$stg->typ]).' '.$db->convert_html_chars($studiengang_bezeichnung).'</td>
	</tr>
	<tr>
		<td>'.$p->t('lvevaluierung/ausbildungssemester').'</td>
		<td>'.$db->convert_html_chars($lv->semester).'</td>
	</tr>
	<tr>
		<td>'.$p->t('lvevaluierung/organisationsform').'</td>
		<td>'.$db->convert_html_chars($lv->orgform_kurzbz).'</td>
	</tr>
	<tr>
		<td>'.$p->t('lvevaluierung/lvtyp').'</td>
		<td>'.$db->convert_html_chars($lehrform).'</td>
	</tr>
	<tr>
		<td>'.$p->t('lvevaluierung/ects').'</td>
		<td>'.$db->convert_html_chars($lv->ects).'</td>
	</tr>
	<tr>
		<td>'.$p->t('global/sprache').'</td>
		<td>'.$db->convert_html_chars($lv->sprache).'</td>
	</tr>
	<tr>
		<td>'.$p->t('global/studiensemester').'</td>
		<td>'.$db->convert_html_chars($studiensemester).'</td>
	</tr>
	<tr>
		<td>'.$p->t('lvevaluierung/anzahlstudierende').'</td>
		<td>'.$db->convert_html_chars($anzahl_studierende).' ( '.$p->t('lvevaluierung/anzahlausgegeben').' '.$lvevaluierung->codes_ausgegeben.' )</td>
	</tr>
	<tr>
		<td>'.$p->t('lvevaluierung/abgeschlossen').'</td>
		<td>'.$anzahl_codes_beendet.' / '.$anzahl_codes_gesamt.' ( '.number_format($prozent_abgeschlossen, 2).'% ) </td>
	</tr>
	<tr>
		<td>'.$p->t('lvevaluierung/durchschnittszeit').'</td>
		<td>'.(($anzahl_codes_beendet > 0)?((int)(($gesamtsekunden / $anzahl_codes_beendet) / 60).':
			'.(($gesamtsekunden / $anzahl_codes_beendet) % 60)):'').'</td>
	</tr>
	</tbody>
	</table>
';

// Antworten zu dieser Evaluierung laden
$lvevaluierung_antwort = new lvevaluierung_antwort();
if ($auswertung == 'persoenlich')
    $lvevaluierung_antwort->loadAntworten($lvevaluierung_id, $code_id, $uid);
elseif (($isStgl || $isInstitutsleiter) && $auswertung != 'gesamt')
    $lvevaluierung_antwort->loadAntworten($lvevaluierung_id, $code_id, $auswertung);
else
    $lvevaluierung_antwort->loadAntworten($lvevaluierung_id, $code_id);

$sprache = getSprache();
$db = new basis_db();

foreach ($lvevaluierung_antwort->result as $lvevaluierung_frage_id => $antworten)
{
	$lvevaluierung_frage = new lvevaluierung_frage();
	if (!$lvevaluierung_frage->load($lvevaluierung_frage_id))
		echo 'Fehler beim Laden der Frage:'.$lvevaluierung_frage->errormsg;

	if ($lvevaluierung_frage->typ == 'label')
		echo '<h1>'.$db->convert_html_chars($lvevaluierung_frage->bezeichnung[$sprache]).'</h1>';
	else if ($lvevaluierung_frage->typ == 'labelsub')
		echo '<p>'.$db->convert_html_chars($lvevaluierung_frage->bezeichnung[$sprache]).'</p>';
	else
		echo '<h2>'.$db->convert_html_chars($lvevaluierung_frage->bezeichnung[$sprache]).'</h2>';
	if ($lvevaluierung_frage->typ == 'text')
	{
		if ($code_id != '')
			echo '<p>'.$p->t('lvevaluierung/anklickenFuerGesamtauswertung').'</p>';
		else
			echo '<p>'.$p->t('lvevaluierung/anklickenFuerEinzelauswertung').'</p>';
	}

	switch($lvevaluierung_frage->typ)
	{
		case 'label':
		case 'labelsub':
			break;
		case 'text':
			foreach ($antworten as $antwort)
			{
				if ($antwort->antwort != '')
				{
					if ($code_id != '')
					{
						echo '<a class="textantwort" title="'.$p->t('lvevaluierung/anklickenFuerGesamtauswertung').'"
								href="auswertung.php?lvevaluierung_id='.$lvevaluierung_id.'"><div class="textantwort">
								'.$db->convert_html_chars($antwort->antwort).'</div></a>';
					}
					else
					{
						echo '<a class="textantwort" title="'.$p->t('lvevaluierung/anklickenFuerEinzelauswertung').'"
								href="auswertung.php?lvevaluierung_id='.$lvevaluierung_id.'&code_id='.$antwort->lvevaluierung_code_id.'">
								<div class="textantwort"> '.$db->convert_html_chars($antwort->antwort).'</div></a>';
					}
				}
			}
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
					$antwort_min = $db->convert_html_chars($awmoeglichkeit->bezeichnung[$sprache]);
					$frage_minwert = $awmoeglichkeit->wert;
				}
				if (is_null($frage_maxwert) || $frage_maxwert < $awmoeglichkeit->wert)
				{
					$antwort_max = $db->convert_html_chars($awmoeglichkeit->bezeichnung[$sprache]);
					$frage_maxwert = $awmoeglichkeit->wert;
				}

				if ($awmoeglichkeit->bezeichnung[$sprache] != '')
					$awm .= $awmoeglichkeit->wert.'='.$db->convert_html_chars($awmoeglichkeit->bezeichnung[$sprache]).'; ';

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
			echo '<table>';
			foreach ($antworten_array as $id => $antworten_row)
			{
				echo '<tr>
					<td>'.$antworten_row['bezeichnung'].'</td>
					<td>'.($antworten_row['wert'] != 0?$antworten_row['wert']:'').'</td>
					<td>';
				$anz = $antworten_row['anzahl'];

				$maximalwert = 400;
				$balken = 400 / $anzahl_antworten_all * $anz;
				$class = 'barchart';
				if ($antworten_row['wert'] == '' || $antworten_row['wert'] == 0)
					$class = 'barchart_nichtausgefuellt';
				echo '<div class="barchart_border" style="width:'.$maximalwert.'px;">';
				echo '<div class="'.$class.'" style="width:'.($balken).'px;">
						'.($antworten_row['anzahl'] != 0?$antworten_row['anzahl']:'').'&nbsp;</div></div>';
				echo '</tr>';
			}
			echo '</table>';
			echo '<table class="tablesorter" style="width:auto">
					<tr>
						<td>'.$p->t('lvevaluierung/durchschnittsbewertung').'</td>
						<td>'.number_format($durchschnitt, 2).'</td>
					</tr>
					</table>';
			break;

		default:
			echo '<b>Typ??:'.$lvevaluierung_frage->typ;
			break;
	}
}

echo '</body></html>';
