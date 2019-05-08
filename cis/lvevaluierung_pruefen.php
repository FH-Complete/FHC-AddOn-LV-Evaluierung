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
 * Authors: Cristina Hainberger <hainberg@technikum-wien.at>
 */

require_once('../../../config/cis.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/phrasen.class.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/lehrveranstaltung.class.php');
require_once('../../../include/studiengang.class.php');
require_once('../../../include/studienjahr.class.php');
require_once('../../../include/studiensemester.class.php');
require_once('../../../include/benutzer.class.php');
require_once('../../../include/datum.class.php');
require_once('../../../include/mail.class.php');
require_once('../include/lvevaluierung_jahresabschluss.class.php');
require_once('../include/lvevaluierung.class.php');
require_once('../include/lvevaluierung_code.class.php');

$uid = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);

$sprache = getSprache();
$p = new phrasen($sprache);

$db = new basis_db();

//check permissions
$isRektor = $rechte->isBerechtigt('addon/lvevaluierung_rektorat');

if(!$isRektor)
	die($p->t('global/keineBerechtigungFuerDieseSeite'));

$studienjahr_aktuell = date('Y') . '/' . (date('y') + 1) ;

//Studiengang
$studiengang_kz = (isset($_POST['studiengang_kz'])) ? $_POST['studiengang_kz'] : '';
//Studienjahr
(isset($_REQUEST['studienjahr_kurzbz']) && !empty($_REQUEST['studienjahr_kurzbz']))
	? $studienjahr_kurzbz = $_REQUEST['studienjahr_kurzbz'] : $studienjahr_kurzbz = $studienjahr_aktuell;

if (!empty($_POST['studiengang_kz']) && !is_numeric($_POST['studiengang_kz']))
	die ($p->t('global/fehlerBeiDerParameteruebergabe'));
//Semester
$semester = (isset($_POST['semester'])) ? $_POST['semester'] : '';
if (!empty($_POST['semester']) && !is_numeric($_POST['semester']))
	die ($p->t('global/fehlerBeiDerParameteruebergabe'));

//get Oes for which user is berechtigt
$oes_berechtigt = $rechte->getOEkurzbz('addon/lvevaluierung_rektorat');

//Wintersemester / Sommersemester
$studiensemester = new studiensemester();
$studiensemester->getWSFromStudienjahr($studienjahr_kurzbz);
$ws = $studiensemester->result;
$studiensemester->getSSFromStudienjahr($studienjahr_kurzbz);
$ss = $studiensemester->result;
//all evaluierte lvs where lector is studiengangsleiter
$selbstev_arr = getEvaluierteLV_whereLectorIsStgl($ws, $ss);
$display_whenFilterNoResult = (count($selbstev_arr) != 0) ? 'style = "display: none;"' : '';

// ***************************************	 FUNCTIONS  -->

//dropdown studiengang
function printOptions_stg()
{
	global $rechte, $oes_berechtigt, $p;
	$studiengang_kz = (isset($_POST['studiengang_kz'])) ? $_POST['studiengang_kz'] : '';
	if (!empty($_POST['studiengang_kz']) && !is_numeric($_POST['studiengang_kz']))
		die ($p->t('global/fehlerBeiDerParameteruebergabe'));

	$stg_arr = $rechte->getStgKz('addon/lvevaluierung');
	$studiengang = new studiengang();
	$studiengang->loadArray($stg_arr,'typ, kurzbz');

	$types = new studiengang();
	$types->getAllTypes();
	$typ = '';

	foreach($studiengang->result as $row)
	{
		foreach ($oes_berechtigt as $oe)
		{
			if ($oe === $row->oe_kurzbz)
			{
				if ($typ != $row->typ || $typ == '')
				{
					if ($typ != '')
						echo '</optgroup>';

					echo '<optgroup label = "'.($types->studiengang_typ_arr[$row->typ] != '' ? $types->studiengang_typ_arr[$row->typ] : $row->typ).'">';
				}
				echo '<option value="'.$row->studiengang_kz.'"'.($studiengang_kz == $row->studiengang_kz ? 'selected' : '').'>'.$row->kuerzel.' - '.$row->bezeichnung.'</option>';
				$typ = $row->typ;

				break;
			}
		}
	}
}
//dropdown studienjahr
function printOptions_stj()
{
	global $p;
	$studienjahr_kurzbz = (isset($_POST['studienjahr_kurzbz'])) ? $_POST['studienjahr_kurzbz'] : '';
	$studienjahr = new studienjahr();
	$studienjahr->getAll();

	foreach (array_reverse($studienjahr->result) as $row)
	{
		$selected = ($studienjahr_kurzbz == $row->studienjahr_kurzbz) ? "selected" : '';
		echo '<option value="'.$row->studienjahr_kurzbz.'" '.$selected.'>'.$row->studienjahr_kurzbz.'</option>';
	}
}
//dropdown semester
function printOptions_sem()
{
	global $p;
	$semester = (isset($_POST['semester'])) ? $_POST['semester'] : '';
	if (!empty($_POST['semester']) && !is_numeric($_POST['semester']))
		die ($p->t('global/fehlerBeiDerParameteruebergabe'));

	for($i=1; $i<=10; $i++)
	{
		$selected = ($semester == $i) ? "selected" : '';
		echo '<option value="'. $i .'" '.$selected.'>'. $i .'</option>';
	}
}
function getEvaluierteLV_whereLectorIsStgl($ws, $ss)
{
	global $db, $oes_berechtigt;
	$studiengang_kz = (isset($_POST['studiengang_kz'])) ? $_POST['studiengang_kz'] : '';
	$semester = (isset($_POST['semester'])) ? $_POST['semester'] : '';
	if (!empty($_POST['semester']) && !is_numeric($_POST['semester']))
		die ($p->t('global/fehlerBeiDerParameteruebergabe'));
	$selbstev_arr = array();
	$studiengang = new studiengang();
	$stgl_arr = $studiengang->getLeitung();
	$person = new person();

	//get all selbstevaluierungen per studiengang and studienjahr
	$qry = 'SELECT lv.bezeichnung, lv.orgform_kurzbz, lvsev.uid, lvev.lvevaluierung_id, lv.lehrveranstaltung_id
			FROM lehre.tbl_lehrveranstaltung lv
			JOIN addon.tbl_lvevaluierung lvev USING (lehrveranstaltung_id)
			JOIN addon.tbl_lvevaluierung_selbstevaluierung lvsev USING (lvevaluierung_id)
			JOIN public.tbl_studiengang USING (studiengang_kz)
			WHERE (lvev.studiensemester_kurzbz = ' . $db->db_add_param($ws, FHC_STRING) . ' OR lvev.studiensemester_kurzbz = ' . $db->db_add_param($ss, FHC_STRING) . ')
			AND tbl_studiengang.oe_kurzbz IN ('.$db->db_implode4SQL($oes_berechtigt).')';

	if (!empty($studiengang_kz))
		$qry.=' AND lv.studiengang_kz = ' . $db->db_add_param($studiengang_kz, FHC_STRING);

	if (!empty($semester))
		$qry.=' AND lv.semester = ' . $db->db_add_param($semester, FHC_INTEGER);

	$qry.= 'AND lvsev.freigegeben = true
			ORDER BY lv.bezeichnung';

	//count / set data for all selbstevaluierungen per studiengang and studienjahr
	if ($result = $db->db_query($qry))
	{
		while ($row = $db->db_fetch_object($result))
		{
			//check id lektor is stgl
			if (in_array($row->uid, $stgl_arr))
			{
				$person->getPersonFromBenutzer($row->uid);

				 $selbstev_arr[] = array('bezeichnung' => $row->bezeichnung,
										'orgform_kurzbz' => $row->orgform_kurzbz,
										'lektorIsStgl' => $person->anrede . ' ' . $person->vorname . ' ' . $person->nachname,
										'lvevaluierung_id' => $row->lvevaluierung_id,
										'lehrveranstaltung_id' => $row->lehrveranstaltung_id);
			}
		}
	}
	return $selbstev_arr;
}
function getRuecklaufquote($lvevaluierung_id, $lehrveranstaltung_id, $ws, $ss)
{
	$evaluierung = new lvevaluierung();
	$codes = new lvevaluierung_code();

	if($evaluierung->getEvaluierung($lehrveranstaltung_id, $ws) || $evaluierung->getEvaluierung($lehrveranstaltung_id, $ss))
	{
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
	}

	return '<span>(' . sprintf("%6s", number_format($prozent_abgeschlossen, 2)) . '%)</span>';
}
?>

<html>
	<head>
		<title><?php echo $p->t('lvevaluierung/lvevaluierungJahresabschlussbericht') ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<link href="../../../skin/fhcomplete.css" rel="stylesheet" type="text/css">
		<link href="../../../skin/style.css.php" rel="stylesheet" type="text/css">
		<link href="../skin/lvevaluierung.css" rel="stylesheet" type="text/css">
		<link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css">
		<link href="../../../skin/jquery-ui-1.9.2.custom.min.css" rel="stylesheet"  type="text/css">
		<script type="text/javascript" src="../../../vendor/jquery/jqueryV1/jquery-1.12.4.min.js"></script>
		<script type="text/javascript" src="../../../vendor/christianbach/tablesorter/jquery.tablesorter.min.js"></script>
		<script type="text/javascript" src="../../../vendor/components/jqueryui/jquery-ui.min.js"></script>
	</head>

	<body class="main">

		<h1><?php echo $p->t('lvevaluierung/evaluierungenPruefen') ?></h1>

		<form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']);?>">

<!-- ***************************************	dropdowns   -->
			<select name="studiengang_kz" style="width: 20%;">
				<option value=""><?php echo '--' . $p->t('global/studiengang') . '--' ?></option>
				<?php printOptions_stg(); ?>
			</select><span>&emsp;&emsp;&emsp;</span>

			<select name="studienjahr_kurzbz" style="width: 20%;">
				<option value=""><?php echo '--' . $p->t('global/studienjahr') . '--' ?></option>
				<?php printOptions_stj(); ?>
			</select><span>&emsp;&emsp;&emsp;</span>

			<select name="semester" style="width: 20%;">
				<option value=""><?php echo '--' . $p->t('global/semester') . '--' ?></option>
				<?php printOptions_sem(); ?>
			</select>

			<input type="submit" value="<?php echo $p->t('global/anzeigen') ?>">
		</form><p></p>

<!-- ***************************************	table studienabschlussberichte  -->
		<table class="table" width ="100%">
			<tbody>
				<tr>
					<th><?php echo $p->t('lvevaluierung/evaluierteLVs') ?></th>
					<th><?php echo $p->t('lvevaluierung/lektorIstStgl') ?></th>
					<th><?php echo $p->t('lvevaluierung/selbstevaluierung') ?></th>
					<th><?php echo $p->t('lvevaluierung/auswertung') ?><small> (<?php echo $p->t('lvevaluierung/ruecklaufquote') ?> in %)</small></th>
				</tr>
				<?php
				foreach($selbstev_arr as $selbstev)
				{
					echo '
					<tr>
					<td>' . $selbstev['bezeichnung'] . ', ' .$selbstev['orgform_kurzbz'] . '</td>
					<td>' . $selbstev['lektorIsStgl'] .'</td>
					<td style="text-align: center;">
						<a href="#" onclick="javascript:window.open(\'selbstevaluierung.php?lvevaluierung_id=' . $selbstev['lvevaluierung_id'] . '\',\'Selbstevaluierung\',
						\'width=700,height=750,resizable=yes,menuebar=no,toolbar=no,status=yes,scrollbars=yes\');return false;">
						<img src="../../../skin/images/edit-paste.png" height="15px" title="Selbstevaluierung anzeigen"></a>
					</td>
					<td style="text-align: center;">
					<a href="#" onclick="javascript:window.open(\'auswertung.php?lvevaluierung_id=' . $selbstev['lvevaluierung_id'] . '\',\'Auswertung\',
						\'width=700,height=750,resizable=yes,menuebar=no,toolbar=no,status=yes,scrollbars=yes\');return false;"><img src="../../../skin/images/statistic.png" height="15px" title="Auswertung anzeigen"></a>					
					</a>' . getRuecklaufquote($selbstev['lvevaluierung_id'], $selbstev['lehrveranstaltung_id'], $ws, $ss) . '<td>
					</tr>';
				}
				?>
			</tbody>
		</table>


<!-- ***************************************	panel info no values found (only if dropdown filter results in no values)  -->
		<div class="lvepanel lvepanel-body" <?php echo $display_whenFilterNoResult ?>>
			<?php echo $p->t('global/keineSuchergebnisse') ?>
		</div>


	</body>
</html>
