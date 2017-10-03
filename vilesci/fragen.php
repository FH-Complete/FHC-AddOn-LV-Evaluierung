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
require_once('../../../config/vilesci.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../include/lvevaluierung_frage.class.php');

echo '<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" href="../../../skin/fhcomplete.css" type="text/css">
	<link rel="stylesheet" href="../../../skin/vilesci.css" type="text/css">
	<link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css">
	<script type="text/javascript" src="../../../vendor/jquery/jqueryV1/jquery-1.12.4.min.js"></script>
	<script type="text/javascript" src="../../../vendor/christianbach/tablesorter/jquery.tablesorter.min.js"></script>
	<title>LV-Evaluierung</title>
	<script type="text/javascript">
	function deleteFrage(id)
	{
		if(confirm("Wollen Sie diese Frage wirklich löschen"))
		{

			$("#data").html(\'<form action="fragen.php" name="sendform" id="sendform" method="POST"><input type="hidden" name="action" value="deleteFrage" /><input type="hidden" name="id" value="\'+id+\'" /></form>\');
			document.sendform.submit();
		}
		return false;
	}

	function deleteAntwort(frage_id, antwort_id)
	{
		if(confirm("Wollen Sie diese Antwort wirklich löschen"))
		{
			$("#data").html(\'<form action="fragen.php" name="sendform" id="sendform" method="POST"><input type="hidden" name="action" value="deleteAntwort" /><input type="hidden" name="antwort_id" value="\'+antwort_id+\'" /><input type="hidden" name="id" value="\'+frage_id+\'" /></form>\');
			document.sendform.submit();
		}
		return false;
	}
	</script>
</head>
<body>

<div id="data" style="display:none"></div>';

$uid = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);

if(!$rechte->isBerechtigt('addon/lvevaluierung', null, 'suid'))
{
	die($rechte->errormsg);
}

$sprache = new sprache();
$sprache->getAll(true);
$db = new basis_db();


if(isset($_REQUEST['action']))
	$action = $_REQUEST['action'];
else
	$action='';

switch($action)
{
	// Loeschen einer Frage
	case 'deleteFrage':
		if(!$rechte->isBerechtigt('addon/lvevaluierung',null,'suid'))
			die($rechte->errormsg);

		$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

		$frage = new lvevaluierung_frage();
		if($frage->delete($id))
			echo '<span class="ok">Frage wurde erfolgreich gelöscht</span>';
		else
			echo '<span class="error">'.$frage->errormsg.'</span>';

		break;
	// Loeschen einer Frage
	case 'deleteAntwort':
		if(!$rechte->isBerechtigt('addon/lvevaluierung',null,'suid'))
			die($rechte->errormsg);

		$antwort_id = filter_input(INPUT_POST, 'antwort_id', FILTER_VALIDATE_INT);

		$frage = new lvevaluierung_frage();
		if($frage->deleteAntwort($antwort_id))
			echo '<span class="ok">Antwort wurde erfolgreich gelöscht</span>';
		else
			echo '<span class="error">'.$frage->errormsg.'</span>';
		$action='details';
		break;

	// Speichern einer Frage
	case 'saveFrage':
		$typ = filter_input(INPUT_POST,'typ');
		$sort = filter_input(INPUT_POST,'sort');
		$lvevaluierung_frage_id = filter_input(INPUT_POST,'lvevaluierung_frage_id');
		$bezeichnung=array();
		foreach($sprache->result as $row_sprache)
		{
			if(isset($_POST['bezeichnung'.$row_sprache->sprache]))
				$bezeichnung[$row_sprache->sprache]=$_POST['bezeichnung'.$row_sprache->sprache];
		}

		$frage = new lvevaluierung_frage();

		if($lvevaluierung_frage_id!='')
		{
			if(!$frage->load($lvevaluierung_frage_id))
				die($frage->errormsg);
		}

		$frage->typ = $typ;
		$frage->sort = $sort;
		$frage->bezeichnung = $bezeichnung;
		$frage->aktiv = isset($_POST['aktiv']);

		if($frage->save())
		{
			if($frage->typ=='singleresponse' && $lvevaluierung_frage_id=='')
			{
				for($i=0;$i<=5;$i++)
				{
					$antwort = new lvevaluierung_frage();
					$antwort->lvevaluierung_frage_id = $frage->lvevaluierung_frage_id;
					$antwort->sort=$i;
					$antwort->wert=$i;
					$antwort->bezeichnung=array();
					switch($i)
					{
						case 0:
							// 0 = keine Angabe
							foreach($sprache->result as $row_sprache)
								$antwort->bezeichnung[$row_sprache->sprache] = 'keine Angabe';
							// keine Angabe soll immer zum schluss stehen
							$antwort->sort=9999;
							break;
						case 1:
							foreach($sprache->result as $row_sprache)
								$antwort->bezeichnung[$row_sprache->sprache] = 'stimme voll und ganz zu';
							break;
						case 5:
							foreach($sprache->result as $row_sprache)
								$antwort->bezeichnung[$row_sprache->sprache] = 'lehne voll und ganz ab';
							break;
					}

					if(!$antwort->saveAntwort())
						echo '<span class="error">'.$antwort->errormsg.'</span>';
				}
			}
			echo '<span class="ok">Frage erfolgreich gespeichert</span>';
		}
		else
			echo '<span class="error">'.$frage->errormsg.'</span>';

		break;

	// Speichern einer Antwort
	case 'saveAntwort':
		$wert = filter_input(INPUT_POST,'wert');
		$sort = filter_input(INPUT_POST,'sort');
		$lvevaluierung_frage_antwort_id = filter_input(INPUT_POST,'lvevaluierung_frage_antwort_id');
		$lvevaluierung_frage_id = filter_input(INPUT_POST,'id');
		$bezeichnung=array();
		foreach($sprache->result as $row_sprache)
		{
			if(isset($_POST['bezeichnung'.$row_sprache->sprache]))
				$bezeichnung[$row_sprache->sprache]=$_POST['bezeichnung'.$row_sprache->sprache];
		}

		$antwort = new lvevaluierung_frage();

		if($lvevaluierung_frage_antwort_id!='')
		{
			if(!$antwort->loadAntwort($lvevaluierung_frage_antwort_id))
				die($antwort->errormsg);
		}

		$antwort->wert = $wert;
		$antwort->sort = $sort;
		$antwort->bezeichnung = $bezeichnung;
		$antwort->lvevaluierung_frage_id = $lvevaluierung_frage_id;

		if($antwort->saveAntwort())
		{
			echo '<span class="ok">Antwort erfolgreich gespeichert</span>';
		}
		else
			echo '<span class="error">'.$antwort->errormsg.'</span>';

		$action='details';
		break;
}

if($action=='details' || $action=='editAntwort')
{
	echo '<h1>Antwortmöglichkeiten</h1>';
	echo '<a href="fragen.php">Zurück zur Fragenübersicht</a><br><br>';
	$id = $_REQUEST['id'];

	$frage = new lvevaluierung_frage();
	if(!$frage->load($id))
		die($frage->errormsg);
	echo 'Frage:'.$frage->bezeichnung[DEFAULT_LANGUAGE];
	$antwort = new lvevaluierung_frage();
	if(!$antwort->loadAntworten($id))
		die('Failed:'.$antwort->errormsg);

	echo '<script>
	$(document).ready(function()
	{
		$("#t2").tablesorter(
		{
			sortList: [[0,0]],
			widgets: ["zebra"]
		});
	});
	</script>
	';

	echo '<form action="fragen.php" method="POST" >';
	echo '<table class="tablesorter" id="t2" style="width:auto;">
			<thead>
			<tr>
				<th>Reihenfolge</th>
				<th>Bezeichnung</th>
				<th>Wert</th>
				<th>Aktion</th>
			</tr>
			</thead>
			<tbody>';
	$maxsort=0;
	$maxwert=0;
	foreach($antwort->result as $row)
	{
		if($maxsort<$row->sort)
			$maxsort=$row->sort;

		if($maxwert<$row->wert)
			$maxwert=$row->wert;

		echo '<tr>';
		echo '<td>'.$row->sort.'</td>';
		echo '<td>'.$db->convert_html_chars($row->bezeichnung[DEFAULT_LANGUAGE]).'</td>';
		echo '<td>'.$row->wert.'</td>';
		echo '<td>
				<a href="#deleteAntwort" onclick="deleteAntwort(\''.$row->lvevaluierung_frage_id.'\',\''.$row->lvevaluierung_frage_antwort_id.'\');return false;"><img src="../../../skin/images/delete.png" height="20px"/></a>
				<a href="fragen.php?action=editAntwort&id='.$row->lvevaluierung_frage_id.'&antwort_id='.$row->lvevaluierung_frage_antwort_id.'"><img src="../../../skin/images/edit.png" height="20px"/></a>
			</td>';
		echo '</tr>';
	}
	echo '</tbody>';


	$antwort = new lvevaluierung_frage();
	if($action=='editAntwort')
	{
		if(!$antwort->loadAntwort($_GET['antwort_id']))
			die($antwort->errormsg);
	}
	else
	{
		$antwort->sort = $maxsort+1;
		$antwort->wert = $maxwert+1;
		$antwort->lvevaluierung_frage_id = $id;
	}

	echo '<tfoot>
		<tr>
			<th valign="top">
				<input type="hidden" name="id" value="'.$antwort->lvevaluierung_frage_id.'" />
				<input type="hidden" name="action" value="saveAntwort" />
				<input type="hidden" name="lvevaluierung_frage_antwort_id" value="'.$antwort->lvevaluierung_frage_antwort_id.'" />
				<input type="text" name="sort" size="3" value="'.$antwort->sort.'" /></th>
	<th valign="top">
	<table>';

	foreach($sprache->result as $s)
	{
	 		echo '<tr><td>'.$s->sprache.'</td><td>';
	 		echo '<textarea cols="50" name="bezeichnung'.$s->sprache.'" >'.(isset($antwort->bezeichnung[$s->sprache])?$db->convert_html_chars($antwort->bezeichnung[$s->sprache]):'').'</textarea></td></tr>';
	}

	echo '</table></th>

			<th valign="top"><input type="text" name="wert" size="1" value="'.$antwort->wert.'" /></th>
			<th valign="top"><input type="submit" name="save" value="Speichern" /></th>
		</tr>';

	echo '</tfoot></table></form>';
}
else
{
	echo '<h1>Fragen - Übersicht</h1>';
	// Fragen anzeigen
	$fragen = new lvevaluierung_frage();
	if(!$fragen->getFragen())
		die($fragen->errormsg);

	echo '<script>
	$(document).ready(function()
	{
		$("#t1").tablesorter(
		{
			sortList: [[0,0]],
			widgets: ["zebra"]
		});
	});
	</script>
	';

	echo '<form action="fragen.php" method="POST">';
	echo '<table class="tablesorter" id="t1">
			<thead>
			<tr>
				<th>Reihenfolge</th>
				<th>Typ</th>
				<th>Bezeichnung</th>
				<th>Aktiv</th>
				<th>Aktion</th>
			</tr>
			</thead>
			<tbody>';

	$maxsort=0;

	foreach($fragen->result as $row)
	{
		if($maxsort<$row->sort)
			$maxsort=$row->sort;
		echo '<tr>';
		echo '<td>'.$db->convert_html_chars($row->sort).'</td>';
		echo '<td>'.$db->convert_html_chars($row->typ).'</td>';
		echo '<td>'.$db->convert_html_chars($row->bezeichnung[DEFAULT_LANGUAGE]).'</td>';
		echo '<td>'.($row->aktiv?'Ja':'Nein').'</td>';
		echo '<td>
				<a href="#deleteFrage" onclick="deleteFrage(\''.$row->lvevaluierung_frage_id.'\');return false;"><img src="../../../skin/images/delete.png" height="20px"/></a>
				<a href="fragen.php?action=editFrage&id='.$row->lvevaluierung_frage_id.'"><img src="../../../skin/images/edit.png" height="20px"/></a>
				<a href="fragen.php?action=details&id='.$row->lvevaluierung_frage_id.'"><img src="../../../skin/images/application_form_edit.png" height="20px"/></a>
			</td>';
		echo '</tr>';
	}

	$frage = new lvevaluierung_frage();
	if($action=='editFrage')
	{

		if(!$frage->load($_GET['id']))
		{
			die($frage->errormsg);
		}
	}
	else
		$frage->sort = ($maxsort+10);

	echo '</tbody>
	<tfoot>
	<tr>
		<th valign="top">
			<input type="hidden" name="action" value="saveFrage" />
			<input type="hidden" name="lvevaluierung_frage_id" value="'.$frage->lvevaluierung_frage_id.'" />
			<input type="text" name="sort" size="3" value="'.$frage->sort.'" />
		</th>
		<th valign="top">
			<select name="typ">
				<option value="text" '.($frage->typ=='text'?'selected="selected"':'').'>Freitext</option>
				<option value="singleresponse" '.($frage->typ=='singleresponse'?'selected="selected"':'').'>SingleResponse</option>
				<option value="label" '.($frage->typ=='label'?'selected="selected"':'').'>Label Titel</option>
				<option value="labelsub" '.($frage->typ=='labelsub'?'selected="selected"':'').'>Label Normaltext</option>
			</select>
		</th>
		<th valign="top">
		<table>';

	foreach($sprache->result as $s)
	{
	 		echo '<tr><td>'.$s->sprache.'</td><td>';
	 		echo '<textarea cols="50" name="bezeichnung'.$s->sprache.'" >'.(isset($frage->bezeichnung[$s->sprache])?$db->convert_html_chars($frage->bezeichnung[$s->sprache]):'').'</textarea></td></tr>';
	}

	echo '</table></th>
	<th valign="top">
		<input type="checkbox" name="aktiv" '.($frage->aktiv?'checked="checked"':'').' />
	</th>
	<th valign="top">
		<input type="submit" name="saveFrage" value="Anlegen" />
	</th>
	</tr>
	</tfoot>
	</table><br><br>
	</form>';

}
?>
