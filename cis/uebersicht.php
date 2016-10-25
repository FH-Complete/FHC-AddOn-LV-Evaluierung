<?php
/* Copyright (C) 2016 fhcomplete.org
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
require_once('../include/lvevaluierung_selbstevaluierung.class.php');
require_once('../include/lvevaluierung_code.class.php');
require_once('../../../include/studiensemester.class.php');
$uid = get_uid();

$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);

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
		<script>
		$(document).ready(function()
		{
			$("#t1").tablesorter(
			{
				sortList: [[0,0]],
				widgets: ["zebra"]
			});
		});
		</script>
	</head>
<body>
';
echo '<h1>'.$p->t('lvevaluierung/uebersicht').'</h1>';

// Berechtigungen pruefen
if(!$rechte->isBerechtigt('addon/lvevaluierung'))
{
	die($p->t('global/keineBerechtigungFuerDieseSeite').'. '.$rechte->errormsg);
}


$studiengang_kz = filter_input(INPUT_POST,'studiengang_kz');
$semester = filter_input(INPUT_POST,'semester');

$studiengang = new studiengang();

if(count($rechte->getFbKz('addon/lvevaluierung'))>0)
{
	$studiengang->getAll('typ, kurzbz',true);
}
else
{
	$stg_arr = $rechte->getStgKz('addon/lvevaluierung');
	$studiengang->loadArray($stg_arr,'typ, kurzbz',true);
}

echo '
<form action="uebersicht.php" method="POST">
<select name="studiengang_kz">';
foreach($studiengang->result as $row_stg)
{
	if($studiengang_kz=='')
		$studiengang_kz=$row_stg->studiengang_kz;

	if($studiengang_kz==$row_stg->studiengang_kz)
		$selected = 'selected="selected"';
	else
		$selected='';

	echo '<option value="'.$row_stg->studiengang_kz.'" '.$selected.'>'.$db->convert_html_chars($row_stg->kuerzel.' - '.$row_stg->kurzbzlang).'</option>';
}
echo '
</select>
<select name="semester">';
for($i=1;$i<=10;$i++)
{
	if($semester=='')
		$semester = $i;

	if($semester==$i)
		$selected='selected="selected"';
	else
		$selected = '';

	echo '<option value="'.$i.'" '.$selected.'>'.$i.'</option>';
}
echo '
</select>
<input type="submit" value="'.$p->t('global/anzeigen').'">
</form>';

$lv = new lehrveranstaltung();
$lv-> load_lva($studiengang_kz, $semester,null,true,true);

$stsem = new studiensemester();
$stsem->getPlusMinus(1, 5);
$stsem_rev = array_reverse($stsem->studiensemester);
echo '<table class="tablesorter" id="t1">
<thead>
<tr>
	<th>'.$p->t('global/bezeichnung').'</th>';
foreach($stsem_rev as $row_stsem)
{
	echo '<th>'.$db->convert_html_chars($row_stsem->studiensemester_kurzbz).'</th>';
}

echo '</tr>
</thead>
<tbody>';
$stg = new studiengang();
$stg->load($studiengang_kz);
foreach($lv->lehrveranstaltungen as $row_lv)
{
	$lva = new lehrveranstaltung();
	$lva->load($row_lv->lehrveranstaltung_id);
	$oes = $lva->getAllOe();
	$oes[]=$lva->oe_kurzbz; // Institut
	$oes[]=$stg->oe_kurzbz; // OE des Studiengangs der Lehrveranstaltung
	$oes = array_unique($oes);
	$allowed_to_show_lv=true;

	if(!$rechte->isBerechtigtMultipleOe('addon/lvevaluierung',$oes,'s'))
		$allowed_to_show_lv=false;

	echo '
	<tr>
	<td>'.$db->convert_html_chars($row_lv->bezeichnung.' '.$row_lv->orgform_kurzbz).'</td>';

	foreach($stsem_rev as $row_stsem)
	{
		echo '<td align="center">';
		$evaluierung = new lvevaluierung();


		if($evaluierung->getEvaluierung($row_lv->lehrveranstaltung_id, $row_stsem->studiensemester_kurzbz))
		{
			if(!$allowed_to_show_lv)
			{
				echo 'X';
				continue;
			}

			$codes = new lvevaluierung_code();

			// Ruecklaufqoute ermitteln
			$codes->loadCodes($evaluierung->lvevaluierung_id);
			$anzahl_codes_beendet=0;
			foreach($codes->result as $row_code)
			{
				if($row_code->endezeit!='')
					$anzahl_codes_beendet++;
			}

			if($evaluierung->codes_ausgegeben!='')
				$anzahl_codes_gesamt = $evaluierung->codes_ausgegeben;
			else
				$anzahl_codes_gesamt = count($codes->result);

			if($anzahl_codes_gesamt>0)
				$prozent_abgeschlossen = (100/$anzahl_codes_gesamt*$anzahl_codes_beendet);
			else
				$prozent_abgeschlossen = 0;

			echo '<a href="auswertung.php?lvevaluierung_id='.$evaluierung->lvevaluierung_id.'" title="Rücklaufqoute - klicken für Detailauswertung">'.number_format($prozent_abgeschlossen,2).'</a>';

			$sev = new lvevaluierung_selbstevaluierung();
			if($sev->getSelbstevaluierung($evaluierung->lvevaluierung_id))
				echo '&nbsp;&nbsp;&nbsp;<a href="#" onclick="javascript:window.open(\'selbstevaluierung.php?lvevaluierung_id='.$evaluierung->lvevaluierung_id.'\',\'Selbstevaluierung\',\'width=700,height=750,resizable=yes,menuebar=no,toolbar=no,status=yes,scrollbars=yes\');return false;"><img src="../../../skin/images/edit-paste.png" height="15px" title="Selbstevaluierung anzeigen"/></a>';
		}

		echo '</td>';

	}
	echo '</tr>';
}
echo '</tbody></table>
</body>
</html>';
?>