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
require_once('../../../include/benutzerfunktion.class.php');
require_once('../../../include/lehrveranstaltung.class.php');
require_once('../../../include/benutzer.class.php');
require_once('../include/lvevaluierung.class.php');
require_once('../include/lvevaluierung_selbstevaluierung.class.php');
require_once('../../../include/studiensemester.class.php');
require_once('../../../include/lehrmodus.class.php');
require_once('../include/lvguihelper.class.php');

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
		<link rel="stylesheet" href="../../../vendor/fgelinas/timepicker/jquery.ui.timepicker.css" type="text/css"/>
		<link rel="stylesheet" type="text/css" ../../../vendor/components/jqueryui/themes/base/jquery-ui.min.css"/>
		<link href="../../../skin/jquery-ui-1.9.2.custom.min.css" rel="stylesheet"  type="text/css">
		<script type="text/javascript" src="../../../vendor/jquery/jquery1/jquery-1.12.4.min.js"></script>
		<script type="text/javascript" src="../../../vendor/christianbach/tablesorter/jquery.tablesorter.min.js"></script>
		<script type="text/javascript" src="../../../vendor/components/jqueryui/jquery-ui.min.js"></script>
		<script src="../../../vendor/fgelinas/timepicker/jquery.ui.timepicker.js" type="text/javascript" ></script>
		<script type="text/javascript" src="../../../include/js/jquery.ui.datepicker.translation.js"></script>
		<style>
		#foo {
			margin: 10px;
		}
		</style>
	</head>
<body><div id="foo">
';
echo '<h1>'.$p->t('lvevaluierung/selbstevaluierung').'</h1>';

// Berechtigungen pruefen
if(!$rechte->isBerechtigt('addon/lvevaluierung'))
{
	die($p->t('global/keineBerechtigungFuerDieseSeite').'. '.$rechte->errormsg);
}

$lvevaluierung_id = filter_input(INPUT_GET,'lvevaluierung_id');

$sev = new lvevaluierung_selbstevaluierung();
if(!$sev->getSelbstevaluierung($lvevaluierung_id))
	die($sev->errormsg);

$lvevaluierung = new lvevaluierung();
$lvevaluierung->load($lvevaluierung_id);

$lehrveranstaltung_id=$lvevaluierung->lehrveranstaltung_id;
$studiensemester_kurzbz = $lvevaluierung->studiensemester_kurzbz;

// Details Anzeigen
$lv = new lehrveranstaltung();
$lv->load($lehrveranstaltung_id);

$stg = new studiengang();
$stg->getAllTypes();
$stg->load($lv->studiengang_kz);

$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);

$oes = $lv->getAllOe();
$oes[]=$lv->oe_kurzbz; // Institut
$oes[]=$stg->oe_kurzbz; // OE des Studiengangs der Lehrveranstaltung
if(!$rechte->isBerechtigtMultipleOe('addon/lvevaluierung',$oes,'s'))
{
	die($p->t('global/keineBerechtigungFuerDieseSeite'));
}

$cssclass = 'tablesorter';
echo LvGuiHelper::formatAsEvalTable($lv, $stg, $p, $db, $lvevaluierung, $sprache, $cssclass);

echo '<br><br><b>'.$p->t('lvevaluierung/selbstevaluierungGruppe').'</b><br /><div class="textantwort">'.nl2br($db->convert_html_chars($sev->gruppe)).'</div>';
echo '<br><br><b>'.$p->t('lvevaluierung/selbstevaluierungPersoenlich').'</b><br /><div class="textantwort">'.nl2br($db->convert_html_chars($sev->persoenlich)).'</div>';
echo '<br><br><b>'.$p->t('lvevaluierung/selbstevaluierungGeplanteEntwicklung').'</b><br /><div class="textantwort">'.nl2br($db->convert_html_chars($sev->entwicklung)).'</div>';
echo '<br><br><b>'.$p->t('lvevaluierung/selbstevaluierungWeiterbildung').'</b><br /><div class="textantwort">'.nl2br($sev->weiterbildung_bedarf === true?$p->t('global/ja'):($sev->weiterbildung_bedarf === false?$p->t('global/nein'):'')).'</div>';
if($sev->weiterbildung_bedarf || !empty($sev->weiterbildung))
	echo '<br><br><b>'.$p->t('lvevaluierung/selbstevaluierungWeiterbildungArt').'</b><br /><div class="textantwort">'.nl2br($db->convert_html_chars($sev->weiterbildung)).'</div>';

echo '</div>
</body>
</html>';
?>
