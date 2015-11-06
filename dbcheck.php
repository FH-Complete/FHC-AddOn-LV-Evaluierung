<?php
/* Copyright (C) 2015 FH Technikum-Wien
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
 * FH-Complete Addon Evaluierung Datenbank Check
 *
 * Prueft und aktualisiert die Datenbank
 */
require_once('../../config/system.config.inc.php');
require_once('../../include/basis_db.class.php');
require_once('../../include/functions.inc.php');
require_once('../../include/benutzerberechtigung.class.php');

// Datenbank Verbindung
$db = new basis_db();

echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
        "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" href="../../skin/fhcomplete.css" type="text/css">
	<link rel="stylesheet" href="../../skin/vilesci.css" type="text/css">
	<title>Addon Datenbank Check</title>
</head>
<body>
<h1>Addon Datenbank Check</h1>';

$uid = get_uid();
$rechte = new benutzerberechtigung();
$rechte->getBerechtigungen($uid);

if(!$rechte->isBerechtigt('basis/addon'))
{
	exit('Sie haben keine Berechtigung für die Verwaltung von Addons');
}

echo '<h2>Aktualisierung der Datenbank</h2>';

// Code fuer die Datenbankanpassungen

//Neue Berechtigung für das Addon hinzufügen
if($result = $db->db_query("SELECT * FROM system.tbl_berechtigung WHERE berechtigung_kurzbz='addon/lvevaluierung'"))
{
	if($db->db_num_rows($result)==0)
	{
		$qry = "INSERT INTO system.tbl_berechtigung(berechtigung_kurzbz, beschreibung) 
				VALUES('addon/lvevaluierung','AddOn LVEvaluierung');";

		if(!$db->db_query($qry))
			echo '<strong>Berechtigung: '.$db->db_last_error().'</strong><br>';
		else 
			echo 'Neue Berechtigung addon/lvevaluierung hinzugefuegt!<br>';
	}
}

if(!$result = @$db->db_query("SELECT 1 FROM addon.tbl_lvevaluierung"))
{

	$qry = "CREATE TABLE addon.tbl_lvevaluierung
			(
				lvevaluierung_id bigint NOT NULL,
				lehrveranstaltung_id integer NOT NULL,
				studiensemester_kurzbz varchar(16) NOT NULL,
				startzeit timestamp,
				endezeit timestamp,
				dauer time
			);

			COMMENT ON TABLE addon.tbl_lvevaluierung IS 'Lehrveranstaltung Evaluierung';

			ALTER TABLE addon.tbl_lvevaluierung ADD CONSTRAINT pk_addon_lvevaluierung_lvevaluierung_id PRIMARY KEY (lvevaluierung_id);
			CREATE SEQUENCE addon.tbl_lvevaluierung_lvevaluierung_id_seq
			INCREMENT BY 1
			NO MAXVALUE
			NO MINVALUE
			CACHE 1;

			ALTER TABLE addon.tbl_lvevaluierung ALTER COLUMN lvevaluierung_id SET DEFAULT nextval('addon.tbl_lvevaluierung_lvevaluierung_id_seq');
			ALTER TABLE addon.tbl_lvevaluierung ADD CONSTRAINT fk_addon_lvevaluierung_lvevaluierung_id FOREIGN KEY (lehrveranstaltung_id) REFERENCES lehre.tbl_lehrveranstaltung(lehrveranstaltung_id) ON DELETE RESTRICT ON UPDATE CASCADE;
			ALTER TABLE addon.tbl_lvevaluierung ADD CONSTRAINT fk_addon_lvevaluierung_studiensemester_kurzbz FOREIGN KEY (studiensemester_kurzbz) REFERENCES public.tbl_studiensemester(studiensemester_kurzbz) ON DELETE RESTRICT ON UPDATE CASCADE;

			CREATE TABLE addon.tbl_lvevaluierung_code
			(
				lvevaluierung_code_id bigint NOT NULL,
				code varchar(32) NOT NULL,
				startzeit timestamp,
				endezeit timestamp,
				lvevaluierung_id bigint NOT NULL
			);

			COMMENT ON TABLE addon.tbl_lvevaluierung_code IS 'Lehrveranstaltung Evaluierung Zugangscodes';

            ALTER TABLE addon.tbl_lvevaluierung_code ADD CONSTRAINT uk_addon_lvevaluierung_code UNIQUE (code);
			ALTER TABLE addon.tbl_lvevaluierung_code ADD CONSTRAINT pk_addon_lvevaluierung_code_lvevaluierung_code_id PRIMARY KEY (lvevaluierung_code_id);
			CREATE SEQUENCE addon.tbl_lvevaluierung_code_lvevaluierung_code_id_seq
			INCREMENT BY 1
			NO MAXVALUE
			NO MINVALUE
			CACHE 1;

			ALTER TABLE addon.tbl_lvevaluierung_code ALTER COLUMN lvevaluierung_code_id SET DEFAULT nextval('addon.tbl_lvevaluierung_code_lvevaluierung_code_id_seq');
			ALTER TABLE addon.tbl_lvevaluierung_code ADD CONSTRAINT fk_addon_lvevaluierung_code_lvevaluierung_id FOREIGN KEY (lvevaluierung_id) REFERENCES addon.tbl_lvevaluierung(lvevaluierung_id) ON DELETE RESTRICT ON UPDATE CASCADE;

			CREATE TABLE addon.tbl_lvevaluierung_frage
			(
				lvevaluierung_frage_id bigint NOT NULL,
				typ varchar(32) NOT NULL,
				bezeichnung text[],
				aktiv boolean NOT NULL DEFAULT true,
				sort smallint
			);

			COMMENT ON TABLE addon.tbl_lvevaluierung_frage IS 'Lehrveranstaltung Evaluierung Fragen';

			ALTER TABLE addon.tbl_lvevaluierung_frage ADD CONSTRAINT pk_addon_lvevaluierung_frage_lvevaluierung_frage_id PRIMARY KEY (lvevaluierung_frage_id);
			CREATE SEQUENCE addon.tbl_lvevaluierung_frage_lvevaluierung_frage_id_seq
			INCREMENT BY 1
			NO MAXVALUE
			NO MINVALUE
			CACHE 1;

			ALTER TABLE addon.tbl_lvevaluierung_frage ALTER COLUMN lvevaluierung_frage_id SET DEFAULT nextval('addon.tbl_lvevaluierung_frage_lvevaluierung_frage_id_seq');

			CREATE TABLE addon.tbl_lvevaluierung_frage_antwort
			(
				lvevaluierung_frage_antwort_id bigint NOT NULL,
				lvevaluierung_frage_id bigint NOT NULL,
				bezeichnung varchar(256)[],
				sort smallint,
				wert integer
			);

			COMMENT ON TABLE addon.tbl_lvevaluierung_frage_antwort IS 'Lehrveranstaltung Evaluierung Antwortmoeglichkeiten';

			ALTER TABLE addon.tbl_lvevaluierung_frage_antwort ADD CONSTRAINT pk_addon_lvevaluierung_frage_antwort_lvevaluierung_frage_antwort_id PRIMARY KEY (lvevaluierung_frage_antwort_id);
			CREATE SEQUENCE addon.tbl_lvevaluierung_frage_antwort_lvevaluierung_frage_antwort_id_seq
			INCREMENT BY 1
			NO MAXVALUE
			NO MINVALUE
			CACHE 1;

			ALTER TABLE addon.tbl_lvevaluierung_frage_antwort ALTER COLUMN lvevaluierung_frage_antwort_id SET DEFAULT nextval('addon.tbl_lvevaluierung_frage_antwort_lvevaluierung_frage_antwort_id_seq');
			ALTER TABLE addon.tbl_lvevaluierung_frage_antwort ADD CONSTRAINT fk_addon_lvevaluierung_frage_antwort_lvevaluierung_frage_id FOREIGN KEY (lvevaluierung_frage_id) REFERENCES addon.tbl_lvevaluierung_frage(lvevaluierung_frage_id) ON DELETE RESTRICT ON UPDATE CASCADE;

			CREATE TABLE addon.tbl_lvevaluierung_antwort
			(
				lvevaluierung_antwort_id bigint NOT NULL,
				lvevaluierung_code_id bigint NOT NULL,
				lvevaluierung_frage_id bigint NOT NULL,
				lvevaluierung_frage_antwort_id bigint,
				antwort text
			);

			COMMENT ON TABLE addon.tbl_lvevaluierung_antwort IS 'Lehrveranstaltung Evaluierung Antworten';

			ALTER TABLE addon.tbl_lvevaluierung_antwort ADD CONSTRAINT pk_addon_lvevaluierung_antwort_lvevaluierung_antwort_id PRIMARY KEY (lvevaluierung_antwort_id);
			CREATE SEQUENCE addon.tbl_lvevaluierung_antwort_lvevaluierung_antwort_id_seq
			INCREMENT BY 1
			NO MAXVALUE
			NO MINVALUE
			CACHE 1;

			ALTER TABLE addon.tbl_lvevaluierung_antwort ALTER COLUMN lvevaluierung_antwort_id SET DEFAULT nextval('addon.tbl_lvevaluierung_antwort_lvevaluierung_antwort_id_seq');
			ALTER TABLE addon.tbl_lvevaluierung_antwort ADD CONSTRAINT fk_addon_lvevaluierung_antwort_lvevaluierung_code_id FOREIGN KEY (lvevaluierung_code_id) REFERENCES addon.tbl_lvevaluierung_code(lvevaluierung_code_id) ON DELETE RESTRICT ON UPDATE CASCADE;
			ALTER TABLE addon.tbl_lvevaluierung_antwort ADD CONSTRAINT fk_addon_lvevaluierung_antwort_lvevaluierung_frage_id FOREIGN KEY (lvevaluierung_frage_id) REFERENCES addon.tbl_lvevaluierung_frage(lvevaluierung_frage_id) ON DELETE RESTRICT ON UPDATE CASCADE;
			ALTER TABLE addon.tbl_lvevaluierung_antwort ADD CONSTRAINT fk_addon_lvevaluierung_antwort_lvevaluierung_frage_antwort_id FOREIGN KEY (lvevaluierung_frage_antwort_id) REFERENCES addon.tbl_lvevaluierung_frage_antwort(lvevaluierung_frage_antwort_id) ON DELETE RESTRICT ON UPDATE CASCADE;

			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_lvevaluierung TO web;
			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_lvevaluierung TO vilesci;
			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_lvevaluierung_code TO web;
			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_lvevaluierung_code TO vilesci;
			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_lvevaluierung_frage TO vilesci;
			GRANT SELECT ON addon.tbl_lvevaluierung_frage TO web;
			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_lvevaluierung_frage_antwort TO vilesci;
			GRANT SELECT ON addon.tbl_lvevaluierung_frage_antwort TO vilesci;
			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_lvevaluierung_antwort TO vilesci;
			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_lvevaluierung_antwort TO web;
			";

	if(!$db->db_query($qry))
		echo '<strong>LV-Evaluierung: '.$db->db_last_error().'</strong><br>';
	else 
		echo ' LV-Evaluierung: Basistabellen hinzugefuegt!<br>';

}

echo '<br>Aktualisierung abgeschlossen<br><br>';
echo '<h2>Gegenprüfung</h2>';


// Liste der verwendeten Tabellen / Spalten des Addons
$tabellen=array(
	"addon.tbl_lvevaluierung"  => array("lvevaluierung_id","lehrveranstaltung_id","studiensemester_kurzbz","startzeit","endezeit","dauer"),
	"addon.tbl_lvevaluierung_code"  => array("lvevaluierung_code_id","code","startzeit","endezeit","lvevaluierung_id"),
	"addon.tbl_lvevaluierung_frage"  => array("lvevaluierung_frage_id","typ","bezeichnung","aktiv","sort"),
	"addon.tbl_lvevaluierung_frage_antwort"  => array("lvevaluierung_frage_antwort_id","lvevaluierung_frage_id","bezeichnung","sort","wert"),
	"addon.tbl_lvevaluierung_antwort"  => array("lvevaluierung_antwort_id","lvevaluierung_code_id","lvevaluierung_frage_id","lvevaluierung_frage_antwort_id","antwort"),
);


$tabs=array_keys($tabellen);
$i=0;
foreach ($tabellen AS $attribute)
{
	$sql_attr='';
	foreach($attribute AS $attr)
		$sql_attr.=$attr.',';
	$sql_attr=substr($sql_attr, 0, -1);

	if (!@$db->db_query('SELECT '.$sql_attr.' FROM '.$tabs[$i].' LIMIT 1;'))
		echo '<BR><strong>'.$tabs[$i].': '.$db->db_last_error().' </strong><BR>';
	else
		echo $tabs[$i].': OK - ';
	flush();
	$i++;
}
?>
