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
require_once('../../../include/mail.class.php');
require_once('../../../include/benutzerfunktion.class.php');
require_once('../include/lvevaluierung.class.php');
require_once('../include/lvevaluierung_code.class.php');
require_once('../include/lvevaluierung_selbstevaluierung.class.php');

$uid = get_uid();

$sprache = getSprache();
$p = new phrasen($sprache);

$db = new basis_db();
$datum_obj = new datum();
$evaluierung_zeitraum_msg='';
$evaluierung_ausgegeben_msg='';
$evaluierung_selbsteval_msg='';
$jsjumpinfo='';

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
		function setBisdatum(datum)
		{
			if(document.getElementById("bis_datum").value=="")
				document.getElementById("bis_datum").value=datum;
		}
		function showSpinner(anz)
		{
			document.getElementById("spinner").style.display="inline";
			window.setTimeout("ausblenden()", 180*anz);
		}
		function ausblenden()
		{
			$("#spinner").hide();
		}
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

	$lva = new lehrveranstaltung();
	$lva->load($lehrveranstaltung_id);
	$oes = $lva->getAllOe();
	$oes[]=$lva->oe_kurzbz; // Institut
	if(!$rechte->isBerechtigtMultipleOe('addon/lvevaluierung',$oes,'s'))
	{
		die($p->t('global/keineBerechtigungFuerDieseSeite'));
	}
}

// Speichern der Evaluierungsdaten
if(isset($_POST['saveEvaluierung']))
{
	$lvevaluierung_id = $_POST['lvevaluierung_id'];
	$von_datum = $_POST['von_datum'];
	$bis_datum = $_POST['bis_datum'];

	//Datum auf Gueltigkeit pruefen
	if (($von_datum=='' || $bis_datum=='') || !$datum_obj->formatDatum($von_datum,'Y-m-d') || !$datum_obj->formatDatum($bis_datum,'Y-m-d'))
	{
			$evaluierung_zeitraum_msg= '<span class="error">'.$p->t('lvevaluierung/bitteGueltigesDatumEingeben').'</span>';
	}
	else
	{
		$von_uhrzeit = $_POST['von_uhrzeit'];
		$bis_uhrzeit = $_POST['bis_uhrzeit'];
		$startzeit = ($von_datum!=''?$datum_obj->formatDatum($von_datum,'Y-m-d').' '.$von_uhrzeit:'');
		$endezeit = ($bis_datum!=''?$datum_obj->formatDatum($bis_datum,'Y-m-d').' '.$bis_uhrzeit:'');
		$dauer = $_POST['dauer'];

		$dtstart=new DateTime($startzeit);
		$dtende = new DateTime($endezeit);

		if($dtende<$dtstart)
		{
			$evaluierung_zeitraum_msg= '<span class="error">'.$p->t('lvevaluierung/endeGroesserStart').'</span>';
		}
		else
		{

			$evaluierung = new lvevaluierung();

			$error = false;
			if($lvevaluierung_id!='')
			{
				if(!$evaluierung->load($lvevaluierung_id))
					die($p->t('global/fehlerBeimLadenDesDatensatzes'));
			}
			else
			{
				if($evaluierung->exists($lehrveranstaltung_id, $studiensemester_kurzbz))
				{
					$evaluierung_zeitraum_msg='<span class="error">Es ist bereits eine Evaluierung vorhanden</span>';
					$error = true;
				}
			}

			if(!$error)
			{
				$evaluierung->startzeit = $startzeit;
				$evaluierung->endezeit = $endezeit;
				$evaluierung->dauer = $dauer;
				$evaluierung->lehrveranstaltung_id = $lehrveranstaltung_id;
				$evaluierung->studiensemester_kurzbz = $studiensemester_kurzbz;
				$evaluierung->insertamum = date('Y-m-d H:i:s');
				$evaluierung->insertvon = $uid;
				$evaluierung->updateamum = date('Y-m-d H:i:s');
				$evaluierung->updatevon = $uid;

				if($evaluierung->save())
				{
					// Zugangscodes generieren
					$codes = new lvevaluierung_code();

					if(!$codes->generateCodes($evaluierung->lvevaluierung_id))
						$evaluierung_zeitraum_msg= '<span class="error">Failed: '.$codes->errormsg.'</span>';
					else
						$evaluierung_zeitraum_msg= '<span class="ok">'.$p->t('global/datenWurdenGespeichert').'</span>';
				}
				else
					$evaluierung_zeitraum_msg= '<span class="error">'.$p->t('global/fehlerBeimSpeichernDerDaten').':'.$evaluierung->errormsg.'</span>';
			}
		}
	}
}

// Speichern der ausgegebenen Codes
if(isset($_POST['saveAusgegeben']))
{
	$lvevaluierung_id = $_POST['lvevaluierung_id'];
	$codes_ausgegeben = $_POST['codes_ausgegeben'];
	$lv = new lehrveranstaltung();
	$lv->load($lehrveranstaltung_id);

	$evaluierung = new lvevaluierung();
	$evaluierung->load($lvevaluierung_id);
	$evaluierung->codes_ausgegeben = $codes_ausgegeben;

	$teilnehmer = $lv->getStudentsOfLv($lehrveranstaltung_id, $studiensemester_kurzbz);
	$anzahl_studierende=count($teilnehmer);

	if($codes_ausgegeben>$anzahl_studierende)
		$evaluierung_ausgegeben_msg= '<span class="error">'.$p->t('lvevaluierung/mehrCodesAusgegebenAlsStudierende').'</span>';
	else
	{
		if(!$evaluierung->save())
			$evaluierung_ausgegeben_msg= '<span class="error">'.$evaluierung->errormsg.'</span>';
		else
			$evaluierung_ausgegeben_msg='<span class="ok">'.$p->t('global/datenWurdenGespeichert').'</span>';
	}
	$jsjumpinfo='<script>window.location="#divcodes";</script>';
}

// Speichern der Selbstevaluierung
if(isset($_POST['saveSelbstevaluierung']) || isset($_POST['saveandsendSelbstevaluierung']))
{
	$sev = new lvevaluierung_selbstevaluierung();

	if(isset($_POST['lvevaluierung_selbstevaluierung_id'])
		&& $_POST['lvevaluierung_selbstevaluierung_id']!='')
	{
		if(!$sev->load($_POST['lvevaluierung_selbstevaluierung_id']))
			die('Fehler beim Laden der Daten:'.$sev->errormsg);

		if($sev->freigegeben)
			die('Diese Selbstevaluierung wurde bereits freigegeben und kann nicht mehr geändert werden');
	}
	else
	{
		$sev->insertamum = date('Y-m-d H:i:s');
		$sev->insertvon = $uid;
	}
	$sev->updateamum = date('Y-m-d H:i:s');
	$sev->updatevon = $uid;
	$sev->uid = $uid;
	$sev->lvevaluierung_id = $_POST['lvevaluierung_id'];
	$sev->persoenlich = $_POST['persoenlich'];
	$sev->entwicklung = $_POST['entwicklung'];
	$sev->gruppe = $_POST['gruppe'];
	$sev->weiterbildung = $_POST['weiterbildung'];

	if(isset($_POST['saveandsendSelbstevaluierung']))
		$sev->freigegeben = true;

	if($sev->save())
	{
		$evaluierung_selbsteval_msg= '<span class="ok">'.$p->t('global/erfolgreichgespeichert').'</span>';
		if($sev->freigegeben)
		{
			$lv = new lehrveranstaltung();
			$lv->load($lehrveranstaltung_id);

			$stg = new studiengang();
			$stg->load($lv->studiengang_kz);

			$to='';

			// Studiengangsleitung
			$stgleitung = $stg->getLeitung($lv->studiengang_kz);

			// geschaeftsfuehrende Studiengangsleitung
			$bnf = new benutzerfunktion();
			$bnf->getBenutzerFunktionen('gLtg', $stg->oe_kurzbz);
			foreach($bnf->result as $rowbnf)
				$stgleitung[]=$rowbnf->uid;

			// Institutsleitung (Nur wenn oe_kurzbz gesetzt)
			$institutsleitung = array();
			if ($lv->oe_kurzbz != '')
			{
				$bnf = new benutzerfunktion();
				$bnf->getBenutzerFunktionen('Leitung', $lv->oe_kurzbz);
				foreach($bnf->result as $rowbnf)
					$institutsleitung[] = $rowbnf->uid;
			}

			$leitung = array_merge($stgleitung, $institutsleitung);
			$leitung = array_unique($leitung);

			foreach($leitung as $rowltg)
				$to.=$rowltg.'@'.DOMAIN.',';

			$to = mb_substr($to,0,-1);
			$benutzer = new benutzer();
			$benutzer->load($uid);

			$text = '';
			$html = $p->t('lvevaluierung/XhatEineEvaluierungDurchgefuehrt',array($benutzer->titelpre.' '.$benutzer->vorname.' '.$benutzer->nachname.' '.$benutzer->titelpost)).'<br /><br />';
			$html.= LVEvaluierungGetInfoBlock($lv, $stg, $studiensemester_kurzbz);
			$html.= "\n".'<br><b>'.$p->t('lvevaluierung/selbstevaluierungGruppe').'</b><br />'.nl2br($db->convert_html_chars($sev->gruppe));
			$html.= "\n".'<br><br><b>'.$p->t('lvevaluierung/selbstevaluierungPersoenlich').'</b><br />'.nl2br($db->convert_html_chars($sev->persoenlich));
			$html.= "\n".'<br><br><b>'.$p->t('lvevaluierung/selbstevaluierungGeplanteEntwicklung').'</b><br />'.nl2br($db->convert_html_chars($sev->entwicklung));
			$html.= "\n".'<br><br><b>'.$p->t('lvevaluierung/selbstevaluierungWeiterbildung').'</b><br />'.nl2br($db->convert_html_chars($sev->weiterbildung));

			$html.= "\n".'<br><br><a href="'.APP_ROOT.'addons/lvevaluierung/cis/auswertung.php?lvevaluierung_id='.urlencode($sev->lvevaluierung_id).'">Detailauswertung anzeigen</a><br /><br /><br />';
			$from = 'noreply@'.DOMAIN;
			$subject = 'LV-Evaluierung - '.$studiensemester_kurzbz.' '.$stg->kuerzel.' '.$lv->semester.' '.$lv->orgform_kurzbz.' '.$lv->bezeichnung;
			$mail = new mail($to, $from, $subject, $text);
			$mail->setHTMLContent($html);
			$mail->setReplyTo($uid.'@'.DOMAIN);
			if($mail->send())
				$evaluierung_selbsteval_msg.= ' <span class="ok">'.$p->t('global/emailgesendetan').' '.$db->convert_html_chars($to).'</span>';
			else
				$evaluierung_selbsteval_msg.= ' <span class="error">'.$p->t('global/fehleraufgetreten').'</span>';
		}
	}
	else
		$evaluierung_selbsteval_msg.= '<span class="error">'.$p->t('global/fehleraufgetreten').' '.$sev->errormsg.'</span>';

	$jsjumpinfo='<script>window.location="#divselbsteval";</script>';
}

// Details zur Lehrveranstaltung Anzeigen
$lv = new lehrveranstaltung();
$lv->load($lehrveranstaltung_id);

echo '<h1>'.$p->t('lvevaluierung/lvevaluierung').' - '.$db->convert_html_chars($lv->bezeichnung.' ('.$lv->lehrveranstaltung_id.')').'</h1>';
if ($p->t('lvevaluierung/infotextAllgemein')!='')
	echo '<p>'.$p->t('lvevaluierung/infotextAllgemein').'.</p>';

$stg = new studiengang();
$stg->load($lv->studiengang_kz);

echo LVEvaluierungGetInfoBlock($lv, $stg, $studiensemester_kurzbz);

// Evaluierungszeitraum Form
echo '
<div class="lvepanel">
	<div class="lvepanel-head">'.$p->t('lvevaluierung/evaluierunganlegen').'</div>
	<div class="lvepanel-body">
		'.$p->t('lvevaluierung/evaluierunganlegenInfotext').'<br><br>';

$evaluierung = new lvevaluierung();
if(!$evaluierung->getEvaluierung($lehrveranstaltung_id, $studiensemester_kurzbz))
{
	$evaluierung->lehrveranstaltung_id=$lehrveranstaltung_id;
	$evaluierung->studiensemester_kurzbz=$studiensemester_kurzbz;
	$neu=true;
}
else
{
	$neu = false;
}

if($evaluierung->verpflichtend)
{
	echo $p->t('lvevaluierung/verpflichtendInfotext').'<br><br>';
}

echo '
		<div id="formular">

		<form action="administration.php?lehrveranstaltung_id='.urlencode($evaluierung->lehrveranstaltung_id).'&studiensemester_kurzbz='.urlencode($evaluierung->studiensemester_kurzbz).'" method="POST">
		<input type="hidden" name="lvevaluierung_id" value="'.$db->convert_html_chars($evaluierung->lvevaluierung_id).'" />
		<table>
			<tr>
				<td>'.$p->t('lvevaluierung/startzeit').'</td>
				<td>
				<input type="text" class="datepicker_datum" id="von_datum" name="von_datum" value="'.$db->convert_html_chars($datum_obj->formatDatum($evaluierung->startzeit,'d.m.Y')).'" size="9" onchange="setBisdatum(this.value)">
				<input type="text" class="timepicker" id="von_uhrzeit" name="von_uhrzeit" value="'.$db->convert_html_chars($datum_obj->formatDatum($evaluierung->startzeit,'H:i')).'" size="4">
				</td>
			</tr>
			<tr>
				<td>'.$p->t('lvevaluierung/endezeit').'</td>
				<td>
				<input type="text" class="datepicker_datum" id="bis_datum" name="bis_datum" value="'.$db->convert_html_chars($datum_obj->formatDatum($evaluierung->endezeit,'d.m.Y')).'" size="9">
				<input type="text" class="timepicker" id="bis_uhrzeit" name="bis_uhrzeit" value="'.$db->convert_html_chars($datum_obj->formatDatum($evaluierung->endezeit,'H:i')).'" size="4">
				</td>
			</tr>
			<tr>
				<td data-toggle="tooltip">'.$p->t('lvevaluierung/dauer').' <img src="'.APP_ROOT.'skin/images/information.png" title="'.$p->t('lvevaluierung/dauerInfotext').'" onclick="document.getElementById(\'dauer_infotext\').style.display=\'inline\'"></td>
				<td><input type="text" name="dauer" value="'.$db->convert_html_chars(mb_substr($evaluierung->dauer,0,5)).'" size="5" data-toggle="tooltip" title="'.$p->t('lvevaluierung/dauerInfotext').'" /> HH:MM   <span id="dauer_infotext" style="display: none">('.$p->t('lvevaluierung/dauerInfotext').')</span></td>
			</tr>
			<tr>
				<td></td>
				<td><input type="submit" name="saveEvaluierung" value="'.$p->t('global/speichern').'" /> '.$evaluierung_zeitraum_msg.'</td>
			</tr>
		</table>
		</form>
		</div>
	</div>
</div>
';

// Weitere Informationen werden erst angezeigt wenn eine Evaluierung angelegt wurde
if($evaluierung->lvevaluierung_id!='')
	$disabled=false;
else
	$disabled=true;

	$teilnehmer = $lv->getStudentsOfLv($lehrveranstaltung_id, $studiensemester_kurzbz);
	$anzahl_studierende=count($teilnehmer);

	// Erstellen der Codes
	echo '
	<div class="lvepanel '.($disabled?'disabled':'').'">
		<div class="lvepanel-head">'.$p->t('lvevaluierung/codesErstellen').'</div>
		<div class="lvepanel-body">'.$p->t('lvevaluierung/codesErstellenInfotext').'
			<br>
			<br>';
			if(!$disabled)
				echo '<a href="qrcode.php?lvevaluierung_id='.$evaluierung->lvevaluierung_id.'" onclick="showSpinner(\''.$anzahl_studierende.'\')">'.$p->t('lvevaluierung/CodeListeErstellen').'</a> <img id="spinner" style="display: none" src="'.APP_ROOT.'skin/images/spinner.gif">';
			else
				echo $p->t('lvevaluierung/CodeListeErstellen');
	echo '
			<br><br>
		</div>
	</div>';

	// Durchfuehrung der Evaluierung
	echo '
	<div class="lvepanel '.($disabled?'disabled':'').'">
		<div class="lvepanel-head">'.$p->t('lvevaluierung/evaluierungDruchfuehren').'</div>
		<div class="lvepanel-body">'.$p->t('lvevaluierung/evaluierungDruchfuehrenInfotext').'
		</div>
	</div>';


	// Ausgegebene Codes erfassen
	echo '
	<div class="lvepanel '.($disabled?'disabled':'').'" id="divcodes">
		<div class="lvepanel-head">'.$p->t('lvevaluierung/codesAusgegeben').'</div>
		<div class="lvepanel-body">
			'.$p->t('lvevaluierung/codesAusgegebenInfotext').'<br><br>
			<form action="administration.php?lehrveranstaltung_id='.urlencode($evaluierung->lehrveranstaltung_id).'&studiensemester_kurzbz='.urlencode($evaluierung->studiensemester_kurzbz).'" method="POST">
			<input type="hidden" name="lvevaluierung_id" value="'.$db->convert_html_chars($evaluierung->lvevaluierung_id).'" />
			'.$p->t('lvevaluierung/codesAusgegebenAnzahl').'
			<input type="text" '.($disabled?'disabled':'').' name="codes_ausgegeben" value="'.$db->convert_html_chars(($evaluierung->codes_ausgegeben!=''?$evaluierung->codes_ausgegeben:$anzahl_studierende)).'" size="9">
			<input type="submit" name="saveAusgegeben" '.($disabled?'disabled':'').' value="'.$p->t('global/speichern').'" />
			'.$evaluierung_ausgegeben_msg.'
			</form>
		</div>
	</div>
	';

	// Auswertung
	echo '
	<div class="lvepanel '.($disabled?'disabled':'').'">
		<div class="lvepanel-head">'.$p->t('lvevaluierung/auswertungAnzeigen').'</div>
		<div class="lvepanel-body">'.$p->t('lvevaluierung/auswertungAnzeigenInfotext').'
			<br>
			<br>';
			if(!$disabled)
				echo '<a href="auswertung.php?lvevaluierung_id='.$evaluierung->lvevaluierung_id.'">'.$p->t('lvevaluierung/Auswertung').'</a>';
			else
				$p->t('lvevaluierung/Auswertung');
	echo '
			<br><br>
		</div>
	</div>';

	// Selbstevaluierung

	$sev = new lvevaluierung_selbstevaluierung();
	$sev->getSelbstevaluierung($evaluierung->lvevaluierung_id);
	if($sev->freigegeben)
		$locked = 'disabled="disabled"';
	else
		$locked='';

	if($disabled)
		$locked = 'disabled="disabled"';

	//Anzeigen der Empfaenger
	$lv = new lehrveranstaltung();
	$lv->load($lehrveranstaltung_id);

	$stg = new studiengang();
	$stg->load($lv->studiengang_kz);

	$empfaenger='';

	// Studiengangsleitung
	$stgleitung = $stg->getLeitung($lv->studiengang_kz);

	// geschaeftsfuehrende Studiengangsleitung
	$bnf = new benutzerfunktion();
	$bnf->getBenutzerFunktionen('gLtg', $stg->oe_kurzbz);
	foreach($bnf->result as $rowbnf)
		$stgleitung[]=$rowbnf->uid;

	// Institutsleitung (Nur wenn oe_kurzbz gesetzt)
	$institutsleitung = array();
	if ($lv->oe_kurzbz != '')
	{
		$bnf = new benutzerfunktion();
		$bnf->getBenutzerFunktionen('Leitung', $lv->oe_kurzbz);
		foreach($bnf->result as $rowbnf)
			$institutsleitung[] = $rowbnf->uid;
	}

	$leitung = array_merge($stgleitung, $institutsleitung);
	$leitung = array_unique($leitung);

	foreach($leitung as $leiter_uid)
	{
		$benutzer = new benutzer();
		$benutzer->load($leiter_uid);

		if ($empfaenger != '')
			$empfaenger .= ', ';
		$empfaenger .= trim($benutzer->titelpre.' '.$benutzer->vorname.' '.$benutzer->nachname.' '.$benutzer->titelpost);
	}

	echo '
	<div class="lvepanel '.($disabled?'disabled':'').'" id="divselbsteval">
		<div class="lvepanel-head">'.$p->t('lvevaluierung/selbstevaluierung').'</div>
		<div class="lvepanel-body">'.$p->t('lvevaluierung/selbstevaluierungInfotext').'<br>
		'.$p->t('lvevaluierung/empfaengerDerSelbstevaluierung').': '.$empfaenger.'

		<form action="administration.php?lehrveranstaltung_id='.urlencode($evaluierung->lehrveranstaltung_id).'&studiensemester_kurzbz='.urlencode($evaluierung->studiensemester_kurzbz).'" method="POST">
		<input type="hidden" name="lvevaluierung_id" value="'.$db->convert_html_chars($evaluierung->lvevaluierung_id).'" />
		<input type="hidden" name="lvevaluierung_selbstevaluierung_id" value="'.$db->convert_html_chars($sev->lvevaluierung_selbstevaluierung_id).'" />
		<br>
		<table>
		<tr>
			<td valign="top"><b>'.$p->t('lvevaluierung/selbstevaluierungGruppe').'</b></td>
		</tr>
		<tr>
			<td>
				<textarea name="gruppe" '.$locked.'>'.$db->convert_html_chars($sev->gruppe).'</textarea>
			</td>
		</tr>
		<tr>
			<td valign="top"><b>'.$p->t('lvevaluierung/selbstevaluierungPersoenlich').'</b></td>
		</tr>
		<tr>
			<td>
				<textarea name="persoenlich" '.$locked.'>'.$db->convert_html_chars($sev->persoenlich).'</textarea>
			</td>
		</tr>
		<tr>
			<td valign="top"><b>'.$p->t('lvevaluierung/selbstevaluierungGeplanteEntwicklung').'</b></td>
		</tr>
		<tr>
			<td>
				<textarea name="entwicklung" '.$locked.'>'.$db->convert_html_chars($sev->entwicklung).'</textarea>
			</td>
		</tr>
		<tr>
			<td valign="top"><b>'.$p->t('lvevaluierung/selbstevaluierungWeiterbildung').'</b></td>
		</tr>
		<tr>
			<td>
				<textarea name="weiterbildung" '.$locked.'>'.$db->convert_html_chars($sev->weiterbildung).'</textarea>
			</td>
		</tr>
		<tr>
			<td>
				<input type="submit" name="saveSelbstevaluierung" value="'.$p->t('global/speichern').'"  '.$locked.'/>
				<input type="submit" name="saveandsendSelbstevaluierung" value="'.$p->t('lvevaluierung/speichernundabschicken').'"  '.$locked.' onclick="return confirm(\''.$p->t('lvevaluierung/confirmEvaluierungAbschicken').'\')"/>
				'.$evaluierung_selbsteval_msg.'
			</td>
		</tr>
		</table>
		</form>
		</div>
	</div>';
//}
echo $jsjumpinfo;
echo '</body></html>';

/**
 * Infoblock mit Details zur Lehrveranstaltung anzeigen
 * @param $lv Lehrverastaltung Objekt mit geladener LV
 * @param $stg Studiengang Objekt mit geladenem Studiengang
 * @param $studiensemester Studienemester Kurzbezeichnung
 */
function LVEvaluierungGetInfoBlock($lv, $stg, $studiensemester_kurzbz)
{
	global $p, $db;
	$leiter_uid = $lv->getLVLeitung($lv->lehrveranstaltung_id, $studiensemester_kurzbz);
	$benutzer = new benutzer();
	$benutzer->load($leiter_uid);

	$lvleitung=$benutzer->titelpre.' '.$benutzer->vorname.' '.$benutzer->nachname.' '.$benutzer->titelpost;

	$studiengang_bezeichnung=$stg->bezeichnung;
	$studiensemester = $studiensemester_kurzbz;

	$teilnehmer = $lv->getStudentsOfLv($lv->lehrveranstaltung_id, $studiensemester_kurzbz);
	$anzahl_studierende=count($teilnehmer);
	$lehrform = $lv->lehrform_kurzbz;

	$stg->getAllTypes();

	return '
	<table width="100%">
	<tr>
		<td valign="top">
			<table class="tablesorter">
			<tbody>
			<tr>
				<td>'.$p->t('lvevaluierung/lvbezeichnung').'</td>
				<td>'.$db->convert_html_chars($lv->bezeichnung.' ('.$lv->lehrveranstaltung_id.')').'</td>
			</tr>
			<tr>
				<td>'.$p->t('lvevaluierung/lvleitung').'</td>
				<td>'.$db->convert_html_chars($lvleitung).'</td>
			</tr>
			<tr>
				<td>'.$p->t('global/studiengang').'</td>
				<td>'.$db->convert_html_chars($stg->studiengang_typ_arr[$stg->typ].' '.$studiengang_bezeichnung).'</td>
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
				<td>'.$db->convert_html_chars($anzahl_studierende).'</td>
			</tr>
			</tbody>
			</table>
		</td>
	</tr>
	</table>';

}
?>
