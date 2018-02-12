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
require_once('../../../include/organisationsform.class.php');

$uid = get_uid();

$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);

$sprache = getSprache();
$p = new phrasen($sprache);

$db = new basis_db();
$datum_obj = new datum();

if(isset($_POST['action']) && $_POST['action']=='changestatus')
{
	if(!$rechte->isBerechtigt('addon/lvevaluierung'))
	{
		die($p->t('global/keineBerechtigungFuerDieseSeite').'. '.$rechte->errormsg);
	}

	$lehrveranstaltung_id = $_POST['lehrveranstaltung_id'];
	$studiensemester_kurzbz = $_POST['studiensemester_kurzbz'];
	$verpflichtend = ($_POST['verpflichtend']=='true'?false:true);

	if(!is_numeric($lehrveranstaltung_id))
		die('Lehrveranstaltung ID ist ungueltig');
	if(!check_stsem($studiensemester_kurzbz))
		die('Studiensemester ist ungueltig');

	// Rechte pruefen
	$lva = new lehrveranstaltung();
	$lva->load($lehrveranstaltung_id);
	$stg = new studiengang();
	$stg->load($lva->studiengang_kz);

	$oes = $lva->getAllOe();
	$oes[]=$lva->oe_kurzbz; // Institut
	$oes[]=$stg->oe_kurzbz; // OE des Studiengangs der Lehrveranstaltung
	$oes = array_unique($oes);

	if(!$rechte->isBerechtigtMultipleOe('addon/lvevaluierung',$oes,'su'))
		die($rechte->errormsg);

	$evaluierung = new lvevaluierung();
	if($evaluierung->getEvaluierung($lehrveranstaltung_id, $studiensemester_kurzbz))
	{
		// Wenn die Evaluierung bereits existiert,
		// und diese bereits eine Startzeit hat, dann aendern
		if($evaluierung->startzeit!='' || $verpflichtend==true)
		{
            $evaluierung->updateamum = date('Y-m-d H:i:s');
            $evaluierung->updatevon = $uid;
			$evaluierung->verpflichtend = $verpflichtend;
			if($evaluierung->save())
			{
				echo 'true';
				exit;
			}
			else
			{
				echo $evaluierung->errormsg;
				exit;
			}
		}
		else
		{
			// Wenn noch keine Startzeit eingetragen ist, dann wird die Evaluierung geloescht
			// da sie in diesem Fall nur exitiert um als verpflichtend markiert zu sein.
			if($evaluierung->delete($evaluierung->lvevaluierung_id))
			{
				echo 'true';
				exit;
			}
			else
			{
				echo $evaluierung->errormsg;
				exit;
			}
		}
	}
	else
	{
		// Wenn keine Evaluierung vorhanden ist, dann eine Anlegen und verpflichtend setzen

		$evaluierung->lehrveranstaltung_id = $lehrveranstaltung_id;
		$evaluierung->studiensemester_kurzbz = $studiensemester_kurzbz;
		$evaluierung->insertamum = date('Y-m-d H:i:s');
		$evaluierung->insertvon = $uid;
		$evaluierung->verpflichtend = true;
		if($evaluierung->save())
		{
			echo 'true';
			exit;
		}
		else
		{
			echo $evaluierung->errormsg;
			exit;
		}
	}
	echo 'false';
	exit;
}

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
		<link rel="stylesheet" type="text/css" ../../../vendor/components/jqueryui/themes/base/jquery-ui.min.css"/>
		<link href="../../../skin/jquery-ui-1.9.2.custom.min.css" rel="stylesheet"  type="text/css">
		<script type="text/javascript" src="../../../vendor/jquery/jqueryV1/jquery-1.12.4.min.js"></script>
		<script type="text/javascript" src="../../../vendor/christianbach/tablesorter/jquery.tablesorter.min.js"></script>
		<script type="text/javascript" src="../../../vendor/components/jqueryui/jquery-ui.min.js"></script>
		<script src="../../../vendor/fgelinas/timepicker/jquery.ui.timepicker.js" type="text/javascript" ></script>
		<script type="text/javascript" src="../../../include/js/jquery.ui.datepicker.translation.js"></script>
		<script>
		$(document).ready(function()
		{
			$("#t1").tablesorter(
			{
				sortList: [[0,0]],
				widgets: ["zebra"]
			});
		});

		function changeState(lehrveranstaltung_id, studiensemester_kurzbz)
		{
			var img = document.getElementById("img_eval_verpflichtend_"+lehrveranstaltung_id+"_"+studiensemester_kurzbz);
			var sum = parseInt($("#sum_"+studiensemester_kurzbz).html());

			$.ajax({
				type: "POST",
				url: "lvevaluierung_anfordern.php",
				data: {
					"action":"changestatus",
					"verpflichtend":img.dataset.verpflichtend,
					"studiensemester_kurzbz":studiensemester_kurzbz,
					"lehrveranstaltung_id":lehrveranstaltung_id
				},
				success: function(data) {
					if(data=="true")
					{
						if(img.dataset.verpflichtend=="true")
						{
							img.setAttribute("src","../skin/images/emblem-person-green.png");
							img.setAttribute("title","'.$p->t('lvevaluierung/freiwillig').'");
							sum = sum - 1;
							img.dataset.verpflichtend="false";
						}
						else
						{
							img.setAttribute("src","../skin/images/emblem-person-red.png");
							img.setAttribute("title","'.$p->t('lvevaluierung/verpflichtend').'");
							sum = sum + 1;
							img.dataset.verpflichtend="true";
						}
						$("#sum_"+studiensemester_kurzbz).html(sum);
					}
					else
					{
						alert("Es ist ein Fehler aufgetreten:"+data);
					}
				}
			});
		}
		</script>
		<style>
		td.offered
		{
			border: 1px solid green !important;
		}
		td.notoffered
		{
		}
		</style>
	</head>
<body>
';
echo '<h1>'.$p->t('lvevaluierung/evaluierungAnfordern'). ' & ' . $p->t('lvevaluierung/Auswertung') . '</h1>';

// Berechtigungen pruefen
if(!$rechte->isBerechtigt('addon/lvevaluierung'))
{
	die($p->t('global/keineBerechtigungFuerDieseSeite').'. '.$rechte->errormsg);
}

if (!isset ($_POST['studiengang_kz']))
    header('Location: uebersicht.php');

$studiengang_kz = filter_input(INPUT_POST,'studiengang_kz');
$semester = filter_input(INPUT_POST,'semester');
$oe_kurzbz = filter_input(INPUT_POST,'oe_kurzbz');
$orgform_kurzbz = filter_input(INPUT_POST,'orgform_kurzbz');
//var_dump($_POST);

if($studiengang_kz=='' && $oe_kurzbz=='')
	die($p->t('lvevaluierung/waehleStudiengangoderInstitut'));

$studiengang = new studiengang();
$fachbereich_arr = $rechte->getFbKz('addon/lvevaluierung');
if(count($fachbereich_arr)>0)
{
	$studiengang->getAll('typ, kurzbz',true);
}
else
{
	$stg_arr = $rechte->getStgKz('addon/lvevaluierung');
	$studiengang->loadArray($stg_arr,'typ, kurzbz',true);
}

$types = new studiengang();
$types->getAllTypes();
$typ = '';
echo '
<form action="lvevaluierung_anfordern.php" method="POST">
<select name="studiengang_kz">
<option value="">-- '.$p->t('global/studiengang').' --</option>';
foreach($studiengang->result as $row_stg)
{
	//if($studiengang_kz=='')
	//	$studiengang_kz=$row_stg->studiengang_kz;

	if ($typ != $row_stg->typ || $typ=='')
	{
		if ($typ!='')
			echo '</optgroup>';
			echo '<optgroup label="'.($types->studiengang_typ_arr[$row_stg->typ]!=''?$types->studiengang_typ_arr[$row_stg->typ]:$row_stg->typ).'">';
	}

	if($studiengang_kz == $row_stg->studiengang_kz)
		$selected = 'selected="selected"';
	else
		$selected='';

	$typ = $row_stg->typ;

	echo '<option value="'.$row_stg->studiengang_kz.'" '.$selected.'>'.$db->convert_html_chars($row_stg->kuerzel.' - '.$row_stg->bezeichnung).'</option>';
}
echo '
</select>
<select name="semester">
<option value="">-- '.$p->t('global/semester').' --</option>';
for($i=1;$i<=10;$i++)
{
	//if($semester=='')
	// $semester = $i;

	if($semester==$i)
		$selected='selected="selected"';
	else
		$selected = '';

	echo '<option value="'.$i.'" '.$selected.'>'.$i.'</option>';
}
echo '
</select>
<select name="orgform_kurzbz">
<option value="">-- '.$p->t('global/organisationsform').' --</option>';
$orgform = new organisationsform();
$orgform->getOrgformLV();
foreach ($orgform->result as $row_orgform)
{
	if($orgform_kurzbz == $row_orgform->orgform_kurzbz)
		$selected = 'selected';
	else
		$selected = '';

	echo '<option value="'.$row_orgform->orgform_kurzbz.'" '.$selected.'>'.$db->convert_html_chars($row_orgform->orgform_kurzbz.' - '.$row_orgform->bezeichnung).'</OPTION>';
}
echo '
</select>
<select name="oe_kurzbz">
<option value="">-- '.$p->t('global/institut').' --</option>';
$fachbereich = new fachbereich();
$fachbereich->loadArray($fachbereich_arr, 'bezeichnung');
foreach($fachbereich->result as $row_fb)
{
	if($oe_kurzbz==$row_fb->oe_kurzbz)
		$selected = 'selected="selected"';
	else
		$selected='';

	echo '<option value="'.$row_fb->oe_kurzbz.'" '.$selected.'>'.$db->convert_html_chars($row_fb->bezeichnung).'</option>';
}
echo '
</select>
<input type="submit" value="'.$p->t('global/anzeigen').'">
</form>';

$lv = new lehrveranstaltung();
if(!$lv->load_lva(	($studiengang_kz != ''?$studiengang_kz:null),
					($semester != ''?$semester:null),
					null,true,true,null,
					($oe_kurzbz != ''?$oe_kurzbz:null),
					null,
					($orgform_kurzbz != ''?$orgform_kurzbz:null)))
	die($lv->errormsg);

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

foreach($lv->lehrveranstaltungen as $row_lv)
{
	$tablerow = '';

	$stg = new studiengang();
	$stg->load($row_lv->studiengang_kz);

	$lva = new lehrveranstaltung();
	$lva->load($row_lv->lehrveranstaltung_id);
	$oes = $lva->getAllOe();
	$oes[]=$lva->oe_kurzbz; // Institut
	$oes[]=$stg->oe_kurzbz; // OE des Studiengangs der Lehrveranstaltung
	$oes = array_unique($oes);
	$allowed_to_show_lv=true;

	if(!$rechte->isBerechtigtMultipleOe('addon/lvevaluierung',$oes,'s'))
		$allowed_to_show_lv=false;

	$tablerow.='
	<tr>
	<td>'.$db->convert_html_chars($stg->kuerzel.' '.$row_lv->semester.' '.$row_lv->bezeichnung.' '.$row_lv->orgform_kurzbz).'</td>';
	$lvoffered_gesamt=false;
	foreach($stsem_rev as $row_stsem)
	{
		$lv_offered = new lehrveranstaltung();
		$lvoffered = $lv_offered->isOffered($row_lv->lehrveranstaltung_id,  $row_stsem->studiensemester_kurzbz);

		if($lvoffered)
			$lvoffered_gesamt=true;

		if(!isset($arr_lvoffered[$row_stsem->studiensemester_kurzbz]['gesamt']))
		{
			$arr_lvoffered[$row_stsem->studiensemester_kurzbz]['gesamt']=0;
			$arr_lvoffered[$row_stsem->studiensemester_kurzbz]['verpflichtend']=0;
		}

		if($lvoffered)
		{
			$arr_lvoffered[$row_stsem->studiensemester_kurzbz]['gesamt']++;
		}

		$tablerow.= '<td class="'.($lvoffered?'offered':'notoffered').'">';
		$evaluierung = new lvevaluierung();

		if($evaluierung->getEvaluierung($row_lv->lehrveranstaltung_id, $row_stsem->studiensemester_kurzbz))
		{
			$tablerow.=printVerpflichtend($evaluierung->verpflichtend, $row_lv->lehrveranstaltung_id, $row_stsem->studiensemester_kurzbz);
			if($evaluierung->verpflichtend)
				$arr_lvoffered[$row_stsem->studiensemester_kurzbz]['verpflichtend']++;
			if(!$allowed_to_show_lv)
			{
				$tablerow.= 'X';
				continue;
			}

			$codes = new lvevaluierung_code();

			// Wenn die Startzeit nicht gesetzt ist, dann ueberspringen da die Evaluierung noch nicht stattgefunden hat
			if($evaluierung->startzeit=='')
				continue;

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

			$tablerow.= '&nbsp;&nbsp;&nbsp;<a href="#" onclick="javascript:window.open(\'auswertung.php?lvevaluierung_id='.$evaluierung->lvevaluierung_id.'\',\'Auswertung\',\'width=700,height=750,resizable=yes,menuebar=no,toolbar=no,status=yes,scrollbars=yes\');return false;">'.number_format($prozent_abgeschlossen,2).'</a>';
			//$tablerow.= '<a href="auswertung.php?lvevaluierung_id='.$evaluierung->lvevaluierung_id.'" title="'.$p->t('lvevaluierung/ruecklaufquoteDetailauswertung').'">'.number_format($prozent_abgeschlossen,2).'</a>';

			$sev = new lvevaluierung_selbstevaluierung();
			if($sev->getSelbstevaluierung($evaluierung->lvevaluierung_id))
				$tablerow.= '&nbsp;&nbsp;&nbsp;<a href="#" onclick="javascript:window.open(\'selbstevaluierung.php?lvevaluierung_id='.$evaluierung->lvevaluierung_id.'\',\'Selbstevaluierung\',\'width=700,height=750,resizable=yes,menuebar=no,toolbar=no,status=yes,scrollbars=yes\');return false;"><img src="../../../skin/images/edit-paste.png" height="15px" title="'.$p->t('lvevaluierung/selbstevaluierungAnzeigen').'"/></a>';
		}
		else
		{
			if($lvoffered)
				$tablerow.=printVerpflichtend(false, $row_lv->lehrveranstaltung_id, $row_stsem->studiensemester_kurzbz);
		}
		$tablerow.= '</td>';

	}
	$tablerow.= '</tr>';

	// Wenn die Lehrveranstaltung in keinem der Studiensemester
	// angeboten wurde, dann wird diese auch nicht angezeigt
	if($lvoffered_gesamt)
		echo $tablerow;
}
echo '</tbody>
<tfoot>
<tr>
	<th>'.$p->t('lvevaluierung/verpflichtendeEvaluierungen').'</th>';

foreach($stsem_rev as $row_stsem)
{
	echo '<th>';
	if(isset($arr_lvoffered[$row_stsem->studiensemester_kurzbz]))
	{
		echo '<span id="sum_'.$row_stsem->studiensemester_kurzbz.'">'.
				$arr_lvoffered[$row_stsem->studiensemester_kurzbz]['verpflichtend'].'
			</span> / '.$arr_lvoffered[$row_stsem->studiensemester_kurzbz]['gesamt'];
	}
	echo '</th>';
}
echo '</tr></tfoot></table>';

echo $p->t('lvevaluierung/legende').'
<table>
<tr>
	<td class="offered">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
	<td>'.$p->t('lvevaluierung/legendeAngebot').'</td>
</tr>
<tr>
	<td><img src="../../../skin/images/edit-paste.png" height="15px"></td>
	<td>'.$p->t('lvevaluierung/selbstevaluierung').'</td>
</tr>
<tr>
	<td><img src="../skin/images/emblem-person-green.png" height="15px"></td>
	<td>'.$p->t('lvevaluierung/legendenichtverpflichtend').'</td>
</tr>
<tr>
	<td><img src="../skin/images/emblem-person-red.png" height="15px"></td>
	<td>'.$p->t('lvevaluierung/legendeverpflichtend').'</td>
</tr>
</table>
';
echo '
</body>
</html>';

function printVerpflichtend($verpflichtend, $lehrveranstaltung_id, $studiensemester_kurzbz)
{
	global $p;
	if($verpflichtend)
		$title = $p->t('lvevaluierung/verpflichtend');
	else
		$title = $p->t('lvevaluierung/freiwillig');

	return '
	<a href="#change" onclick="changeState(\''.$lehrveranstaltung_id.'\',\''.$studiensemester_kurzbz.'\')">
		<img src="../skin/images/'.($verpflichtend?'emblem-person-red.png':'emblem-person-green.png').'"'.
		' height="20px" '.
		' id="img_eval_verpflichtend_'.$lehrveranstaltung_id.'_'.$studiensemester_kurzbz.'" '.
		' title="'.$title.'"'.
		' data-verpflichtend="'.($verpflichtend?'true':'false').'"/>
	</a>';
}
?>
