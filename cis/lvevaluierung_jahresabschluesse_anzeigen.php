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
require_once('../../../include/benutzer.class.php');
require_once('../../../include/datum.class.php');
require_once('../../../include/mail.class.php');
require_once('../include/lvevaluierung_jahresabschluss.class.php');

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


//Studiengang
$studiengang_kz = (isset($_POST['studiengang_kz'])) ? $_POST['studiengang_kz'] : '';
//Studienjahr
$studienjahr_kurzbz = (isset($_POST['studienjahr_kurzbz'])) ?  $_POST['studienjahr_kurzbz'] : '';

if (!empty($_POST['studiengang_kz']) && !is_numeric($_POST['studiengang_kz']))
    die ($p->t('global/fehlerBeiDerParameteruebergabe'));

//get Studienjahresabschlussberichte
$studienabschlussbericht_arr = getJahresabschlussberichte($studiengang_kz, $studienjahr_kurzbz);
$display_whenFilterNoResult = (count($studienabschlussbericht_arr) > 0) ? 'style = "display: none;"' : ''; 

// ***************************************     FUNCTIONS  -->

//dropdown studiengang
function printOptions_stg()
{
    global $rechte, $p; 
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
        if ($typ != $row->typ || $typ == '')
        {
            if ($typ != '')
                echo '</optgroup>';
                            
            echo '<optgroup label = "'.($types->studiengang_typ_arr[$row->typ] != '' ? $types->studiengang_typ_arr[$row->typ] : $row->typ).'">';
        }
        echo '<option value="'.$row->studiengang_kz.'"'.($studiengang_kz == $row->studiengang_kz ? 'selected' : '').'>'.$row->kuerzel.' - '.$row->bezeichnung.'</option>';
        $typ = $row->typ;
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
//returns all freigegebene jahresabschlussberichte
function getJahresabschlussberichte($studiengang_kz, $studienjahr_kurzbz)
{
    global $db;
    $jahresabschluss = new lvevaluierung_jahresabschluss();
    $jahresabschluss_arr = array();
   
    //dropdown selections
    if (empty($studiengang_kz) && empty($studienjahr_kurzbz))
    {
        $jahresabschluss->getAllJahresabschluesse();
        foreach ($jahresabschluss->result as $obj)
        {
            if($db->db_parse_bool($obj->freigegeben))
            {
                $stg = new studiengang();
                $stg->getStudiengangFromOe($obj->oe_kurzbz);

                $jahresabschluss_arr[] = array(
                                            'lvevaluierung_jahresabschluss_id' => $obj->lvevaluierung_jahresabschluss_id,
                                            'studiengang_kz' => $stg->studiengang_kz,
                                            'studienbezeichnung' => strtoupper($obj->oe_kurzbz) . ' - ' . $stg->bezeichnung,
                                            'studienjahr_kurzbz' => $obj->studienjahr_kurzbz,
                                            'oe_kurzbz' => strtoupper($obj->oe_kurzbz));
            }
        }
    } 
    if (empty($studiengang_kz) && !empty($studienjahr_kurzbz))
    {
        $jahresabschluss->getByStudienjahr($studienjahr_kurzbz);        
        foreach ($jahresabschluss->result as $obj)
        {
            if($db->db_parse_bool($obj->freigegeben))
            {
                $stg = new studiengang();
                $stg->getStudiengangFromOe($obj->oe_kurzbz);

                $jahresabschluss_arr[] = array(
                                            'lvevaluierung_jahresabschluss_id' => $obj->lvevaluierung_jahresabschluss_id,
                                            'studiengang_kz' => $stg->studiengang_kz,
                                            'studienbezeichnung' => strtoupper($obj->oe_kurzbz) . ' - ' . $stg->bezeichnung,
                                            'studienjahr_kurzbz' => $obj->studienjahr_kurzbz,
                                            'oe_kurzbz' => strtoupper($obj->oe_kurzbz));
            }
        }
    }
    if (!empty($studiengang_kz) && !empty($studienjahr_kurzbz) || !empty($studiengang_kz) && empty($studienjahr_kurzbz))
    {
        //Organisationseinheit
        $stg = new studiengang();
        $stg->load($studiengang_kz);
        $oe_kurzbz = $stg->oe_kurzbz; 
       
        $jahresabschluss->getByOeStudienjahr($oe_kurzbz, $studienjahr_kurzbz);
        foreach ($jahresabschluss->result as $obj)
        {
            if($db->db_parse_bool($obj->freigegeben))
            {
                $stg = new studiengang();
                $stg->getStudiengangFromOe($obj->oe_kurzbz);

                $jahresabschluss_arr[] = array(
                                            'lvevaluierung_jahresabschluss_id' => $obj->lvevaluierung_jahresabschluss_id,
                                            'studiengang_kz' => $stg->studiengang_kz,
                                            'studienbezeichnung' => strtoupper($obj->oe_kurzbz) . ' - ' . $stg->bezeichnung,
                                            'studienjahr_kurzbz' => $obj->studienjahr_kurzbz,
                                            'oe_kurzbz' => strtoupper($obj->oe_kurzbz));
            }
        }

    }
      
    return $jahresabschluss_arr;
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
    
    <body class="main" style="padding: 15px;">
        
        <h1><?php echo $p->t('lvevaluierung/jahresabschlussberichtAlle') ?></h1>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']);?>">      

<!-- ***************************************     dropdowns   -->                     
            <select name="studiengang_kz" style="width: 20%;">
                <option value=""><?php echo '--' . $p->t('global/studiengang') . '--' ?></option>
                <?php printOptions_stg(); ?>   
            </select><span>&emsp;<?php echo $p->t('global/und') . "/" . $p->t('global/oder')?>&emsp;</span>                

            <select name="studienjahr_kurzbz" style="width: 20%;">
                <option value=""><?php echo '--' . $p->t('global/studienjahr') . '--' ?></option>
                <?php printOptions_stj(); ?>   
            </select><span>&emsp;&emsp;</span>  

            <input type="submit" value="<?php echo $p->t('global/anzeigen') ?>">
        </form><p></p>
<!-- ***************************************     table studienabschlussberichte  -->
        <table class="table" width ="100%">
            <tbody>
                <tr>
                    <th><b><?php echo $p->t('global/studiengang') ?></b></th>
                    <th><b><?php echo $p->t('global/studienjahr') ?></b></th>
                    <th><b><?php echo $p->t('global/anzeigen') ?></b></th>
                    <th><b><?php echo $p->t('global/pdfExport') ?></b></th>
                </tr>
                <?php
                foreach($studienabschlussbericht_arr as $studienabschlussbericht)
                {
                    echo '
                    <tr>
                    <td>'.$studienabschlussbericht['studienbezeichnung'].'</td>
                    <td>'.$studienabschlussbericht['studienjahr_kurzbz'].'</td>
                    <td><a href = "lvevaluierung_jahresabschluesse_erstellen.php?studiengang_kz=' . $studienabschlussbericht['studiengang_kz'] . '&studienjahr_kurzbz=' . urlencode($studienabschlussbericht['studienjahr_kurzbz']) . '">' . $p->t('global/bericht') . ' ' . $studienabschlussbericht['oe_kurzbz'] . ' - ' . $studienabschlussbericht['studienjahr_kurzbz'] . '</a></td>
                    <td style="text-align: center;"><a href = "lvEvaluierungAbschlussbericht.pdf.php?lvev_jahresabschluss_id=' . $studienabschlussbericht['lvevaluierung_jahresabschluss_id'] . '">
                            <img style="cursor:pointer; height: 16px;" src="../../../skin/images/pdf_icon.png"</a></td>
                    </tr>
                    ';    
                }
                ?>
            </tbody>
        </table>

<!-- ***************************************     panel info no values found (only if dropdown filter results in no values)  -->        
        <div class="lvepanel lvepanel-body" <?php echo $display_whenFilterNoResult ?>>
            <?php echo $p->t('global/keineSuchergebnisse') ?> 
        </div>


    </body>
</html>

