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
 */
require_once('../../../config/cis.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/phrasen.class.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/lehreinheitmitarbeiter.class.php');
require_once('../../../include/lehrveranstaltung.class.php');
require_once('../../../include/studiengang.class.php');
require_once('../../../include/benutzer.class.php');
require_once('../../../include/datum.class.php');
require_once('../include/lvevaluierung.class.php');
require_once('../include/lvevaluierung_code.class.php');

$uid = get_uid();

$sprache = getSprache();
$p = new phrasen($sprache);

$db = new basis_db();
$datum_obj = new datum();

echo '<!DOCTYPE html>
<html>
	<head>
		<title>'.$p->t('lvevaluierung/lvevaluierung').'</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<link href="../../../skin/fhcomplete.css" rel="stylesheet" type="text/css">
		<link href="../../../skin/style.css.php" rel="stylesheet" type="text/css">
		<link href="../skin/lvevaluierung.css" rel="stylesheet" type="text/css">
		<link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css">
		<link href="../../../skin/jquery.ui.timepicker.css" rel="stylesheet" type="text/css"/>
        <link href="../../../skin/jquery-ui-1.9.2.custom.min.css" rel="stylesheet"  type="text/css">
		<script type="text/javascript" src="../../../include/js/jquery1.9.min.js"></script>
        <script src="../../../include/js/jquery.ui.timepicker.js" type="text/javascript" ></script>

		<script type="text/javascript">
		$(document).ready(function()
		{
			//$(\'#formular\').hide();

		    $( ".datepicker_datum" ).datepicker({
					 changeMonth: true,
					 changeYear: true,
					 dateFormat: "dd.mm.yy",
					 });

			$( ".timepicker" ).timepicker({
					showPeriodLabels: false,
					hourText: "Stunde",
					minuteText: "Minute",
					hours: {starts: 7,ends: 22},
					rows: 4,
					});

		});

		</script>
	</head>
<body>
';

if(isset($_REQUEST['lehrveranstaltung_id']))
	$lehrveranstaltung_id = $_REQUEST['lehrveranstaltung_id'];
else
	die('lv ungültig');

if(isset($_REQUEST['studiensemester_kurzbz']))
	$studiensemester_kurzbz = $_REQUEST['studiensemester_kurzbz'];
else
	die('stsem ungültig');

// Berechtigungen pruefen
$lem = new lehreinheitmitarbeiter();
if(!$lem->existsLV($lehrveranstaltung_id, $studiensemester_kurzbz,  $uid))
{
	$rechte = new benutzerberechtigung();
	$rechte->getBerechtigungen($uid);

	if(!$rechte->isBerechtigt('admin'))
	{
		die($p->t('global/keineBerechtigung'));
	}
}

if(isset($_POST['saveEvaluierung']))
{
	$lvevaluierung_id = $_POST['lvevaluierung_id'];
	$von_datum = $_POST['von_datum'];
	$bis_datum = $_POST['bis_datum'];
	$von_uhrzeit = $_POST['von_uhrzeit'];
	$bis_uhrzeit = $_POST['bis_uhrzeit'];
	$startzeit = $datum_obj->formatDatum($von_datum,'Y-m-d').' '.$von_uhrzeit;
	$endezeit = $datum_obj->formatDatum($bis_datum,'Y-m-d').' '.$bis_uhrzeit;
	$dauer = $_POST['dauer'];

	$evaluierung = new lvevaluierung();

	if($lvevaluierung_id!='')
	{
		if(!$evaluierung->load($lvevaluierung_id))
			die($p->t('global/fehlerBeimLadenDesDatensatzes'));
	}

	$evaluierung->startzeit = $startzeit;
	$evaluierung->endezeit = $endezeit;
	$evaluierung->dauer = $dauer;
	$evaluierung->lehrveranstaltung_id = $lehrveranstaltung_id;
	$evaluierung->studiensemester_kurzbz = $studiensemester_kurzbz;

	if($evaluierung->save())
	{
		// Zugangscodes generieren
		$codes = new lvevaluierung_code();

		if(!$codes->generateCodes($evaluierung->lvevaluierung_id))
			echo '<span class="error">Failed: '.$codes->errormsg.'</span>';
		else
			echo '<span class="ok">'.$p->t('global/datenWurdenGespeichert').'</span>';
	}
	else
		echo '<span class="error">'.$p->t('global/fehlerBeimSpeichernDerDaten').'</span>';
}

// Details Anzeigen
$lv = new lehrveranstaltung();
$lv->load($lehrveranstaltung_id);

echo '<h1>'.$p->t('lvevaluierung/lvevaluierung').' - '.$db->convert_html_chars($lv->bezeichnung.' ('.$lv->lehrveranstaltung_id.')').'</h1>';

$leiter_uid = $lv->getLVLeitung($lehrveranstaltung_id, $studiensemester_kurzbz);
$benutzer = new benutzer();
$benutzer->load($leiter_uid);

$lvleitung=$benutzer->titelpre.' '.$benutzer->vorname.' '.$benutzer->nachname.' '.$benutzer->titelpost;

$stg = new studiengang();
$stg->load($lv->studiengang_kz);

$studiengang_bezeichnung=$stg->bezeichnung;
$studiensemester = $studiensemester_kurzbz;

$teilnehmer = $lv->getStudentsOfLv($lehrveranstaltung_id, $studiensemester_kurzbz);
$anzahl_studierende=count($teilnehmer);
$lehrform = $lv->lehrform_kurzbz;

$evaluierung = new lvevaluierung();
if(!$evaluierung->getEvaluierung($lehrveranstaltung_id, $studiensemester_kurzbz))
{
	echo $p->t('lvevaluierung/keineEvaluierungAngelegt');
	$evaluierung->lehrveranstaltung_id=$lehrveranstaltung_id;
	$evaluierung->studiensemester_kurzbz=$studiensemester_kurzbz;
	$neu=true;
}
else
{
	$neu = false;
	echo $p->t('lvevaluierung/zeitinfo',array($datum_obj->formatDatum($evaluierung->startzeit,'d.m.Y H:i'),$datum_obj->formatDatum($evaluierung->endezeit,'d.m.Y H:i')));
}

echo '
<table width="100%">
<tr>
	<td valign="top">
		<div id="formular">
		<form action="administration.php" method="POST">
		<input type="hidden" name="lvevaluierung_id" value="'.$db->convert_html_chars($evaluierung->lvevaluierung_id).'" />
		<input type="hidden" name="lehrveranstaltung_id" value="'.$db->convert_html_chars($evaluierung->lehrveranstaltung_id).'" />
		<input type="hidden" name="studiensemester_kurzbz" value="'.$db->convert_html_chars($evaluierung->studiensemester_kurzbz).'" />
		<table>
			<tr>
				<td>'.$p->t('lvevaluierung/startzeit').'</td>
				<td>
				<input type="text" class="datepicker_datum" id="von_datum" name="von_datum" value="'.$db->convert_html_chars($datum_obj->formatDatum($evaluierung->startzeit,'d.m.Y')).'" size="9">
				<input onchange="checkZeiten()" type="text" class="timepicker" id="von_uhrzeit" name="von_uhrzeit" value="'.$db->convert_html_chars($datum_obj->formatDatum($evaluierung->startzeit,'H:i')).'" size="4">
				</td>
			</tr>
			<tr>
				<td>'.$p->t('lvevaluierung/endezeit').'</td>
				<td>
				<input type="text" class="datepicker_datum" id="bis_datum" name="bis_datum" value="'.$db->convert_html_chars($datum_obj->formatDatum($evaluierung->endezeit,'d.m.Y')).'" size="9">
				<input onchange="checkZeiten()" type="text" class="timepicker" id="bis_uhrzeit" name="bis_uhrzeit" value="'.$db->convert_html_chars($datum_obj->formatDatum($evaluierung->endezeit,'H:i')).'" size="4">
				</td>
			</tr>
			<tr>
				<td>'.$p->t('lvevaluierung/dauer').'</td>
				<td><input type="text" name="dauer" value="'.$db->convert_html_chars($evaluierung->dauer).'" size="8" /> HH:MM:SS</td>
			</tr>
			<tr>
				<td></td>
				<td><input type="submit" name="saveEvaluierung" value="'.$p->t('global/speichern').'" /></td>
			</tr>
		</table>
		</form>
		</div>
	</td>
	<td valign="top">
		<table class="tablesorter">
		<tbody>
		<tr>
			<td>'.$p->t('lvevaluierung/lvleitung').'</td>
			<td>'.$db->convert_html_chars($lvleitung).'</td>
		</tr>
		<tr>
			<td>'.$p->t('global/studiengang').'</td>
			<td>'.$db->convert_html_chars($studiengang_bezeichnung).'</td>
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
			<td>'.$p->t('lvevaluierung/ausbildungssemester').'</td>
			<td>'.$db->convert_html_chars($lv->semester).'</td>
		</tr>
		<tr>
			<td>'.$p->t('lvevaluierung/anzahlstudierende').'</td>
			<td>'.$db->convert_html_chars($anzahl_studierende).'</td>
		</tr>
		</tbody>
		</table>
	</td>
</tr>
</table>';

if($evaluierung->lvevaluierung_id!='')
{
	echo '<a href="qrcode.php?lvevaluierung_id='.$evaluierung->lvevaluierung_id.'">'.$p->t('lvevaluierung/CodeListeErstellen').'</a>';
	echo '<br><br><a href="auswertung.php?lvevaluierung_id='.$evaluierung->lvevaluierung_id.'">'.$p->t('lvevaluierung/Auswertung').'</a>';
}
echo '</body></html>';
?>
