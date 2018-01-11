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
require_once('../../../include/phrasen.class.php');
require_once('../../../include/benutzerberechtigung.class.php');
require_once('../../../include/studiengang.class.php');
require_once('../../../include/studienjahr.class.php');
require_once('../../../include/organisationsform.class.php');

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

//show/hide lvevaluierung prüfen $ alle jahresabschlussberichte anzeigen
$display = ($isStgl && !$isRektor) ? 'style = "display: none;"' : '';



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
        echo '<option value="'.$row->studiengang_kz.'"'.(($studiengang_kz == $row->studiengang_kz) ? 'selected' : '').'>'.$row->kuerzel.' - '.$row->bezeichnung.'</option>';
        $typ = $row->typ;
    }
}
//dropdown fachbereich (=institut)
function printOptions_fb()
{
    global $rechte, $p;
    $oe_kurzbz = (isset($_POST['oe_kurzbz'])) ? $_POST['oe_kurzbz'] : '';
    
    $fachbereich_arr = $rechte->getFbKz('addon/lvevaluierung');
    $fachbereich = new fachbereich();
    $fachbereich->loadArray($fachbereich_arr, 'bezeichnung');
       
    foreach($fachbereich->result as $row)
    {       
        $selected = ($oe_kurzbz == $row->oe_kurzbz) ? "selected" : '';
        echo '<option value="'.$row->oe_kurzbz.'"'. $selected .'>'.$row->bezeichnung.'</option>';
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
//dropdown organisationsform
function printOptions_orgForm()
{
    global $p;
    $orgform_kurzbz = (isset($_POST['orgform_kurzbz'])) ? $_POST['orgform_kurzbz'] : '';
    $orgform = new organisationsform();
    $orgform->getOrgformLV();
    
    foreach ($orgform->result as $row)
    {
        $selected = ($orgform_kurzbz == $row->orgform_kurzbz) ? "selected" : '';
        echo '<option value="'.$row->orgform_kurzbz.'" '.$selected.'>'.$row->orgform_kurzbz.' - '.$row->bezeichnung.'</option>';
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

?>

<!DOCTYPE html>
<html>
	<head>
		<title><?php echo $p->t('lvevaluierung/uebersicht') ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<link href="../../../skin/fhcomplete.css" rel="stylesheet" type="text/css">
		<link href="../../../skin/style.css.php" rel="stylesheet" type="text/css">
		<link href="../skin/lvevaluierung.css" rel="stylesheet" type="text/css">
		<link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css">
		<link href="../../../skin/jquery-ui-1.9.2.custom.min.css" rel="stylesheet"  type="text/css">
		<script type="text/javascript" src="../../../vendor/jquery/jqueryV1/jquery-1.12.4.min.js"></script>
		<script type="text/javascript" src="../../../vendor/christianbach/tablesorter/jquery.tablesorter.min.js"></script>
		<script type="text/javascript" src="../../../vendor/components/jqueryui/jquery-ui.min.js"></script>
        
<script>            
$(function() {

$("form :submit").click(function(event){   
    var required_arr = [];
    var name_arr = [];
    var val_arr = [];
    var dd_name_arr =[];
    var msg = '';
    var and_name_arr = [];
    var xor_name_arr = [];
    var or_name_arr = [];

    var xor = false;
    var and = false;
    var or = false;
    var xor_selected_arr = [];
    var and_empty_cnt = 0;
    var or_cnt = 0;

    //remove warning message
    $("small").remove(); 

    $(this).parent().find('select').each(function()
    {                   
        required_arr.push($(this).attr('class'));
        name_arr.push($(this).attr('name'));
        val_arr.push($(this).val());
        dd_name_arr.push($(this).find("option:first").text().substring(2,($(this).find("option:first").text().length)-2));
     })


    for (var i = 0; i < required_arr.length; i++)
    {
        switch (required_arr[i]) 
        {
            case 'required': 
                if(!val_arr[i])
                {
                    event.preventDefault();
                    msg = '<?php echo $p->t('global/bitteWaehlen')?>: ' + dd_name_arr[i];
                }
                break;
            case 'required-and': 
                and = true;
                and_name_arr.push(dd_name_arr[i]);
                if(!val_arr[i])
                      and_empty_cnt++;
                break;
            case 'required-or':
                or = true;
                or_name_arr.push(dd_name_arr[i]);
                if(val_arr[i])
                      or_cnt++;
                break;
            case 'required-xor':
                xor = true;
                xor_name_arr.push(dd_name_arr[i]);
                if(val_arr[i])
                    xor_selected_arr.push(dd_name_arr[i]);
                break;
        }              
    }
  
//if xor fields required 
if (xor)
{
    if (xor_selected_arr.length === 0 || xor_selected_arr.length > 1)  
    {
        event.preventDefault();
        msg = '<?php echo $p->t('global/bitteWaehlen')?>: ' + xor_name_arr.join(' <?php echo strtoupper($p->t('global/oder'))?> ');
    }
    else if (xor_selected_arr.length == 1)
        return;
}  
if (or && or_cnt == 0)
{
    event.preventDefault();
    msg = '<?php echo $p->t('global/bitteWaehlen')?>: ' + or_name_arr.join(' <?php echo $p->t('global/und') . "/" . $p->t('global/oder')?> ');
}
if(and && and_empty_cnt > 0)
{
    event.preventDefault();
    msg = '<?php echo $p->t('global/bitteWaehlen')?>: ' + and_name_arr.join(' <?php echo strtoupper($p->t('global/und'))?> ');
}


//create warning massage
    if(msg)
        $("<p><div><small class='error'>" + msg + "</small></div></p>").appendTo($(this).parent().prev());

})
});    
</script>
    </head>
   
    
    <body class="main" style="padding: 15px;">
        
        <h1><?php echo $p->t('lvevaluierung/uebersicht') ?></h1>
        
<!-- ***************************************     panel LV Evaluierungen anfordern (access for lector & rector) -->
        <div class="lvepanel">
            <div class="lvepanel-head"><?php echo $p->t('lvevaluierung/evaluierungenAnfordern') ?></div>
            <div class="lvepanel-body"><?php echo $p->t('lvevaluierung/evaluierungenAnfordernTxt') ?></p>                
            <form action="lvevaluierung_anfordern.php" method="POST">
                    <select class="required-or" name="studiengang_kz" style="width: 20%;">
                    <option value=""><?php echo '--' . $p->t('global/studiengang') . '--' ?></option>
                    <?php printOptions_stg(); ?>   
                    </select><span>&emsp;und/oder&emsp;</span>                
                                        
                    <select class="required-or" name="oe_kurzbz" style="width: 20%;">
                    <option value=""><?php echo '--' . $p->t('global/institut') . '--' ?></option>                  
                    <?php printOptions_fb(); ?>                      
                    </select></p>
                    
                    <select name="semester" style="width: 20%;">
                    <option value=""><?php echo '--' . $p->t('global/semester') . '--' ?></option>
                    <?php printOptions_sem(); ?>             
                    </select></p>
                    
                    <select name="orgform_kurzbz" style="width: 20%;">
                    <option value=""><?php echo '--' . $p->t('global/organisationsform') . '--' ?></option>
                    <?php
                    printOptions_orgForm(); 
                    echo '</select></p>';
                    echo '<input type="submit" value="'.$p->t('global/anzeigen').'">';
                    ?>
                </form>
            </div>
        </div>

<!-- ***************************************     panel LV Evaluierung Jahresabschluss bearbeiten (access for lector & rector) -->
        <div class="lvepanel">
            <div class="lvepanel-head"><?php echo $p->t('lvevaluierung/jahresabschlussberichtErstellen') ?></div>
            <div class="lvepanel-body"><?php echo $p->t('lvevaluierung/jahresabschlussberichtErstellenTxt') ?></p>                
                <form novalidate action="lvevaluierung_jahresabschluesse_erstellen.php" method="POST">                    
                    <select class="required-and" name="studiengang_kz" style="width: 20%;">
                    <option value=""><?php echo '--' . $p->t('global/studiengang') . '--' ?></option>
                     <?php printOptions_stg(); ?>          
                    </select></p>
                    
                    <select class="required-and" name="studienjahr_kurzbz" style="width: 20%;">
                    <option value=""><?php echo '--' . $p->t('global/studienjahr') . '--' ?></option>
                    <?php
                    printOptions_stj(); 
                    echo '</select></p>'; 
                    
                    echo '<input type="submit" value="'.$p->t('global/anzeigen'). ' / ' 
                                                       .$p->t('global/bearbeiten'). ' / ' 
                                                       .$p->t('global/drucken').'">';
                    ?>    
                </form>
            </div>
        </div>

<!-- ***************************************     panel LV Evaluierung prüfen (access for rector only) -->
        <div class="lvepanel" <?php echo $display ?> >
            <div class="lvepanel-head"><?php echo $p->t('lvevaluierung/evaluierungenPruefen') ?></div>
            <div class="lvepanel-body"><?php echo $p->t('lvevaluierung/evaluierungenPruefenTxt') ?></p>                
               <form novalidate action="lvevaluierung_pruefen.php" method="POST"> 
                    <select name="studienjahr_kurzbz" style="width: 20%;">
                    <option value=""><?php echo '--' . $p->t('global/studienjahr') . '--' ?></option>
                    <?php
                    printOptions_stj(); 
                    echo '</select></p>';   
                    echo '<input type="submit" value="'.$p->t('global/anzeigen').'">';
                    ?>     
                </form>              
            </div>
        </div>

<!-- ***************************************     panel LV Evaluierung Jahresabschluss anzeigen (access for rector only) -->
        <div class="lvepanel" <?php echo $display ?> >
            <div class="lvepanel-head"><?php echo $p->t('lvevaluierung/alleJahresabschlussberichteAnzeigen') ?></div>
            <div class="lvepanel-body"><?php echo $p->t('lvevaluierung/alleJahresabschlussberichteAnzeigenTxt') ?></p>              
                <button type="button" onclick="location.href = 'lvevaluierung_jahresabschluesse_anzeigen.php'"><?php echo $p->t('global/anzeigen') ?></button>                            
            </div>
        </div>
               
    </body>
</html>

