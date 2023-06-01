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
 * Authors: Andreas Österreicher <andreas.oesterreicher@technikum-wien.at>,
 * 			Alexei Karpenko <karpenko@technikum-wien.at>,
 *			Cristina Hainberger <cristina.hainberg@technikum-wien.at>
 */
require_once('../lvevaluierung.config.inc.php');
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
$lv_aufgeteilt = false;

echo '<!DOCTYPE html>
<html>
	<head>
		<title>'.$p->t('lvevaluierung/lvevaluierung').'</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<link href="../../../skin/fhcomplete.css" rel="stylesheet" type="text/css">
		<link href="../../../skin/style.css.php" rel="stylesheet" type="text/css">
		<link href="../skin/lvevaluierung.css" rel="stylesheet" type="text/css">
		<link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css">
		<link rel="stylesheet" href="../../../vendor/fgelinas/timepicker/jquery.ui.timepicker.css" type="text/css"/>
		<link rel="stylesheet" type="text/css" href="../../../vendor/components/jqueryui/themes/base/jquery-ui.min.css"/>
		<link href="../../../skin/jquery-ui-1.9.2.custom.min.css" rel="stylesheet"  type="text/css">
		<script type="text/javascript" src="../../../vendor/jquery/jqueryV1/jquery-1.12.4.min.js"></script>
		<script type="text/javascript" src="../../../vendor/christianbach/tablesorter/jquery.tablesorter.min.js"></script>
		<script type="text/javascript" src="../../../vendor/components/jqueryui/jquery-ui.min.js"></script>
		<script src="../../../vendor/fgelinas/timepicker/jquery.ui.timepicker.js" type="text/javascript" ></script>
		<script type="text/javascript" src="../../../include/js/jquery.ui.datepicker.translation.js"></script>

		<script type="text/javascript">
		$(document).ready(function()
		{
			$( ".datepicker_datum" ).datepicker({
					 changeMonth: true,
					 changeYear: true,
					 dateFormat: "dd.mm.yy"
					 });

			$( ".timepicker" ).timepicker({
					showPeriodLabels: false,
					hourText: "Stunde",
					minuteText: "Minute",
					hours: {starts: 7,ends: 22},
					rows: 4
					});

			//Setzen des events für Anzeigen/Verstecken des Weiterbildung-textfeldes der Selbstevaluierung
			$("input[name=weiterbildung_bedarf]").click(toggleWeiterbildung);

		});

		function setBisdatum(datum)
		{
			if(document.getElementById("bis_datum").value=="")
				document.getElementById("bis_datum").value=datum;
		}

		function styleOnSubmit(form, anzahl_studierende)
		{
		    // Spinner anzeigen
		    showSpinner(anzahl_studierende);

	        // Nur wenn codes per mail gesendet werden, button sofort deaktivieren.
			// Verhindert mehrfaches Klicken und Versenden von codes an gleiche Teilnehmer.
			if (form.codes_verteilung.value == "mail")
			    {
			        form.submitCodesBtn.disabled = true;
			    }

    		return true;
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
		/**
		* zeigt/versteckt Weiterbildungstextfeld
		*/
		function toggleWeiterbildung()
		{
			var showWb = $("input[name=weiterbildung_bedarf]:checked").val();
			console.log(showWb);
			if(showWb === "ja")
			{
				$(".weiterb").show();
				window.scrollTo(0, document.body.scrollHeight);
				$("textarea[name=weiterbildung]").focus();
			}
			else
			{
				$(".weiterb").hide();
				$("textarea[name=weiterbildung]").val("");
			}
		}

		/**
		* clientseitige Validierung des Selbstevaluierungsformulars.
		* @param confirmtext Text der vor der Bestätigung durch den User zu Abschicken und Sperren des Formulars angezeigt wird
		* @param errormsg Fehlermeldung bei fehlerhaften/fehlenden Daten
		* @returns {boolean} ob das Formular richtig ausgefüllt wurde
		*/
		function validateSelbstevaluierungForm(confirmtext, errormsg)
		{
			var bedarfSelect= $("input[name=weiterbildung_bedarf]:checked").val();
			if(bedarfSelect == null)
			{
				$("input[name=saveandsendSelbstevaluierung]").parent().find(".error, .ok").remove();
				$("<span></span>").addClass("error").text(" "+errormsg).insertAfter("input[name=saveandsendSelbstevaluierung]");
				return false;
			}
			else
				return confirm(confirmtext);
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
	if (isset($_POST['lv_aufgeteilt']))
		if ($_POST['lv_aufgeteilt'] == 'true')
			$lv_aufgeteilt = true;
		else
			$lv_aufgeteilt = false;

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
				$evaluierung->lv_aufgeteilt = $lv_aufgeteilt;

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

	$evaluierung = new lvevaluierung();
	$evaluierung->load($lvevaluierung_id);
	$evaluierung->codes_ausgegeben = $codes_ausgegeben;

	//Prüfen ob Codeanzahl valide ist
	$validationresult = anzahlCodesValidieren($evaluierung, $lehrveranstaltung_id, $studiensemester_kurzbz, $p);

	if(!$validationresult["result"])
		$evaluierung_ausgegeben_msg= '<span class="error">'.$validationresult["error"].'</span>';
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
	$sev->weiterbildung_bedarf = null;
	if(isset($_POST['weiterbildung_bedarf']))
	{
		if ($_POST['weiterbildung_bedarf'] === "ja")
			$sev->weiterbildung_bedarf = true;
		else if ($_POST['weiterbildung_bedarf'] === "nein")
			$sev->weiterbildung_bedarf = false;
	}

	$sev->weiterbildung = $_POST['weiterbildung'];

	if(isset($_POST['saveandsendSelbstevaluierung']))
		$sev->freigegeben = true;

	$lvevaluierung_id = $_POST['lvevaluierung_id'];

	$evaluierung = new lvevaluierung();
	$evaluierung->load($lvevaluierung_id);

	//Validierung der Codeanzahl vor Speichern der Selbstevaluierung
	$validationresult = anzahlCodesValidieren($evaluierung, $lehrveranstaltung_id, $studiensemester_kurzbz, $p);
	//serverseitige Prüfung, dass Bedarf an Weiterbildung eingegeben ist
	if(isset($_POST['saveandsendSelbstevaluierung']) && !isset($sev->weiterbildung_bedarf))
		$evaluierung_selbsteval_msg= '<span class="error">'.$p->t('lvevaluierung/selbstevaluierungWeiterbFehlt').'</span>';
	else if(!$validationresult["result"] && $evaluierung->codes_gemailt == false) // wenn codes gemailt wurden, ist die Codeanzahl nicht relevant
		$evaluierung_selbsteval_msg= '<span class="error">'.$validationresult["error"].'</span>';
	else
	{
		if ($sev->save())
		{
			$evaluierung_selbsteval_msg = '<span class="ok">'.$p->t('global/erfolgreichgespeichert').'</span>';
			if ($sev->freigegeben)
			{
				$lv = new lehrveranstaltung();
				$lv->load($lehrveranstaltung_id);

				$stg = new studiengang();
				$stg->load($lv->studiengang_kz);

				$to = '';

				// Studiengangsleitung
				$stgleitung = $stg->getLeitung($lv->studiengang_kz);

				// geschaeftsfuehrende Studiengangsleitung
				/*
				$bnf = new benutzerfunktion();
				$bnf->getBenutzerFunktionen('gLtg', $stg->oe_kurzbz);
				foreach($bnf->result as $rowbnf)
					$stgleitung[]=$rowbnf->uid;
				*/

				// Institutsleitung (Nur wenn oe_kurzbz gesetzt)
				$institutsleitung = array();
				if ($lv->oe_kurzbz != '')
				{
					$bnf = new benutzerfunktion();
					$bnf->getBenutzerFunktionen('Leitung', $lv->oe_kurzbz);
					foreach ($bnf->result as $rowbnf)
						$institutsleitung[] = $rowbnf->uid;

					$rechte = new benutzerberechtigung();
					$rechte->getBerechtigungen($uid);

					if ($rechte->getBenutzerFromBerechtigung('addon/lvevaluierung_mail', false, $lv->oe_kurzbz))
					{
						if (isset($rechte->result) && is_array($rechte->result))
						{
							foreach ($rechte->result as $row_zusaetzlich)
								$institutsleitung[] = $row_zusaetzlich->uid;
						}
					}
				}

				$leitung = array_merge($stgleitung, $institutsleitung);
				$leitung = array_unique($leitung);

				foreach($leitung as $rowltg)
					$to.=$rowltg.'@'.DOMAIN.',';

				$to = mb_substr($to,0,-1);
				$benutzer = new benutzer();
				$benutzer->load($uid);

				$text = '';
				$html = $p->t('lvevaluierung/XhatEineEvaluierungDurchgefuehrt', array($benutzer->titelpre.' '.$benutzer->vorname.' '.$benutzer->nachname.' '.$benutzer->titelpost)).'<br /><br />';
				$html .= LVEvaluierungGetInfoBlock($lv, $stg, $studiensemester_kurzbz);
				$html .= "\n".'<br><b>'.$p->t('lvevaluierung/selbstevaluierungGruppe').'</b><br />'.nl2br($db->convert_html_chars($sev->gruppe));
				$html .= "\n".'<br><br><b>'.$p->t('lvevaluierung/selbstevaluierungPersoenlich').'</b><br />'.nl2br($db->convert_html_chars($sev->persoenlich));
				$html .= "\n".'<br><br><b>'.$p->t('lvevaluierung/selbstevaluierungGeplanteEntwicklung').'</b><br />'.nl2br($db->convert_html_chars($sev->entwicklung));
				$html .= "\n".'<br><br><b>'.$p->t('lvevaluierung/selbstevaluierungWeiterbildung').'</b><br />'.nl2br(($sev->weiterbildung_bedarf)?$p->t('global/ja'):$p->t('global/nein'));
				if($sev->weiterbildung_bedarf || !empty($sev->weiterbildung))
					$html .= "\n".'<br><br><b>'.$p->t('lvevaluierung/selbstevaluierungWeiterbildungArt').'</b><br />'.nl2br($db->convert_html_chars($sev->weiterbildung));

				$html .= "\n".'<br><br><a href="'.APP_ROOT.'addons/lvevaluierung/cis/auswertung.php?lvevaluierung_id='.urlencode($sev->lvevaluierung_id).'">Detailauswertung anzeigen</a><br /><br /><br />';
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
			$evaluierung_selbsteval_msg .= '<span class="error">'.$p->t('global/fehleraufgetreten').' '.$sev->errormsg.'</span>';
	}
	$jsjumpinfo='<script>window.location="#divselbsteval";</script>';
}

// Details zur Lehrveranstaltung anzeigen
$lv = new lehrveranstaltung();
$lv->load($lehrveranstaltung_id);

echo '<h1>'.$p->t('lvevaluierung/lvevaluierung').' - '.$db->convert_html_chars($lv->bezeichnung.' ('.$lv->lehrveranstaltung_id.')').'</h1>';
if ($p->t('lvevaluierung/infotextAllgemein')!='')
	echo '<p>'.$p->t('lvevaluierung/infotextAllgemein').'.</p>';

$stg = new studiengang();
$stg->load($lv->studiengang_kz);

//echo LVEvaluierungGetInfoBlock($lv, $stg, $studiensemester_kurzbz);

//check, ob eine LV mehrere Lektoren hat
$le_mitarbeiter = new Lehreinheitmitarbeiter();
$le_mitarbeiter->getMitarbeiterLV($lehrveranstaltung_id, $studiensemester_kurzbz);
$hasMehrereLektoren = false;
if (isset($le_mitarbeiter->result) && count($le_mitarbeiter->result) > 1)
	$hasMehrereLektoren = true;

// Evaluierungszeitraum Form
echo '
<div class="lvepanel">
	<div class="lvepanel-head">'.$p->t('lvevaluierung/evaluierunganlegen').'</div>
	<div class="lvepanel-body">
		';

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

//wenn freigegeben, sind Selbstevaluierung und Codeformular gesperrt
$sev = new lvevaluierung_selbstevaluierung();
$sev->getSelbstevaluierung($evaluierung->lvevaluierung_id);
$locked = ($sev->freigegeben)?'disabled="disabled"':'';

echo '
		<div id="formular">
		<form action="administration.php?lehrveranstaltung_id='.urlencode($evaluierung->lehrveranstaltung_id).'&studiensemester_kurzbz='.urlencode($evaluierung->studiensemester_kurzbz).'" method="POST">
		<input type="hidden" name="lvevaluierung_id" value="' . $db->convert_html_chars($evaluierung->lvevaluierung_id) . '">';



		echo $p->t('lvevaluierung/evaluierunganlegenInfotext');
	echo '
		<table>
			<tr>
				<td><br></td>
			</tr>
			<tr>
				<td>'.$p->t('lvevaluierung/startzeit').'</td>
				<td>
				<input type="text" class="datepicker_datum" id="von_datum" name="von_datum" value="'.$db->convert_html_chars($datum_obj->formatDatum($evaluierung->startzeit,'d.m.Y')).'" size="9" onchange="setBisdatum(this.value)" '.$locked.'>
				<input type="text" class="timepicker" id="von_uhrzeit" name="von_uhrzeit" value="'.$db->convert_html_chars($datum_obj->formatDatum($evaluierung->startzeit,'H:i')).'" size="4" '.$locked.'>
				</td>
			</tr>
			<tr>
				<td>'.$p->t('lvevaluierung/endezeit').'</td>
				<td>
				<input type="text" class="datepicker_datum" id="bis_datum" name="bis_datum" value="'.$db->convert_html_chars($datum_obj->formatDatum($evaluierung->endezeit,'d.m.Y')).'" size="9" '.$locked.'>
				<input type="text" class="timepicker" id="bis_uhrzeit" name="bis_uhrzeit" value="'.$db->convert_html_chars($datum_obj->formatDatum($evaluierung->endezeit,'H:i')).'" size="4" '.$locked.'>
				</td>
			</tr>
			<tr>
				<td data-toggle="tooltip">'.$p->t('lvevaluierung/dauer').' <img src="'.APP_ROOT.'skin/images/information.png" title="'.$p->t('lvevaluierung/dauerInfotext').'" onclick="document.getElementById(\'dauer_infotext\').style.display=\'inline\'"></td>
				<td><input type="text" name="dauer" value="'.$db->convert_html_chars(mb_substr($evaluierung->dauer,0,5)).'" size="5" data-toggle="tooltip" title="'.$p->t('lvevaluierung/dauerInfotext').'" '.$locked.' /> HH:MM   <span id="dauer_infotext" style="display: none">('.$p->t('lvevaluierung/dauerInfotext').')</span></td>
			</tr>';

//nur angezeigt wenn LV komplett getrennt durch mehrere Lektoren durchgeführt
if($hasMehrereLektoren)
{
	echo '
			<tr>
				<td><br></td>
			</tr>
			<tr>
				<td colspan="2"><small>' . $p->t('lvevaluierung/mehrereLektorenEineLvTxt') . '</small></td>
				<td><input type="radio" name="lv_aufgeteilt" value="true"'; if ($evaluierung->lv_aufgeteilt) echo 'checked '; else echo ''; echo $locked; echo '>' . $p->t('global/ja') . '
					<input type="radio" name="lv_aufgeteilt" value="false"'; if (!$evaluierung->lv_aufgeteilt) echo 'checked '; else echo ''; echo $locked; echo '>' . $p->t('global/nein') . '</td>
			</tr>
			<tr>
				<td><br></td>
			</tr>';

}

echo		'
			<tr>
				<td></td>
				<td><input type="submit" name="saveEvaluierung" value="'.$p->t('global/speichern').'" '.$locked.' /> '.$evaluierung_zeitraum_msg.'</td>
			</tr>
		</table>
		</form>
		</div>
	</div>
</div>
';

// Weitere Informationen werden erst angezeigt wenn Codes vorhanden
$codes = new lvevaluierung_code();
$codes->loadCodes($evaluierung->lvevaluierung_id);
if(count($codes->result) > 0)
	$disabled=false;
else
	$disabled=true;

	$teilnehmer = $lv->getStudentsOfLv($lehrveranstaltung_id, $studiensemester_kurzbz);
	$anzahl_studierende=count($teilnehmer);

	$codes_ausgegeben_msg  = '';
	if (!$evaluierung->codes_gemailt && $evaluierung->codes_ausgegeben)
	{
		$codes_ausgegeben_msg = $p->t('lvevaluierung/direktDurchgefuehrt');
	}
	elseif($evaluierung->codes_gemailt)
	{
		$codes_ausgegeben_msg = $p->t('lvevaluierung/perMailDurchgefuehrt', array($anzahl_studierende));
	}

	// Erstellen der Codes
	echo '
	<div class="lvepanel '.($disabled?'disabled':'').'">
		<div class="lvepanel-head">'.$p->t('lvevaluierung/codesErstellen').'</div>
		<div class="lvepanel-body">';
			if (!defined('ADDON_LVEVALUIERUNG_CODES_DRUCKEN') || ADDON_LVEVALUIERUNG_CODES_DRUCKEN)
				echo $p->t('lvevaluierung/codesErstellenInfotext');
			else
				echo $p->t('lvevaluierung/codesErstellenInfotextNurMail');

			echo '<br><br>';
			if(!$disabled)
			{
				echo '<form action="qrcode.php" method="GET" onsubmit="return styleOnSubmit(this, '. $anzahl_studierende. ');">';
				echo '<input type="hidden" name="lvevaluierung_id" value="'. $evaluierung->lvevaluierung_id. '">';
				echo '<table>';
				echo '<tr>';
				if (!defined('ADDON_LVEVALUIERUNG_CODES_DRUCKEN') || ADDON_LVEVALUIERUNG_CODES_DRUCKEN)
				{
					echo '<td><input type="radio" name="codes_verteilung" value="print"'; echo (!$evaluierung->codes_gemailt) ? 'checked ' : ''; echo (!$evaluierung->codes_gemailt && $evaluierung->codes_ausgegeben) ? 'disabled ' : ''; echo $locked; echo '>' . $p->t('lvevaluierung/CodeListeErstellen'). '</td>';
					echo '<td><input type="radio" name="codes_verteilung" value="mail"'; echo ($evaluierung->codes_gemailt) ? 'checked ' : ''; echo ($evaluierung->codes_ausgegeben) ? 'disabled ' : ''; echo $locked; echo '>' . $p->t('lvevaluierung/CodeListeMailen'). '</td>';
				}
				else
				{
					echo '<td><input type="radio" name="codes_verteilung" value="mail" checked'; echo ($evaluierung->codes_ausgegeben) ? 'disabled ' : ''; echo $locked; echo '>' . $p->t('lvevaluierung/CodeListeMailen'). '</td>';
				}
				echo '<td width="100" align="center"><input type="submit" name="submitCodesBtn" value="'. $p->t('global/durchfuehren'). '"';  echo ($evaluierung->codes_gemailt) ? 'disabled ' : ''; echo $locked; echo ' /></td>';
				echo '<td><img id="spinner" style="display: none" src="'.APP_ROOT.'skin/images/spinner.gif"></td>';
				echo '<td><span style="color: green"><b>'. $codes_ausgegeben_msg. '</b></span></td>';
				echo '</tr>';
				echo '</table>';
				echo '</form>';
			}
			else
			{
				if (!defined('ADDON_LVEVALUIERUNG_CODES_DRUCKEN') || ADDON_LVEVALUIERUNG_CODES_DRUCKEN)
					echo $p->t('lvevaluierung/CodeListeErstellen');
				echo '<table>';
				echo '<tr>';
				if (!defined('ADDON_LVEVALUIERUNG_CODES_DRUCKEN') || ADDON_LVEVALUIERUNG_CODES_DRUCKEN)
				{
					echo '<td><input type="radio">'. $p->t('lvevaluierung/CodeListeErstellen'). '</td>';
					echo '<td><input type="radio">' . $p->t('lvevaluierung/CodeListeMailen'). '</td>';
				}
				else
				{
					echo '<td><input type="radio">' . $p->t('lvevaluierung/CodeListeMailen'). '</td>';
				}
				echo '<td width="100" align="center"><input type="submit" disabled/></td>';
				echo '</tr>';
				echo '</table>';
			}
	echo '
			<br><br>
		</div>
	</div>';

	// Durchfuehrung der Evaluierung
	echo '
	<div class="lvepanel '.($disabled || $evaluierung->codes_gemailt ?'disabled':'').'">
		<div class="lvepanel-head">'.$p->t('lvevaluierung/evaluierungDruchfuehren').'</div>
		<div class="lvepanel-body">'.$p->t('lvevaluierung/evaluierungDruchfuehrenInfotext').'
		</div>
	</div>';


	// Ausgegebene Codes erfassen
//	$anzahl_codes_ausgegeben = $evaluierung->codes_gemailt ? $anzahl_studierende : $evaluierung->codes_ausgegeben;
	echo '
	<div class="lvepanel '.($disabled || $evaluierung->codes_gemailt ?'disabled ':''). '" id="divcodes">
		<div class="lvepanel-head">'.$p->t('lvevaluierung/codesAusgegeben').'</div>
		<div class="lvepanel-body">
			'.$p->t('lvevaluierung/codesAusgegebenInfotext').'<br><br>
			<form action="administration.php?lehrveranstaltung_id='.urlencode($evaluierung->lehrveranstaltung_id).'&studiensemester_kurzbz='.urlencode($evaluierung->studiensemester_kurzbz).'" method="POST">
			<input type="hidden" name="lvevaluierung_id" value="'.$db->convert_html_chars($evaluierung->lvevaluierung_id).'" />
			'.$p->t('lvevaluierung/codesAusgegebenAnzahl').'
			<input type="text" '.($disabled || $evaluierung->codes_gemailt ?'disabled':'').' name="codes_ausgegeben" size="9" value="' . $evaluierung->codes_ausgegeben . '">
			<input type="submit" name="saveAusgegeben" '.($disabled || $evaluierung->codes_gemailt ?'disabled':'').' value="'.$p->t('global/speichern').'" />
			'.$evaluierung_ausgegeben_msg.'
			</form>
		</div>
	</div>
</div>';

// Auswertung
if (!defined('ADDON_LVEVALUIERUNG_AUSWERTUNG_ANZEIGEN') || ADDON_LVEVALUIERUNG_AUSWERTUNG_ANZEIGEN)
{
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
}

// Selbstevaluierung
if (!$disabled)
{
	//Selbstevaluierung ist deaktiviert wenn codesausgegeben noch nicht valide gespeichert ist bzw. solange die Codes noch nicht per mail versendet worden sind
	if ((!anzahlCodesValidieren($evaluierung, $lehrveranstaltung_id, $studiensemester_kurzbz, $p)["result"]
		&& $evaluierung->codes_gemailt == false)
		&& !$sev->freigegeben)
		$disabled = true;
}

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
/*
$bnf = new benutzerfunktion();
$bnf->getBenutzerFunktionen('gLtg', $stg->oe_kurzbz);
foreach($bnf->result as $rowbnf)
	$stgleitung[]=$rowbnf->uid;
*/
// Institutsleitung (Nur wenn oe_kurzbz gesetzt)
$institutsleitung = array();
if ($lv->oe_kurzbz != '')
{
	$bnf = new benutzerfunktion();
	$bnf->getBenutzerFunktionen('Leitung', $lv->oe_kurzbz);
	foreach($bnf->result as $rowbnf)
		$institutsleitung[] = $rowbnf->uid;

	$rechte = new benutzerberechtigung();
	$rechte->getBerechtigungen($uid);

	if($rechte->getBenutzerFromBerechtigung('addon/lvevaluierung_mail', false, $lv->oe_kurzbz))
	{
		if(isset($rechte->result) && is_array($rechte->result))
		{
			foreach($rechte->result as $row_zusaetzlich)
				$institutsleitung[] = $row_zusaetzlich->uid;
		}
	}
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

if($disabled && !$sev->freigegeben)
	echo '<div id ="codesHinweistext" class="lvepanel"><div class="lvepanel-body"><br>'.$p->t('lvevaluierung/hinweisCodesNichtEingetragenSelbstevaluierung').'<br><br></div></div>';

$checked = $display = $checkedNein = '';

if((!isset($sev->weiterbildung_bedarf)  || $sev->weiterbildung_bedarf === '') && (!isset($sev->weiterbildung) || $sev->weiterbildung == ''))
	//Wenn sowohl weiterbildung_bedarf (ja/nein) als auch Weiterbildung nicht eingetragen/leer sind, Texfeld für Weiterbildung nicht anzeigen
	$display = 'style="display:none;"';
else if($sev->weiterbildung_bedarf === true)//Weiterbildung auf "ja"
	$checked = 'checked="checked"';
else if($sev->weiterbildung_bedarf === false)//Weiterbildung auf "nein"
{
	$checkedNein = 'checked="checked"';
	$display = 'style="display:none;"';
}

//bei serverseitiger Formularvalidierung: bei Neuladen aufgrund von Fehlern sollen eingegebene Werte erhalten bleiben
$selbstevalNames = array("gruppe", "persoenlich", "entwicklung", "weiterbildung");
foreach($selbstevalNames as $name)
	${$name} = $db->convert_html_chars((isset($_POST[$name]))?$_POST[$name]:$sev->{$name});

if (!defined('ADDON_LVEVALUIERUNG_SELBSTEVALUIERUNG_ANZEIGEN') || ADDON_LVEVALUIERUNG_SELBSTEVALUIERUNG_ANZEIGEN)
{
	echo '
	<div class="lvepanel '.($disabled?'disabled':'').'" id="divselbsteval">
		<div class="lvepanel-head">'.$p->t('lvevaluierung/selbstevaluierung').'</div>
		<div class="lvepanel-body">'.$p->t('lvevaluierung/selbstevaluierungInfotext').'<br>
		'.$p->t('lvevaluierung/empfaengerDerSelbstevaluierung').': '.$empfaenger.'

		<form action="administration.php?lehrveranstaltung_id='.urlencode($evaluierung->lehrveranstaltung_id).'&studiensemester_kurzbz='.urlencode($evaluierung->studiensemester_kurzbz).'" method="POST">
		<input type="hidden" name="lvevaluierung_id" value="'.$db->convert_html_chars($evaluierung->lvevaluierung_id).'" />
		<input type="hidden" name="lvevaluierung_selbstevaluierung_id" value="'.$db->convert_html_chars($sev->lvevaluierung_selbstevaluierung_id).'" />
		<br>
		<table width="80%">
		<tr>
			<td colspan="2" valign="top"><b>'.$p->t('lvevaluierung/selbstevaluierungGruppe').'</b></td>
		</tr>
		<tr>
			<td colspan="2">
				<textarea name="gruppe" '.$locked.'>'.$gruppe.'</textarea>
			</td>
		</tr>
		<tr>
			<td colspan="2" valign="top"><b>'.$p->t('lvevaluierung/selbstevaluierungPersoenlich').'</b></td>
		</tr>
		<tr>



			<td colspan="2">
				<textarea name="persoenlich" '.$locked.'>'.$db->convert_html_chars($sev->persoenlich).'</textarea>
			</td>
		</tr>
		<tr>
			<td colspan="2" valign="top"><b>'.$p->t('lvevaluierung/selbstevaluierungGeplanteEntwicklung').'</b></td>
		</tr>
		<tr>
			<td colspan="2">
				<textarea name="entwicklung" '.$locked.'>'.$entwicklung.'</textarea>
			</td>
		</tr>
		<tr>
			<td colspan="2" valign="top"><b>'.$p->t('lvevaluierung/selbstevaluierungWeiterbildung').' *</b></td>
		</tr>
		<tr align="center">
			<td>
				<label for="weiterbradio1">'.$p->t('global/ja').'</label>
				<input type="radio" name="weiterbildung_bedarf" id="weiterbradio1" value="ja" '.$checked.' '.$locked.' />
				<label for="weiterbradio2">'.$p->t('global/nein').'</label>
				<input type="radio" name="weiterbildung_bedarf" id="weiterbradio2" value="nein" '.$checkedNein.' '.$locked.' />
			</td>
		</tr>
		<tr '.$display.' class="weiterb">
			<td valign="top" colspan="2"><b>'.$p->t('lvevaluierung/selbstevaluierungWeiterbildungArt').'</b></td>
		</tr>
		<tr '.$display.' class="weiterb">
			<td colspan="2">
				<textarea name="weiterbildung" '.$locked.'>'.$weiterbildung.'</textarea>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<input type="submit" name="saveSelbstevaluierung" value="'.$p->t('global/speichern').'"  '.$locked.'/>
				<input type="submit" name="saveandsendSelbstevaluierung" value="'.$p->t('lvevaluierung/speichernundabschicken').'"  '.$locked.' onclick="return validateSelbstevaluierungForm(&quot;'.$p->t('lvevaluierung/confirmEvaluierungAbschicken').'&quot;, &quot;'.$p->t('lvevaluierung/selbstevaluierungWeiterbFehlt').'&quot;)"/>
				'.$evaluierung_selbsteval_msg.'
			</td>
		</tr>
		</table>
		</form>
		</div>
	</div>';
//}
}

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
	$lehrmodus = $lv->lehrmodus_kurzbz;

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
				<td>'.$p->t('lvevaluierung/lvmodus').'</td>
				<td>'.$db->convert_html_chars($lehrmodus).'</td>
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

/**
 * Liefert Anzahl Evaluierungsbögen zurück, die von den Studierenden ausgefüllt wurden
 * (Kriterium für ausgefüllt ist eine vorhandene Endzeit)
 * @param $lvevaluierung_id id der  Evaluierung
 * @return int Anzahl ausgefüllter Evaluierungsbögen
 */
function getAusgefuellteBoegen($lvevaluierung_id)
{
	$codes = new lvevaluierung_code();
	$codes->loadCodes($lvevaluierung_id);
	$anzahl_codes_beendet = 0;

	foreach ($codes->result as $code)
	{
		if ($code->endezeit != '')
			$anzahl_codes_beendet++;
	}
	return $anzahl_codes_beendet;
}

/**
 * Führt Checks betreffend Anzahl eingegebener Evaluierungscodes durch:
 * [Anzahl ausgefüllter Evaluierungsbögen] <= [Anzahl ausgegebener Evaluierungscodes] <= [Anzahl Studierende]
 * @param $evaluierung beinhaltet Evaluierungsinfos wie ausgegebene Codes
 * @param $lvid Lehrveranstaltungsid für Bestimmung der Anzahl der Studierenden
 * @param $studiensemester_kurzbz Studiensemesterkurzbezeichnung für Bestimmung der Anzahl der Studierenden
 * @param $p Phrasenobjekt für Fehlermeldungen
 * @return array assoziatives array mit Einträgen "result" (alle Checks erfolgreich oder nicht) und "error" (Text Fehlermeldung)
 */
function anzahlCodesValidieren($evaluierung, $lvid, $studiensemester_kurzbz, $p)
{
	$codes_ausgegeben = $evaluierung->codes_ausgegeben;

	$anzahl_codes_beendet = getAusgefuellteBoegen($evaluierung->lvevaluierung_id);

	$lv = new lehrveranstaltung();
	$lv->load($lvid);
	$teilnehmer = $lv->getStudentsOfLv($lvid, $studiensemester_kurzbz);
	$anzahl_studierende=count($teilnehmer);

	if (empty($codes_ausgegeben) || $codes_ausgegeben < 0)
		return array("result" => false, "error" => $p->t('lvevaluierung/codesNichtEingetragen'));
	/*else if ($codes_ausgegeben>$anzahl_studierende)
		return array("result" => false, "error" => $p->t('lvevaluierung/mehrCodesAusgegebenAlsStudierende'));
	*/
	else if ($anzahl_codes_beendet>$codes_ausgegeben)
		return array("result" => false, "error" => $p->t('lvevaluierung/mehrAusgefuelltAlsAusgegeben'));
	return array("result" => true);
}
?>
