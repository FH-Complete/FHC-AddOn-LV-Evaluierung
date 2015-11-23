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
require_once('../../../include/dokument_export.class.php');
require_once('../../../include/lehrveranstaltung.class.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/lehreinheitmitarbeiter.class.php');
require_once('../include/lvevaluierung.class.php');
require_once('../include/lvevaluierung_code.class.php');
require_once('../vendor/kairos/phpqrcode/qrlib.php');

$uid = get_uid();

if(!isset($_GET['lvevaluierung_id']))
	die('lvevaluierung_id muss uebergeben werden');
if(!is_numeric($_GET['lvevaluierung_id']))
	die('Id ist ungueltig');

$lvevaluierung_id = $_GET['lvevaluierung_id'];

$lvevaluierung = new lvevaluierung();
$lvevaluierung->load($lvevaluierung_id);


// Berechtigungen pruefen
$lem = new lehreinheitmitarbeiter();
if(!$lem->existsLV($lvevaluierung->lehrveranstaltung_id, $lvevaluierung->studiensemester_kurzbz,  $uid))
{
	$rechte = new benutzerberechtigung();
	$rechte->getBerechtigungen($uid);

	if(!$rechte->isBerechtigt('admin'))
	{
		die($p->t('global/keineBerechtigung'));
	}
}

$lv = new lehrveranstaltung();
$lv->load($lvevaluierung->lehrveranstaltung_id);

$codes_obj = new lvevaluierung_code();
if(!$codes_obj->loadCodes($lvevaluierung_id))
	die($codes_obj->errormsg);

$doc = new dokument_export('LVEvalCode');

$url = APP_ROOT.'addons/lvevaluierung/cis/index.php';

$data = array(
	'url'=>$url,
	'bezeichnung'=>$lv->bezeichnung
);

$files=array();

foreach($codes_obj->result as $code)
{
	$filename='/tmp/fhc_lveval_code'.$code->lvevaluierung_code_id.'.png';
	$files[]=$filename;

	// QRCode ertellen und speichern
	QRcode::png($url.'?code='.$code->code, $filename);

	// QRCode zu Dokument hinzufuegen
	$doc->addImage($filename, $code->lvevaluierung_code_id.'.png', 'image/png');
	$data[]=array('code'=>array('lvevaluierung_code_id'=>$code->lvevaluierung_code_id,'code'=>$code->code));


}
$doc->addDataArray($data,'lvevaluierung');
if(!$doc->create('pdf'))
	die($doc->errormsg);
$doc->output();
$doc->close();

// QR Codes aus Temp Ordner entfernen
foreach($files as $file)
	unlink($file);

?>
