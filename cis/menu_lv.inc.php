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
 */
/**
 * Hinzufuegen von neuen Menuepunkten bei CIS Lehrveranstaltungen
 */

if($is_lector)
{
	$menu[]=array
	(
		'id'=>'addon_lvevaluierung_menu_lvevaluierung',
		'position'=>'130',
		'name'=>$p->t('lvevaluierung/lvevaluierung'),
		'icon'=>'../../../addons/lvevaluierung/skin/images/button_lvevaluierung.png',
		'link'=>'../../../addons/lvevaluierung/cis/administration.php?lehrveranstaltung_id='.urlencode($lvid).'&studiensemester_kurzbz='.urlencode($angezeigtes_stsem),
		'text'=>''
	);
}
?>
