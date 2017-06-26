<?php
/* Copyright (C) 2017 fhcomplete.org
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
 * check
 * Authors: Andreas Österreicher <andreas.oesterreicher@technikum-wien.at>
 */
require_once('../../../config/cis.config.inc.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/phrasen.class.php');
require_once('../include/lvevaluierung.class.php');

$uid = get_uid();

$sprache = getSprache();
$p = new phrasen($sprache);

if(isset($_GET['lehrveranstaltung_id']) && is_numeric($_GET['lehrveranstaltung_id']))
	$lehrveranstaltung_id = $_GET['lehrveranstaltung_id'];
else
	die('lehrveranstaltung_id ist ungültig');

if(isset($_GET['studiensemester_kurzbz']))
	$studiensemester_kurzbz = $_GET['studiensemester_kurzbz'];
else
	die('Studiensemester ist ungültig');

$evaluierung = new lvevaluierung();
$evaluierung->getEvaluierung($lehrveranstaltung_id, $studiensemester_kurzbz);

if($evaluierung->verpflichtend)
{
	if(check_lektor($uid))
		$link = APP_ROOT.'cms/content.php?content_id='.$p->t('dms_link/lvevaluierungMitarbeiterCMS');
	else
		$link = APP_ROOT.'cms/content.php?content_id='.$p->t('dms_link/lvevaluierungStudierendeCMS');

	echo json_encode(
		array('message'=>'
		<span style="color:red">'.
		$p->t('lvevaluierung/lvzurEvaluierungAusgewaehlt',array($link)).'
		</span>')
	);
}
else
{
	echo json_encode(array('message'=>''));
}
?>
