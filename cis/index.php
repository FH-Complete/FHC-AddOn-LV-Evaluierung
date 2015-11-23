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
require_once('../include/lvevaluierung_code.class.php');
require_once('../include/lvevaluierung.class.php');

session_start();
$lang = filter_input(INPUT_GET, 'lang');

if(isset($lang))
	setSprache($lang);

$method = filter_input(INPUT_GET, 'method');
$message = '';
$datum = new datum();

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

$code = trim(filter_input(INPUT_GET, 'code'));

// Login gestartet
if ($code)
{
	$lvevaluierung_code = new lvevaluierung_code();

	if(true===$lvevaluierung_code->getCode($code))
	{
		if($lvevaluierung_code->endezeit=='')
		{
			$lvevaluierung = new lvevaluierung();
			if($lvevaluierung->isOffen($lvevaluierung_code->lvevaluierung_id))
			{

				$_SESSION['lvevaluierung/code'] = $code;
				$_SESSION['lvevaluierung/lvevaluierung_id'] = $lvevaluierung_code->lvevaluierung_id;

				$lvevaluierung_code->startzeit=date('Y-m-d H:i:s');
				$lvevaluierung_code->save();

				header('Location: evaluierung.php');
				exit;
			}
			else
			{
				$message = $p->t('lvevaluierung/nichtoffen');
			}
		}
		else
		{
			$message = $p->t('lvevaluierung/codeAbgelaufen');
		}
	}
	else
	{
		$message = $p->t('lvevaluierung/codeFalsch');
	}

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
		<link href="../../../skin/styles.css.php" rel="stylesheet" type="text/css">
		<link href="../skin/lvevaluierung.css" rel="stylesheet" type="text/css">
	</head>
	<body class="main">
		<div class="container">
			<?php
			$sprache2 = new sprache();
			$sprache2->getAll(true);
			?>
			<div class="dropdown pull-right">
				<button class="btn btn-default dropdown-toggle" type="button" id="sprache-label" data-toggle="dropdown" aria-expanded="true">
					<?php echo $sprache2->getBezeichnung(getSprache(), getSprache()) ?>
					<span class="caret"></span>
				</button>
				<ul class="dropdown-menu" role="menu" aria-labelledby="sprache-label" id="sprache-dropdown">
					<?php foreach($sprache2->result as $row): ?>
						<li role="presentation">
							<a href="#" role="menuitem" tabindex="-1" data-sprache="<?php echo $row->sprache ?>">
								<?php echo $row->bezeichnung_arr[getSprache()] ?>
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
				<div class="row">
					<div class="col-xs-10 col-xs-offset-1 col-sm-6 col-sm-offset-3">
                        <form action ="<?php echo basename(__FILE__) ?>" method="GET" id="lp" class="form-horizontal">
							<h1 class="text-center">
								<?php echo $p->t('lvevaluierung/welcome') ?>
							</h1>
							<img class="center-block img-responsive" src="../../../skin/styles/<?php echo DEFAULT_STYLE ?>/logo.png">
							<p class="infotext">
								<?php echo $p->t('lvevaluierung/einleitungstext') ?>
							</p>
		<?php
		if($message !='')
		{
			echo '	<div class="alert alert-danger" id="danger-alert">
					<button type="button" class="close" data-dismiss="alert">x</button>
					<strong>'.$p->t('global/fehleraufgetreten').' </strong>'.$message.'
				</div>';
		}
		?>

							<div class="form-group">
								<div class="input-group">
									<input class="form-control" type="text" placeholder="<?php echo $p->t('lvevaluierung/code') ?>" name="code" autofocus="autofocus">
									<span class="input-group-btn">
										<button class="btn btn-primary" type="submit" name="submit_btn">
											Login
										</button>
									</span>
								</div>
							</div>
							<br><br><br><br><br><br><br>
							<br><br><br><br><br><br><br>
							<br><br><br><br><br><br><br>

						</form>
					</div>
				</div>
		</div>
		<script src="../../../include/js/jquery.min.1.11.1.js"></script>
		<script src="../../../submodules/bootstrap/dist/js/bootstrap.min.js"></script>
		<script type="text/javascript">

			function changeSprache(sprache)
			{
				var method = '<?php echo $db->convert_html_chars($method);?>';

				window.location.href = "index.php?sprache=" + sprache + "&method=" + method;
			}

			$(function() {

				$('#sprache-dropdown a').on('click', function() {

					var sprache = $(this).attr('data-sprache');
					changeSprache(sprache);
				});
			});

		</script>
	</body>
</html>
