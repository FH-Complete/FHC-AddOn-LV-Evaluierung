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
require_once('../../../include/organisationseinheit.class.php');
require_once('../../../include/lehrveranstaltung.class.php');
require_once('../../../include/studiengang.class.php');
require_once('../../../include/studiensemester.class.php');
require_once('../../../include/benutzer.class.php');
require_once('../../../include/datum.class.php');
require_once('../../../include/mail.class.php');
require_once('../include/lvevaluierung.class.php');
require_once('../include/lvevaluierung_code.class.php');
require_once('../include/lvevaluierung_jahresabschluss.class.php');
require_once('../include/lvevaluierung_selbstevaluierung.class.php');

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

//Studiengang
if (isset($_REQUEST['studiengang_kz']) && !empty($_REQUEST['studiengang_kz']))
	$studiengang_kz = $_REQUEST['studiengang_kz'];
else header('Location: uebersicht.php');
//Studienjahr
if (isset($_REQUEST['studienjahr_kurzbz']) && !empty($_REQUEST['studienjahr_kurzbz']))
	$studienjahr_kurzbz = $_REQUEST['studienjahr_kurzbz'];
else die($p->t('global/studienjahrKonnteNichtGefundenWerden'));

if (!empty($_POST['studiengang_kz']) && !is_numeric($_POST['studiengang_kz']))
	die ($p->t('global/fehlerBeiDerParameteruebergabe'));

//Organisationseinheit
$stg = new studiengang();
$stg->load($studiengang_kz);
$oe_kurzbz = $stg->oe_kurzbz;
//Wintersemester / Sommersemester
$studiensemester = new studiensemester();
$studiensemester->getWSFromStudienjahr($studienjahr_kurzbz);
$ws = $studiensemester->result;
$studiensemester->getSSFromStudienjahr($studienjahr_kurzbz);
$ss = $studiensemester->result;

// ***************************************	 PAGE BUILDING DATA on page opening  -->
//Studienabschlussbericht vars
list(
	$isNew,
	$isFreigegeben,
	$lvevaluierung_jahresabschluss_id,
	$ergebnisse,
	$verbesserungen,
	$date_whenFreigegeben) =
	checkStudienabschlussbericht($oe_kurzbz, $studienjahr_kurzbz);

$locked = ($isFreigegeben) ? 'disabled' : '';									//locks textareas and buttons if studienabschluss exists
$display_whenFreigegeben = ($isFreigegeben) ? '' : 'style = "display: none;"' ; //show / hides freigegeben-info
$display_whenSaved = 'style = "display: none;"';								//show / hides saved-info
$display_whenSent = 'style = "display: none;"';									//show / hides sent to-info

list(
	$selbstev_arr,						//saves all selbstevaluierungen per studiengang and studienjahr
	$selbstev_cnt) =					//sums up number of selbstevaluierungen per studiengang and studienjahr
	getEvaluierteLV($studiengang_kz, $ws, $ss);


if (isset($selbstev_arr['orgform_kurzbz']))
{
	$orgform_unique_arr = array_unique($selbstev_arr['orgform_kurzbz']);
	sort($orgform_unique_arr);
}

$display_whenFilterNoResult = (numberOfElements($selbstev_arr) != 0) ? 'style = "display: none;"' : '';


list (
	$lv_cnt,						//sums up number of lv per studiengang and studienjahr
	$ev_quote,						//calculates evaluation quota
	$ev_quoten_txt) =				//text for evaluation quota
	getMainInfo($studiengang_kz, $ws, $ss, $selbstev_cnt);

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
	//Studienabschlussbericht-Ergebnisse
	(isset($_POST['ergebnisse'])) ? $ergebnisse = $_POST['ergebnisse'] : '';
	//Studienabschlussbericht-Verbesserungen
	(isset($_POST['verbesserungen'])) ? $verbesserungen = $_POST['verbesserungen'] : '';

// ***************************************	 SAVE / SAVEandSEND  jahresabschlussbericht  -->
	if(isset($_POST['saveJahresabschlussbericht']) || isset($_POST['saveandsendJahresabschlussbericht']))
	{
		if ($ergebnisse == '' && $verbesserungen == '')
			echo '<span class="error">Bitte beschreiben Sie erst Ergebnisse oder Verbesserungen, um einen Jahresabschlussbericht zu speichern.</span>';
		else
			if(!saveJahresabschlussbericht($isNew, $lvevaluierung_jahresabschluss_id, $oe_kurzbz, $studienjahr_kurzbz, $ergebnisse, $verbesserungen))
				echo '<span class="error">' . $p->t('global/fehlerBeimSpeichernDerDaten') . '</span>';
			else
				$display_whenSaved = '';

		if (isset($_POST['saveandsendJahresabschlussbericht']))
		{
			if ($ergebnisse == '' || $verbesserungen == '')
				echo '<span class="error">Bitte beschreiben Sie erst die Ergebnisse und Verbesserungen. Erst dann können Sie den Studienabschlussbericht abschicken.</span>';
			else
			{
				$isFreigegeben = true;
				$locked = 'disabled';
				$to = mailJahresabschlussbericht($studiengang_kz, $studienjahr_kurzbz, $stg);
				if ($to === false)
					echo '<span class="error">' . $p->t('global/emailNichtVersendet') .'</span>';
				else
					$display_whenSent = '';
			}
		}
	}

// ***************************************	 PRINT PDF jahresabschlussbericht  -->
	if(isset($_POST['printJahresabschlussbericht']))
	{
		if(!printJahresabschlussbericht($lvevaluierung_jahresabschluss_id))
			echo '<span class="error">' . ($p->t('lvevaluierung/jahresabschlussberichtNichtVorhanden') . '</span>');  ;
	}
}

// ***************************************	 FUNCTIONS  -->
//check, if studienabschlussbericht exists
function checkStudienabschlussbericht($oe_kurzbz, $studienjahr_kurzbz)
{
	global $db;
	$isNew = true;
	$isFreigegeben = false;
	$ergebnisse = '';
	$verbesserungen = '';
	$lvevaluierung_jahresabschluss_id = 0;
	$date_whenFreigegeben = new datum();
	$jahresabschluss = new lvevaluierung_jahresabschluss();

	if ($jahresabschluss->exists($oe_kurzbz, $studienjahr_kurzbz))
	{
		$isNew = false;
		$jahresabschluss->getByOeStudienjahr($oe_kurzbz, $studienjahr_kurzbz);
		$lvevaluierung_jahresabschluss_id = $jahresabschluss->result[0]->lvevaluierung_jahresabschluss_id;
		$ergebnisse = $jahresabschluss->result[0]->ergebnisse;
		$verbesserungen = $jahresabschluss->result[0]->verbesserungen;
		$isFreigegeben = $db->db_parse_bool($jahresabschluss->result[0]->freigegeben);
	}
	$date_whenFreigegeben = ($isFreigegeben) ? $date_whenFreigegeben->convertISODate ($jahresabschluss->result[0]->updateamum) : '';

	return array($isNew, $isFreigegeben, $lvevaluierung_jahresabschluss_id, $ergebnisse, $verbesserungen, $date_whenFreigegeben);
}
//get data for table with main data
function getMainInfo($studiengang_kz, $ws, $ss, $selbstev_cnt)
{
	global $p;
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
		($ev_quote != 0) ? $ev_quoten_txt = $p->t('lvevaluierung/evaluierungQuoteTxtResult', $ev_quote) : $ev_quoten_txt = $p->t('lvevaluierung/evaluierungQuoteTxtNoResult');
	}
	else
		$ev_quoten_txt = '-';

	return array($lv_cnt, $ev_quote, $ev_quoten_txt);
}
//gets data for table with evaluierte lehrveranstaltungen
function getEvaluierteLV($studiengang_kz, $ws, $ss)
{
	global $db;
	$selbstevaluierung = new lvevaluierung_selbstevaluierung();
	$selbstev_arr = array();
	$selbstev_cnt = 0;

	//get all selbstevaluierungen per studiengang and studienjahr
	$selbstevaluierung->getLVwhereSelbstevaluierungen($studiengang_kz, $ws, $ss);
	$selbstev_arr = $selbstevaluierung->result;
	if (numberOfElements($selbstev_arr) > 0)
		$selbstev_cnt = numberOfElements($selbstev_arr['bezeichnung']);


	return array($selbstev_arr, $selbstev_cnt);
}
//saves new / updated jahresabschlussbericht
function saveJahresabschlussbericht($isNew, $lvevaluierung_jahresabschluss_id, $oe_kurzbz, $studienjahr_kurzbz, $ergebnisse, $verbesserungen)
{
	global $uid;
	$jahresabschluss = new lvevaluierung_jahresabschluss();

	if(!$isNew)
	{
		$jahresabschluss->lvevaluierung_jahresabschluss_id = $lvevaluierung_jahresabschluss_id;
		$jahresabschluss->new = false;
	}

	$jahresabschluss->oe_kurzbz = $oe_kurzbz;
	$jahresabschluss->studienjahr_kurzbz = $studienjahr_kurzbz;
	$jahresabschluss->ergebnisse = $ergebnisse;
	$jahresabschluss->verbesserungen = $verbesserungen;
	(isset($_POST['saveandsendJahresabschlussbericht']) && ($ergebnisse != '' && $verbesserungen != '')) ? $jahresabschluss->freigegeben = true : $jahresabschluss->freigegeben = false;
	($jahresabschluss->new) ? $jahresabschluss->insertamum = date('Y-m-d H:i:s') : '';
	($jahresabschluss->new) ? $jahresabschluss->insertvon = $uid : '';
	$jahresabschluss->updateamum = date('Y-m-d H:i:s');
	$jahresabschluss->updatevon = $uid;

	return ($jahresabschluss->save()) ? true : false;
}
//set email data and send mail
function mailJahresabschlussbericht($studiengang_kz, $studienjahr_kurzbz, $stg)
{
	global $uid, $db, $p;

	//get data about sender
	$benutzer = new benutzer();
	$benutzer->load($uid);

	//set receivers email adresses
	$receiver_arr = array();
	$rechte = new benutzerberechtigung();

	$stgoe = $stg->oe_kurzbz;

	$organisationseinheit = new organisationseinheit();

	if ($rechte->getBenutzerFromBerechtigung('addon/lvevaluierung_rektorat', false, null))
	{
		if ($stgoeparents = $organisationseinheit->getParents($stgoe))
		{
			if (isset($rechte->result) && is_array($rechte->result))
			{
				foreach ($rechte->result as $row)
				{
					$receivemailberechtigt = false;

					if ($row->oe_kurzbz == null)
						$receivemailberechtigt = true;
					else
					{
						foreach ($stgoeparents as $stgoeparent)
						{
							if ($row->oe_kurzbz === $stgoeparent)
							{
								$receivemailberechtigt = true;
								break;
							}
						}
					}

					if ($receivemailberechtigt && (($row->ende == NULL || $row->ende > date('Y-m-d')) && ($row->start == NULL || $row->start < date('Y-m-d'))))
					{
						$benutzeraktiv = new benutzer($row->uid);
						if ($benutzeraktiv->aktiv)
						{
							$receiver_arr[] = $row->uid.'@'.DOMAIN;
						}
					}
				}
			}
		}
	}

	//set mail attributes & content
	$from = 'noreply@'.DOMAIN;
	$to = (numberOfElements($receiver_arr) > 1) ? implode(', ', $receiver_arr) : $receiver_arr[0];
	$subject = 'LV-Evaluierung - Studienabschlussbericht ' . $stg->bezeichnung . ' - ' . $studienjahr_kurzbz;
	$content = '<p>' . $p->t('lvevaluierung/XhatEinenJahresabschlussDurchgefuehrt',
		array(
			$benutzer->titelpre.' '.$benutzer->vorname.' '.$benutzer->nachname.' '.$benutzer->titelpost,
			$stg->kurzbzlang . ' - ' . $studienjahr_kurzbz)).'<br /><br /><br />';
	$content.= '<p>' . $p->t('lvevaluierung/folgenSieDenLinks').'</p>';
	$content.= '<a href="' . APP_ROOT. 'addons/lvevaluierung/cis/lvevaluierung_jahresabschluesse_erstellen.php' . '?studiengang_kz=' . urlencode($studiengang_kz) . '&studienjahr_kurzbz=' . urlencode($studienjahr_kurzbz).'">LV-Evaluierungen Studienabschlussbericht ' . $stg->kurzbzlang . ' - ' . $studienjahr_kurzbz . ' anzeigen</a><br /><br />';
	$content.= '<a href="' . APP_ROOT. 'addons/lvevaluierung/cis/uebersicht.php">LV-Evaluierungen Übersicht anzeigen</a><br /><br /><br />';

	//send mail
	$mail = new mail($to, $from, $subject, $content);
	$mail->setHTMLContent($content);
	$mail->setReplyTo($uid.'@'.DOMAIN);

	return (!$mail->send()) ? false : $to;

}
//exports data for pdf creation & printing
function printJahresabschlussbericht($lvevaluierung_jahresabschluss_id)
{
	global $p;
	if($lvevaluierung_jahresabschluss_id != 0)
		header("Location: lvEvaluierungAbschlussbericht.pdf.php?lvev_jahresabschluss_id=" . $lvevaluierung_jahresabschluss_id);
	else
		return false;
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
			$anzahl_codes_gesamt = numberOfElements($codes->result);

		if($anzahl_codes_gesamt>0)
			$prozent_abgeschlossen = (100/$anzahl_codes_gesamt*$anzahl_codes_beendet);
		else
			$prozent_abgeschlossen = 0;
	}

	return '<span>(' . sprintf("%6s", number_format($prozent_abgeschlossen, 2)) . '%)</span>';
}

?>



<!DOCTYPE html>
<html>
	<head>
		<title><?php echo $p->t('lvevaluierung/lvevaluierungJahresabschlussbericht') ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<link href="../../../skin/fhcomplete.css" rel="stylesheet" type="text/css">
		<link href="../../../skin/style.css.php" rel="stylesheet" type="text/css">
		<link href="../skin/lvevaluierung.css" rel="stylesheet" type="text/css">
		<link rel="stylesheet" href="../../../skin/tablesort.css" type="text/css">
		<link href="../../../skin/jquery-ui-1.9.2.custom.min.css" rel="stylesheet"  type="text/css">
		<script type="text/javascript" src="../../../vendor/jquery/jquery1/jquery-1.12.4.min.js"></script>
		<script type="text/javascript" src="../../../vendor/christianbach/tablesorter/jquery.tablesorter.min.js"></script>
		<script type="text/javascript" src="../../../vendor/components/jqueryui/jquery-ui.min.js"></script>
	</head>

	<body class="main">

		<h1><?php echo $p->t('lvevaluierung/lvevaluierungJahresabschlussbericht') ?></h1>
		<h2><?php echo $p->t('lvevaluierung/jahresabschlussbericht') . ' ' . $stg->kurzbzlang . ' - ' . $studienjahr_kurzbz?></h2>

<!-- ***************************************	 table main data  -->
		<table class="tablesorter" width ="100%">
			<tbody>
				<tr>
					<td><b><?php echo $p->t('global/studiengang') ?></b></td>
					<td><b><?php echo $stg->bezeichnung ?></b></td>
				</tr>
				<tr>
					<td><b><?php echo $p->t('global/studienjahr') ?></b></td>
					<td><b><?php echo $ws . ' / ' . $ss ?></b></td>
				</tr>
				<tr>
					<td><?php echo $p->t('lvevaluierung/evaluierungAnzahl') ?></td>
					<?php
					echo  '<td>' . $p->t('lvevaluierung/evaluierungAnzahlTxt', array($lv_cnt, $selbstev_cnt)) . '</td>';
					?>
				</tr>
				<tr>
					<td><?php echo $p->t('lvevaluierung/evaluierungQuote') ?></td>
					<?php
					echo '<td>' . $ev_quoten_txt . '</td>';
					?>
				</tr>
			</tbody>
		</table>

<!-- ***************************************	 table evaluierte lehrveranstaltungen  -->
		<table class="table" width ="100%">
			<tbody>
				<?php
				if (isset($orgform_unique_arr))
				{
					foreach($orgform_unique_arr as $orgform)
					{
					?>
					<tr>
						<th style="width: 60%;"><b><?php echo $p->t('lvevaluierung/evaluierteLVs') . ' ' . $orgform ?></b></th>
						<th><b><?php echo $p->t('lvevaluierung/ausbildungssemester') ?></b></th>
						<th><?php echo $p->t('lvevaluierung/selbstevaluierung') ?></th>
						<th><?php echo $p->t('lvevaluierung/auswertung') ?><small> (<?php echo $p->t('lvevaluierung/ruecklaufquote') ?> in %)</small></th>
					</tr>
					<?php
						for ($i = 0; $i < $selbstev_cnt; $i++)
						{

							if ($selbstev_arr['orgform_kurzbz'][$i] == $orgform)
							{
								echo '
								<tr>
									<td>'.$selbstev_arr['bezeichnung'][$i].'</td>
									<td style="text-align: center;">'.$selbstev_arr['semester'][$i].'</td>
									<td style="text-align: center;">
										<a href="#" onclick="javascript:window.open(\'selbstevaluierung.php?lvevaluierung_id=' . $selbstev_arr['lvevaluierung_id'][$i] . '\',\'Selbstevaluierung\',
										\'width=700,height=750,resizable=yes,menuebar=no,toolbar=no,status=yes,scrollbars=yes\');return false;">
										<img src="../../../skin/images/edit-paste.png" height="15px" title="Selbstevaluierung anzeigen"></a>
									</td>
									<td style="text-align: center;">
										<a href="#" onclick="javascript:window.open(\'auswertung.php?lvevaluierung_id=' . $selbstev_arr['lvevaluierung_id'][$i] . '\',\'Auswertung\',
											\'width=700,height=750,resizable=yes,menuebar=no,toolbar=no,status=yes,scrollbars=yes\');return false;"><img src="../../../skin/images/statistic.png" height="15px" title="Auswertung anzeigen"></a>
										</a>' . getRuecklaufquote($selbstev_arr['lvevaluierung_id'][$i], $selbstev_arr['lehrveranstaltung_id'][$i], $ws, $ss) . '
									</td>
								</tr>';
							}
						}
					}
				}
				?>
			</tbody>
		</table>

<!-- ***************************************	 panel info no values found (only if no lv-evaluierungen)  -->
		<div class="lvepanel lvepanel-body" <?php echo $display_whenFilterNoResult ?>>
			<?php echo $p->t('lvevaluierung/keineEvaluierteLvs') ?>
		</div>

<!-- ***************************************	 FORM START  -->
		<form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?studiengang_kz=' . urlencode($studiengang_kz) . '&studienjahr_kurzbz=' . urlencode($studienjahr_kurzbz);?>">

		<!-- ***************************************	 textarea ergebnisse  -->
		<div class="lvepanel">
			<div class="lvepanel-head"><?php echo $p->t('lvevaluierung/welcheErgebnisse') ?></div>
			<div class="lvepanel-body">
				<textarea <?php echo $locked ?> name="ergebnisse"><?php echo $db->convert_html_chars($ergebnisse) ?></textarea>
			</div>
		</div>

<!-- ***************************************	 textarea verbesserungsmaßnahmen  -->
		<div class="lvepanel">
			<div class="lvepanel-head"><?php echo $p->t('lvevaluierung/welcheVerbesserungen') ?></div>
			<div class="lvepanel-body">
				<textarea <?php echo $locked ?> name="verbesserungen"><?php echo $db->convert_html_chars($verbesserungen) ?></textarea>
			</div>
		</div>

<!-- ***************************************	 panel info saved )  -->
		<div id="msgBox" class="lvepanel lvepanel-body ok" <?php echo $display_whenSaved ?>>
			<span><?php echo $p->t('global/erfolgreichgespeichert') ?></span></br>
			<span <?php echo $display_whenSent ?>><?php echo $p->t('global/emailgesendetan') . ' ' .  $to ?></span>
		</div>

<!-- ***************************************	 panel info gesperrt (only if $freigegeben == true)  -->
		<div class="lvepanel lvepanel-body" <?php echo $display_whenFreigegeben ?>>
			 <?php echo $p->t('lvevaluierung/jahresabschlussberichtGesperrt', $date_whenFreigegeben) ?>
		</div>

<!-- ***************************************	 buttons  -->
		<input <?php echo $locked ?> type="submit" name="saveJahresabschlussbericht" value="<?php echo $p->t('global/speichern') ?>">
		<input <?php echo $locked ?> type="submit" name="saveandsendJahresabschlussbericht" value="<?php echo $p->t('global/speichern'). ' & ' .$p->t('global/abschicken') ?>"
			onclick="return confirm('<?php echo $p->t('lvevaluierung/selbstevaluierungAbschicken') ?>')">
		<input type="submit" name="printJahresabschlussbericht" value="<?php echo $p->t('global/drucken') ?>">
		</form>
<!-- ***************************************	 FORM END  -->
	</body>
</html>
