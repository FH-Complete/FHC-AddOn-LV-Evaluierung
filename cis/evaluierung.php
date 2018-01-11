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
require_once('../../../config/global.config.inc.php');
require_once('../../../include/phrasen.class.php');
require_once('../../../include/functions.inc.php');
require_once('../../../include/datum.class.php');
require_once('../../../include/lehrveranstaltung.class.php');
require_once('../../../include/benutzer.class.php');
require_once('../../../include/lehreinheitmitarbeiter.class.php');
require_once('../include/lvevaluierung_code.class.php');
require_once('../include/lvevaluierung.class.php');
require_once('../include/lvevaluierung_frage.class.php');
require_once('../include/lvevaluierung_antwort.class.php');

session_cache_limiter('none');
session_start();

// Wenn Code nicht gesetzt ist auf Login umleiten
if (!isset($_SESSION['lvevaluierung/code']) || $_SESSION['lvevaluierung/code']=='')
{
	$_SESSION['request_uri']=$_SERVER['REQUEST_URI'];

	header('Location: index.php');
	exit;
}

// Sprachewechsel
$sprache = filter_input(INPUT_GET, 'sprache');

if(isset($sprache))
{
	$sprache = new sprache();
	if($sprache->load($_GET['sprache']))
	{
		setSprache($_GET['sprache']);
	}
	else
		setSprache(DEFAULT_LANGUAGE);
}

$sprache = getSprache();
$p = new phrasen($sprache);
$db = new basis_db();
$message='';

// Pruefen ob der Code gueltig ist und die Evaluierung offen ist
$lvevaluierung_id = $_SESSION['lvevaluierung/lvevaluierung_id'];
$code = $_SESSION['lvevaluierung/code'];

$lvevaluierung_code = new lvevaluierung_code();
if(true===$lvevaluierung_code->getCode($code))
{
	if($lvevaluierung_code->endezeit=='')
	{
		$lvevaluierung = new lvevaluierung();
		if(!$lvevaluierung->isOffen($lvevaluierung_id))
		{
			session_destroy();
			die($p->t('lvevaluierung/nichtoffen'));
		}
	}
	else
	{
		die($p->t('lvevaluierung/codeAbgelaufen'));
	}
}
else
{
	die($p->t('lvevaluierung/codeFalsch'));
}

?>
<!DOCTYPE html>
<html>
	<head>
		<title><?php echo $p->t('lvevaluierung/lvevaluierung'); ?></title>
		<meta http-equiv="X-UA-Compatible" content="chrome=1">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<meta name="robots" content="noindex">
		<link href="../../../vendor/components/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css">
<!--		<link href="../../../skin/style.css.php" rel="stylesheet" type="text/css">-->
		<link href="../skin/lvevaluierung.css" rel="stylesheet" type="text/css">

		<style>
		.btn-group {
		  white-space: nowrap;
		  .btn {
			float: none;
			display: inline-block;
		  }
		}
		.btn-group {
		  display: flex;
		}
		</style>
	</head>
	<body class="main">
		<div class="container-fluid">
			<?php
			$sprache2 = new sprache();
			$sprache2->getAll(true);
			?>
			<div class="dropdown pull-right">
				<button class="btn btn-default dropdown-toggle" type="button" id="sprache-label" data-toggle="dropdown" aria-expanded="true">
					<?php echo $db->convert_html_chars($sprache2->getBezeichnung(getSprache(), getSprache())); ?>
					<span class="caret"></span>
				</button>
				<ul class="dropdown-menu" role="menu" aria-labelledby="sprache-label" id="sprache-dropdown">
					<?php foreach($sprache2->result as $row): ?>
						<li role="presentation">
							<a href="#" role="menuitem" tabindex="-1" data-sprache="<?php echo $row->sprache ?>">
								<?php echo $db->convert_html_chars($row->bezeichnung_arr[getSprache()]); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<ol class="breadcrumb">
					<li class="active">
						<a href="<?php echo basename(__FILE__) ?>">
							<?php echo $p->t('lvevaluierung/lvevaluierung') ?>
						</a>
					</li>
			</ol>

		<?php

		// Speichern der Daten
		if(isset($_POST['submit_btn']))
		{
            $lektor_uid = (isset($_POST['lektor_uid'])) ? $_POST['lektor_uid'] : '';          
			// Save
            $fragenantworten=array();
			// Alle Antworten speichern
			foreach($_POST as $key_post=>$value_post)
			{
				if(mb_strstr($key_post,'antwort_'))
				{
					$frage_id = mb_substr($key_post,8);
					//echo $key_post.'->'.$value_post.'->'.$frage_id;

					$frage = new lvevaluierung_frage();
					if($frage->load($frage_id))
					{
						$antwort = new lvevaluierung_antwort();

						$antwort->lvevaluierung_frage_id=$frage_id;
						$antwort->lvevaluierung_code_id = $lvevaluierung_code->lvevaluierung_code_id;
						if($frage->typ=='text')
							$antwort->antwort = $value_post;
						else
							$antwort->lvevaluierung_frage_antwort_id = $value_post;

						$antwort->save();
					}
                    $fragenantworten[]=$frage_id;
				}
			}
            // Fragen die gestellt wurden, aber nicht beantwortet werden auch geholt und ohne Antwort gespeichert
            // damit erfasst ist, dass er die Frage bekommen hat

            $fragen_obj = new lvevaluierung_frage();
            $fragen_obj->getFragen();
            foreach($fragen_obj->result as $row_fragen)
            {
                if(!in_array($row_fragen->lvevaluierung_frage_id, $fragenantworten))
                {
                    $antwort = new lvevaluierung_antwort();

                    $antwort->lvevaluierung_frage_id=$row_fragen->lvevaluierung_frage_id;
                    $antwort->lvevaluierung_code_id = $lvevaluierung_code->lvevaluierung_code_id;
                    $antwort->antwort = '';
                    $antwort->lvevaluierung_frage_antwort_id = '';
                    $antwort->save();
                }
            }
           
			// Code Endezeit und Lektor setzen
			$lvevaluierung_code->endezeit=date('Y-m-d H:i:s');
            $lvevaluierung_code->lektor_uid = $lektor_uid;
			$lvevaluierung_code->save();

			// Ausloggen um umleiten
			session_destroy();

			echo '	<div class="alert alert-success" id="success-alert">
					<button type="button" class="close" data-dismiss="alert">x</button>
					<strong>'.$p->t('lvevaluierung/danke').' </strong>
				</div>';
			exit;
		}

		// Laufzeit ermitteln
		$dtstartzeit = new DateTime($lvevaluierung_code->startzeit);
		$dtnow = new DateTime();
		$laufzeit = $dtnow->diff($dtstartzeit);
		$laufzeit_sekunden = ($laufzeit->format('%H')*60*60)+($laufzeit->format('%i')*60)+($laufzeit->format('%s'));

		/*

		echo 'startzeit:'.$lvevaluierung_code->startzeit;
		echo 'Laufzeit in sekunden:'.$laufzeit->format('%H:%i:%s');
		echo 'GesamtDauer:'.$lvevaluierung->dauer;
		echo 'Sekunden:'.$laufzeit_sekunden;*/

		//Dauer der Umfrage in Sekunden umrechnen
		list($stunde, $minute, $sekunde) = explode(':',$lvevaluierung->dauer);
		$dauer_sekunden = (int) ($stunde*60*60+$minute*60+$sekunde);
		//Wenn die Zeit negativ ist und die Stunde 0 ist,
		//dann muss die Zeit mit -1 multipliziert werden
		if(substr($stunde,0,1)=='-' && $stunde==0)
			$dauer_sekunden = $dauer_sekunden*-1;
		$restdauer = $dauer_sekunden-$laufzeit_sekunden;

		if($restdauer<=0)
		{
			echo '	<div class="alert alert-success" id="success-alert">
					<button type="button" class="close" data-dismiss="alert">x</button>
					<strong>'.$p->t('lvevaluierung/codeAbgelaufen').' </strong>
				</div>';
			//exit;
		}

		$lvevaluierung = new lvevaluierung();
		$lvevaluierung->load($lvevaluierung_id);

		$lv = new lehrveranstaltung();
		$lv->load($lvevaluierung->lehrveranstaltung_id);

		$leiter_uid = $lv->getLVLeitung($lv->lehrveranstaltung_id, $lvevaluierung->studiensemester_kurzbz);
		$benutzer = new benutzer();
		$benutzer->load($leiter_uid);

        $lvleitung=$benutzer->titelpre.' '.$benutzer->vorname.' '.$benutzer->nachname.' '.$benutzer->titelpost;

        $lem = new lehreinheitmitarbeiter();
        $lem->getMitarbeiterLV($lv->lehrveranstaltung_id, $lvevaluierung->studiensemester_kurzbz);

        $lektoren='';
        foreach($lem->result as $row_lektoren)
            $lektoren .= $row_lektoren->titelpre.' '.$row_lektoren->vorname.' '.$row_lektoren->nachname.' '.$row_lektoren->titelpost.', ';
		$lektoren = mb_substr($lektoren, 0, -2);

		$stg = new studiengang();
		$stg->getAllTypes();
		$stg->load($lv->studiengang_kz);

		$studiengang_bezeichnung=$stg->bezeichnung;
		$studiensemester = $lvevaluierung->studiensemester_kurzbz;

		$teilnehmer = $lv->getStudentsOfLv($lv->lehrveranstaltung_id, $lvevaluierung->studiensemester_kurzbz);
		$anzahl_studierende=count($teilnehmer);
		$lehrform = $lv->lehrform_kurzbz;
        
        $lv_aufgeteilt = $lvevaluierung->lv_aufgeteilt;   

		echo '
		 <div class="table-responsive" >
			<table class="table  table-bordered">
			<tr>
				<td>'.$p->t('lvevaluierung/lvbezeichnung').'</td>
				<td>'.$db->convert_html_chars($lv->bezeichnung.' ('.$lv->lehrveranstaltung_id.')').'</td>
			</tr>
			<tr>
				<td>'.$p->t('lvevaluierung/lvleitung').'</td>
				<td>'.$db->convert_html_chars($lektoren).'</td>
			</tr>
			<tr>
				<td>'.$p->t('global/studiengang').'</td>
				<td>'.$db->convert_html_chars($stg->studiengang_typ_arr[$stg->typ]).' '.$db->convert_html_chars($studiengang_bezeichnung).'</td>
			</tr>
			<tr>
				<td>'.$p->t('lvevaluierung/organisationsform').'</td>
				<td>'.$db->convert_html_chars($lv->orgform_kurzbz).'</td>
			</tr>
			<tr>
				<td>'.$p->t('lvevaluierung/lvtyp').'</td>
				<td>'.$db->convert_html_chars($lehrform).'</td>
			</tr>
			<tr>
				<td>'.$p->t('lvevaluierung/ects').'</td>
				<td>'.$db->convert_html_chars($lv->ects).'</td>
			</tr>
			<tr>
				<td>'.$p->t('global/sprache').'</td>
				<td>'.$db->convert_html_chars($lv->sprache).'</td>
			</tr>
			<tr>
				<td>'.$p->t('global/studiensemester').'</td>
				<td>'.$db->convert_html_chars($studiensemester).'</td>
			</tr>
			<tr>
				<td>'.$p->t('lvevaluierung/ausbildungssemester').'</td>
				<td>'.$db->convert_html_chars($lv->semester).'</td>
			</tr>
			<tr>
				<td>'.$p->t('lvevaluierung/anzahlstudierende').'</td>
				<td>'.$db->convert_html_chars($anzahl_studierende).'</td>
			</tr>

			</table>
		</div>';

		echo $p->t('lvevaluierung/restzeit').' <span id="counter">00:00</span>';

		echo '	<div class="row">
					<div class="col-xs-10 col-sm-6 col-sm-offset-3 col-md-6 col-md-offset-3 col-lg-6 col-lg-offset-3">

                        <form action ="'.basename(__FILE__).'" method="POST" id="umfrage" name="umfrage" class="form-vertical">';

        //dropdown mit lektoren nur anzeigen, wenn LV von mehreren Lektoren gehalten worden ist (und bei erstellung der lvevaluierung checkbox gesetzt worden ist)
        if ($lv_aufgeteilt){
            echo '<div class="form-group">';
            echo '<label>' . $p->t('lvevaluierung/lektorDropdown') . ':</label>';          
            echo '          
            <select required class="form-control" name="lektor_uid">
            <option value="">' . $p->t('lvevaluierung/lektorWaehlen') . '</option>';
            foreach($lem->result as $row) 
            {  
                echo '<option value="' . $row->uid . '">' . $row->titelpre . ' ' . $row->titelpost . ' ' . $row->vorname . ' ' . $row->nachname . '</option>';
            }
            echo '
            </select>
            </div></p>';
        } 
        
		$frage = new lvevaluierung_frage();
		$frage->getFragen($lvevaluierung_id);

		foreach($frage->result as $row_frage)
		{
			switch($row_frage->typ)
			{
				// Label Titel
				case 'label':
					echo '<div><h2>'.$db->convert_html_chars($row_frage->bezeichnung[$sprache]).'</h2></div>';
					break;
					// Label Text
				case 'labelsub':
					echo '<div>'.$db->convert_html_chars($row_frage->bezeichnung[$sprache]).'<br><br></div>';
					break;

				// Textarea
				case 'text':
					echo '
							<div class="form-group">
								<label for="antwort_'.$row_frage->lvevaluierung_frage_id.'">'.$db->convert_html_chars($row_frage->bezeichnung[$sprache]).'</label>
								<div class="col-sm-9">
									<textarea name="antwort_'.$row_frage->lvevaluierung_frage_id.'" class="form-control"></textarea>
								</div>
							</div>';
					break;

				// SingeResponse Frage
				case 'singleresponse':

					$antwort = new lvevaluierung_frage();
					$antwort->loadAntworten($row_frage->lvevaluierung_frage_id);

					echo '<div class="form-group">
						<label>
						'.$row_frage->bezeichnung[$sprache].'<br>';

					$antwortinfo='';

					foreach($antwort->result as $row_antwort)
					{
                        if($row_antwort->wert!=0)
                        	if($row_antwort->bezeichnung[$sprache]!='')
							    $antwortinfo.= ' '.$db->convert_html_chars($row_antwort->wert).'='.$db->convert_html_chars($row_antwort->bezeichnung[$sprache]).';';
					}
                    echo '<span class="antwortinfo">';
                    echo mb_substr($antwortinfo,0,-1);
                    echo '</span>';
                    echo '</label>';


                    echo '
            			<div class="btn-group" data-toggle="buttons">';

					foreach($antwort->result as $row_antwort)
					{
                        if($row_antwort->wert!=0)
                        {
    						echo '
    							<label class="btn btn-primary">
    								<input type="radio" name="antwort_'.$row_antwort->lvevaluierung_frage_id.'" value="'.$row_antwort->lvevaluierung_frage_antwort_id.'" />'.$db->convert_html_chars($row_antwort->wert).'
    							</label>';
                        }
                        else
                        {
                            // keine Angabe
                            echo '
        							<label class="btn btn-primary">
        								<input type="radio" name="antwort_'.$row_antwort->lvevaluierung_frage_id.'" value="'.$row_antwort->lvevaluierung_frage_antwort_id.'" />'.$db->convert_html_chars($row_antwort->bezeichnung[$sprache]).'
        							</label>';
                        }
					}

					echo '</div>';

                    echo '
					</div>';

                    echo '<hr>';
					break;

				default:
					break;
			}
		}

		echo '
			<div class="col-lg-12 col-sm-12 col-xs-12" style="margin-top: 15px">
			<input type="hidden" name="submit_btn" value="1" />
			<button class="btn btn-primary" type="submit">
				'.$p->t('global/abschicken').'
			</button>
			</div>
		';

		?>
							<br><br><br><br><br><br><br>

						</form>
					</div>
				</div>
		</div>
		<script src="../../../vendor/jquery/jqueryV1/jquery-1.12.4.min.js"></script>
		<script src="../../../vendor/components/bootstrap/js/bootstrap.min.js"></script>
		<script type="text/javascript">

			function changeSprache(sprache)
			{
				window.location.href = "evaluierung.php?sprache=" + sprache;
			}

			$(function() {

				$('#sprache-dropdown a').on('click', function() {
					var sprache = $(this).attr('data-sprache');
					changeSprache(sprache);
				});
              
				<?php
				if($restdauer>0)
					echo 'count_down('.$restdauer.');';
				?>
			});



		function count_down(zeit)
		{
			if(zeit<=0)
			{
				document.umfrage.submit();
			}
			else
			{
				zeit = zeit-1;
				minuten = parseInt((zeit/60));
				if(minuten<10)
					minuten = '0'+minuten;
				sekunden = zeit-minuten*60;
				if(sekunden<10)
					sekunden = '0'+sekunden;
				window.document.getElementById('counter').innerHTML = minuten+':'+sekunden;

				window.setTimeout('count_down('+zeit+')',1000);
			}
		}

		</script>
	</body>
</html>
