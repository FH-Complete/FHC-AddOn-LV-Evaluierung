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
 * Authors: Cristina Hainberger <hainberg@technikum-wien.at>
 * 
 * Description: This file creates a lehrveranstaltungsevaluierungs-jahresabschlussbericht 
 * by a given lvevaluierung_jahresabschluss_id.
 * standard output format is pdf.
 * 
 */

require_once('../../../config/cis.config.inc.php');
require_once('../../../include/dokument_export.class.php');
require_once('../../../include/lehrveranstaltung.class.php');
require_once('../../../include/phrasen.class.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/functions.inc.php');
require_once('../include/lvevaluierung_jahresabschluss.class.php');

$uid = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);

$sprache = getSprache();
$p = new phrasen($sprache);

$db = new basis_db();

//check permissions
$isStgl = $rechte->isBerechtigt('addon/lvevaluierung');
$isRektor = $rechte->isBerechtigt('addon/lvevaluierung_rektorat');

if(!$isStgl && !$isRektor)
    die($p->t('global/keineBerechtigungFuerDieseSeite'));

if (!$db = new basis_db())
    die('Es konnte keine Verbindung zum Server aufgebaut werden.');


//Studienjahrabschlussbericht-ID
$lvevaluierung_jahresabschluss_id = (isset($_GET['lvev_jahresabschluss_id'])) ?  $_GET['lvev_jahresabschluss_id'] : '';
if (!is_numeric($_GET['lvev_jahresabschluss_id']))
    die ($p->t('global/fehlerBeiDerParameteruebergabe'));

//Output format
$output = (isset($_GET['output']) && ($output = 'odt' || $output = 'doc')) ? $output = $_GET['output'] : 'pdf';



if ($sprache == 'English')
{
	$doc = new dokument_export('LvEvaluierungAbschlussberichtEng');
}
else
{
	$doc = new dokument_export('LvEvaluierungAbschlussbericht');
}

//Studienabschlussbericht vars
list(
    $oe_kurzbz,                         
    $studienjahr_kurzbz, 
    $ergebnisse,
    $verbesserungen) = 
    getStudienabschlussbericht($lvevaluierung_jahresabschluss_id);

//Studiengangkennzeichen und -bezeichnung
$stg = new studiengang();
$stg->getStudiengangFromOe($oe_kurzbz);
$studiengang_kz = $stg->studiengang_kz;
$stg_bezeichnung = $stg->bezeichnung;
$stg_bezeichnung_eng = $stg->english;

//Wintersemester / Sommersemester
$ws = 'WS'. substr($studienjahr_kurzbz, 0, 4);                               
$ss = 'SS'. strval(substr($studienjahr_kurzbz, 0, 4) + 1);   

list(
    $selbstev_arr,                  //all selbstevaluierungen per studiengang and studienjahr
    $selbstev_cnt,					//sums up number of selbstevaluierungen per studiengang and studienjahr
	$orgform_container) =           //all evaluierte lvs blocked by organisationsform    
    getEvaluierteLV($studiengang_kz, $ws, $ss);

list (
    $lv_cnt,                        //sums up number of lv per studiengang and studienjahr
    $ev_quote,                      //calculates evaluation quota
    $ev_quoten_txt) =               //text for evaluation quota
    getMainInfo($studiengang_kz, $ws, $ss, $selbstev_cnt);


// ***************************************     FUNCTIONS  -->
//get studienabschlussbericht
function getStudienabschlussbericht($lvevaluierung_jahresabschluss_id)
{   
    global $db;
    $oe_kurzbz = $studienjahr_kurzbz = $ergebnisse = $verbesserungen = '';

    $jahresabschluss = new lvevaluierung_jahresabschluss();
    $jahresabschluss->load($lvevaluierung_jahresabschluss_id);
    $oe_kurzbz = $jahresabschluss->oe_kurzbz;
    $studienjahr_kurzbz = $jahresabschluss->studienjahr_kurzbz;
    $ergebnisse = $jahresabschluss->ergebnisse;
    $verbesserungen = $jahresabschluss->verbesserungen;
    
    return array($oe_kurzbz, $studienjahr_kurzbz, $ergebnisse, $verbesserungen);
}
//get data for table with main data
function getMainInfo($studiengang_kz, $ws, $ss, $selbstev_cnt)
{  
    global $sprache;
    $lv_cnt =  0;
    $ev_quote = 0;
    $ev_quoten_txt = '';      
    $lv = new lehrveranstaltung(); 
    
    $lv->load_lva($studiengang_kz);

    //count offered lv
    foreach($lv->lehrveranstaltungen as $lv)
    {
        if($lv->isOffered($lv->lehrveranstaltung_id, $ws) || $lv->isOffered($lv->lehrveranstaltung_id, $ss))
            $lv_cnt++;       
    }

    //calculate evaluation quota
    if($lv_cnt != 0)
    {
        $ev_quote = round($selbstev_cnt/$lv_cnt*100) . '%';
        
        if ($sprache == 'English'){
            if ($ev_quote != 0)                
            {
                $ev_quoten_txt = 'Evaluation quote is ' . $ev_quote;
            }
            else
            {
                $ev_quoten_txt = 'There are no course evaluations this academic year.';
            }
        }
        else
        {
            if ($ev_quote != 0)                
            {
                $ev_quoten_txt = 'Die Evaluationsquote beträgt ' . $ev_quote;
            }
            else
            {
                $ev_quoten_txt = 'In diesem Jahr gibt es noch keine LV-Evaluationen.';
            }
        }
        
        
       
//        ($ev_quote != 0) ? $ev_quoten_txt = 'Die Evaluationsquote beträgt ' . $ev_quote : $ev_quoten_txt = 'In diesem Jahr gibt es noch keine LV-Evaluationen.';
    }
    else
       $ev_quoten_txt = '-'; 
    
    return array($lv_cnt, $ev_quote, $ev_quoten_txt);
}
//gets data for table with evaluierte lehrveranstaltungen
function getEvaluierteLV($studiengang_kz, $ws, $ss)
{
    global $db;
    $selbstev_arr = array(); 
    $selbstev_cnt = 0;
    
    //get all selbstevaluierungen per studiengang and studienjahr
    $qry = 'SELECT lv.bezeichnung, lv.orgform_kurzbz
            FROM lehre.tbl_lehrveranstaltung lv
            JOIN addon.tbl_lvevaluierung lvev USING (lehrveranstaltung_id)
            JOIN addon.tbl_lvevaluierung_selbstevaluierung lvsev USING (lvevaluierung_id)
            WHERE lv.studiengang_kz = ' . $db->db_add_param($studiengang_kz, FHC_INTEGER) . '
            AND (lvev.studiensemester_kurzbz = ' . $db->db_add_param($ws, FHC_STRING) . ' OR lvev.studiensemester_kurzbz = ' . $db->db_add_param($ss, FHC_STRING) . ')
            AND lvsev.freigegeben = true
            ORDER BY lv.bezeichnung';

    //count / set data for all selbstevaluierungen per studiengang and studienjahr
    if ($result = $db->db_query($qry)) 
        {
        while ($row = $db->db_fetch_object($result)) 
            {
            $selbstev_arr['bezeichnung'][] = $row->bezeichnung;
            $selbstev_arr['orgform_kurzbz'][] = $row->orgform_kurzbz;
            $selbstev_cnt++;
            }
        }
		
		$orgform_container = array();
		if (isset($selbstev_arr['orgform_kurzbz']))
		{
			$orgform_unique_arr = array_unique($selbstev_arr['orgform_kurzbz']);

			if (count($orgform_unique_arr) > 0)
			{
				sort($orgform_unique_arr);

				foreach($orgform_unique_arr as $orgform){
					for ($i = 0; $i < $selbstev_cnt; $i++){
						if ($selbstev_arr['orgform_kurzbz'][$i] == $orgform)
						{	
							$orgform_container[$orgform][] = array('lv' => $selbstev_arr['bezeichnung'][$i]); 

						}
					}
				}
			}
		}
    return array($selbstev_arr, $selbstev_cnt, $orgform_container);    
    }

       
// ***************************************     PDF-EXPORT  -->   
//set jahresabschlussbericht-data for xsl
$data = array(
    'studiengang_lang' => $stg_bezeichnung,
    'studiengang_lang_eng' => $stg_bezeichnung_eng,
    'studiengang_kurz' => strtoupper($oe_kurzbz),
    'studienjahr' => $studienjahr_kurzbz,
    'wintersemester' => $ws,
    'sommersemester' => $ss,
    'anzahl_lvs' => $lv_cnt,
    'anzahl_evaluierte_lvs' => $selbstev_cnt,
    'evaluationsquote' => $ev_quoten_txt,
    'ergebnisse' => $ergebnisse,
    'verbesserungen' => $verbesserungen
);

if(count($orgform_container) > 0)
{
	foreach($orgform_container as $key => $value)
	{
		$data[]= array(
			'evaluierte_lehrveranstaltungen' => array(
			'org_form' => ' - ' . $key,
			'evaluierte_lvs' => $value      
			)
		);
	}
}
else 
	{
		$data[]= array(
			'evaluierte_lehrveranstaltungen' => array(
			'org_form' => '',
			'evaluierte_lvs' => ''     
			)
		);
	}

//add data to lvEvaluierungAbschlussbericht.xsl
$doc->addDataArray($data, 'abschlussbericht');

////set doc name
if ($sprache == 'English')
{
	$doc->setFilename('Academic_Year_Final_Report_' . $stg_bezeichnung . '_' . $studienjahr_kurzbz);
}
else
{
    $doc->setFilename('Studienjahrabschlussbericht_' . $stg_bezeichnung . '_' . $studienjahr_kurzbz);
}

//create doc in format required 
if (!$doc->create($output))
    die($doc->errormsg);

//download doc
$doc->output();

//unlink doc from tmp-folder
$doc->close();