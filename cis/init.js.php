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
 *
 * Authors: Andreas Oesterreicher <andreas.oesterreicher@technikum-wien.at>
 */
/**
 * Initialisierung des Addons
 */
require_once('../../../config/cis.config.inc.php');
?>
if(typeof addon =='undefined')
	var addon=Array();

addon.push(
{
	init: function(page, params)
	{
		// Diese Funktion wird nach dem Laden der Seite im CIS aufgerufen

		switch(page)
		{
			case 'cis/private/lehre/lesson.php':
				AddonLVEvaluierungDisplayEvaluationInformation(params);
				break;

			default:
				break;
		}
	}
});


function AddonLVEvaluierungDisplayEvaluationInformation(params)
{
	var urlpath = '<?php echo APP_ROOT;?>addons/lvevaluierung/cis/getevaluierunginfo.php';
	urlpath += '?lehrveranstaltung_id='+params.lvid;
	urlpath += '&studiensemester_kurzbz='+params.studiensemester_kurzbz

	$.ajax({
		type: "GET",
		dataType: 'json',
		url: urlpath,
		success: function (result)
		{
			var infobox = document.getElementById('lesson_infobox_lektor');
			var data = '';

			if(result.message!='')
			{
				data = result.message;
			}
			
			infobox.innerHTML = data;
		},
		error: function(){
			alert("Error LVEvaluierung Load");
		}
	});
}
