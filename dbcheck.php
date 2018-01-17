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

if(!$rechte->isBerechtigt('basis/addon', null, 'suid'))
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
			GRANT SELECT ON addon.tbl_lvevaluierung_frage_antwort TO web;
			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_lvevaluierung_antwort TO vilesci;
			GRANT SELECT, INSERT, UPDATE, DELETE ON addon.tbl_lvevaluierung_antwort TO web;

			GRANT SELECT, UPDATE ON addon.tbl_lvevaluierung_antwort_lvevaluierung_antwort_id_seq TO web;
			GRANT SELECT, UPDATE ON addon.tbl_lvevaluierung_antwort_lvevaluierung_antwort_id_seq TO vilesci;

			GRANT SELECT, UPDATE ON addon.tbl_lvevaluierung_frage_antwort_lvevaluierung_frage_antwort_id_seq TO vilesci;
			GRANT SELECT, UPDATE ON addon.tbl_lvevaluierung_frage_lvevaluierung_frage_id_seq TO vilesci;

			GRANT SELECT, UPDATE ON addon.tbl_lvevaluierung_code_lvevaluierung_code_id_seq TO vilesci;
			GRANT SELECT, UPDATE ON addon.tbl_lvevaluierung_code_lvevaluierung_code_id_seq TO web;
			GRANT SELECT, UPDATE ON addon.tbl_lvevaluierung_lvevaluierung_id_seq TO vilesci;
			GRANT SELECT, UPDATE ON addon.tbl_lvevaluierung_lvevaluierung_id_seq TO web;
			";

	if(!$db->db_query($qry))
		echo '<strong>LV-Evaluierung: '.$db->db_last_error().'</strong><br>';
	else
		echo ' LV-Evaluierung: Basistabellen hinzugefuegt!<br>';

}

if($result = $db->db_query("SELECT * FROM public.tbl_vorlage WHERE vorlage_kurzbz='LVEvalCode'"))
{
	if($db->db_num_rows($result)==0)
	{
        $qry_oe = "SELECT oe_kurzbz FROM public.tbl_organisationseinheit WHERE oe_parent_kurzbz is null";
        if($result = $db->db_query($qry_oe))
        {
            $qry = "INSERT INTO public.tbl_vorlage(vorlage_kurzbz, bezeichnung, anmerkung,mimetype)
            VALUES('LVEvalCode','LVEvaluierung Codes', 'LVEVaaluierung Codes', 'application/vnd.oasis.opendocument.text');";

            $text = file_get_contents('system/xsl/lvevalcode.xml');
            $style = file_get_contents('system/xsl/lvevalcode_style.xml');

            while($row = $db->db_fetch_object($result))
            {
                $qry.="INSERT INTO public.tbl_vorlagestudiengang(vorlage_kurzbz, studiengang_kz, version, text,
                        oe_kurzbz, style, berechtigung, anmerkung_vorlagestudiengang, aktiv) VALUES(
                        'LVEvalCode',0,0,".$db->db_add_param($text).",".$db->db_add_param($row->oe_kurzbz).",".
                        $db->db_add_param($style).",null,'',true);";
            }
        }

		if(!$db->db_query($qry))
			echo '<strong>LVEvalCode Dokumentenvorlage: '.$db->db_last_error().'</strong><br>';
		else
			echo 'LVEvalCode Dokumentenvorlage hinzugefuegt<br>';
	}
}

if(!@$db->db_query("SELECT codes_ausgegeben FROM addon.tbl_lvevaluierung LIMIT 1"))
{
	$qry = "ALTER TABLE addon.tbl_lvevaluierung ADD COLUMN codes_ausgegeben smallint;";

	if(!$db->db_query($qry))
		echo '<strong>tbl_lvevaluierung.codes_ausgegeben: '.$db->db_last_error().'</strong><br>';
	else
		echo 'Neue Spalte codes_ausgegeben in tbl_lvevaluierung hinzugefuegt<br>';
}

if(!@$db->db_query("SELECT 1 FROM addon.tbl_lvevaluierung_selbstevaluierung LIMIT 1"))
{
	$qry = "CREATE TABLE addon.tbl_lvevaluierung_selbstevaluierung
        (
            lvevaluierung_selbstevaluierung_id integer,
            lvevaluierung_id integer,
            uid varchar(32),
            freigegeben boolean NOT NULL DEFAULT false,
            gruppe text,
            persoenlich text,
            entwicklung text,
            weiterbildung text,
            insertamum timestamp,
            insertvon varchar(32),
            updateamum timestamp,
            updatevon varchar(32)
        );

        ALTER TABLE addon.tbl_lvevaluierung_selbstevaluierung ADD CONSTRAINT pk_addon_lvevaluierung_selbstevaluierung_lvevaluierung_selbstevaluierung_id PRIMARY KEY (lvevaluierung_selbstevaluierung_id);

    	CREATE SEQUENCE addon.tbl_lvevaluierung_selbstevaluierung_lvevaluierung_selbstevaluierung_id_seq
    	INCREMENT BY 1
    	NO MAXVALUE
    	NO MINVALUE
    	CACHE 1;

        ALTER TABLE addon.tbl_lvevaluierung_selbstevaluierung ALTER COLUMN lvevaluierung_selbstevaluierung_id SET DEFAULT nextval('addon.tbl_lvevaluierung_selbstevaluierung_lvevaluierung_selbstevaluierung_id_seq');
        ALTER TABLE addon.tbl_lvevaluierung_selbstevaluierung ADD CONSTRAINT fk_addon_lvevaluierung_selbstevaluierung_lvevaluierung_id FOREIGN KEY (lvevaluierung_id) REFERENCES addon.tbl_lvevaluierung(lvevaluierung_id) ON DELETE RESTRICT ON UPDATE CASCADE;
        ALTER TABLE addon.tbl_lvevaluierung_selbstevaluierung ADD CONSTRAINT fk_addon_lvevaluierung_selbstevaluierung_uid FOREIGN KEY (uid) REFERENCES public.tbl_benutzer(uid) ON DELETE RESTRICT ON UPDATE CASCADE;

        GRANT SELECT, UPDATE, INSERT, DELETE ON addon.tbl_lvevaluierung_selbstevaluierung TO web;
        GRANT SELECT, UPDATE, INSERT, DELETE ON addon.tbl_lvevaluierung_selbstevaluierung TO vilesci;
        GRANT SELECT, UPDATE ON addon.tbl_lvevaluierung_selbstevaluierung_lvevaluierung_selbstevaluierung_id_seq TO web;
        GRANT SELECT, UPDATE ON addon.tbl_lvevaluierung_selbstevaluierung_lvevaluierung_selbstevaluierung_id_seq TO vilesci;

        ";

	if(!$db->db_query($qry))
		echo '<strong>tbl_lvevaluierung_selbstevaluierung: '.$db->db_last_error().'</strong><br>';
	else
		echo 'Neue Tabelle tbl_lvevaluierung_selbstevaluierung hinzugefuegt<br>';
}

// Spalten insertamum, insertvon, updateamum, updatevon in addon.tbl_lvevaluierung
if(!@$db->db_query("SELECT insertamum FROM addon.tbl_lvevaluierung LIMIT 1"))
{
	$qry = "ALTER TABLE addon.tbl_lvevaluierung ADD COLUMN insertamum timestamp;
			ALTER TABLE addon.tbl_lvevaluierung ADD COLUMN insertvon varchar(32);
			ALTER TABLE addon.tbl_lvevaluierung ADD COLUMN updateamum timestamp;
			ALTER TABLE addon.tbl_lvevaluierung ADD COLUMN updatevon varchar(32);";

	if(!$db->db_query($qry))
		echo '<strong>tbl_lvevaluierung.insertamum: '.$db->db_last_error().'</strong><br>';
		else
			echo 'Neue Spalten insertamum, insertvon, updateamum, updatevon in addon.tbl_lvevaluierung hinzugefuegt<br>';
}

// Spalte verpflichtend in addon.tbl_lvevaluierung
if(!@$db->db_query("SELECT verpflichtend FROM addon.tbl_lvevaluierung LIMIT 1"))
{
	$qry = "ALTER TABLE addon.tbl_lvevaluierung ADD COLUMN verpflichtend boolean NOT NULL DEFAULT false;";

	if(!$db->db_query($qry))
		echo '<strong>tbl_lvevaluierung.verpflichtend: '.$db->db_last_error().'</strong><br>';
		else
			echo 'Neue Spalte verpflichtend in addon.tbl_lvevaluierung hinzugefuegt<br>';
}

// Spalte weiterbildung_bedarf in addon.tbl_lvevaluierung_selbstevaluierung
if(!@$db->db_query("SELECT weiterbildung_bedarf FROM addon.tbl_lvevaluierung_selbstevaluierung LIMIT 1"))
{
	$qry = "ALTER TABLE addon.tbl_lvevaluierung_selbstevaluierung ADD COLUMN weiterbildung_bedarf boolean DEFAULT NULL;";

	if(!$db->db_query($qry))
		echo '<strong>tbl_lvevaluierung_selbstevaluierung.weiterbildung_bedarf: '.$db->db_last_error().'</strong><br>';
	else
		echo 'Neue Spalte weiterbildung_bedarf in addon.tbl_lvevaluierung_selbstevaluierung hinzugefuegt<br>';
}

//Neue Berechtigung für das Addon hinzufügen
if($result = $db->db_query("SELECT * FROM system.tbl_berechtigung WHERE berechtigung_kurzbz='addon/lvevaluierung_mail'"))
{
	if($db->db_num_rows($result)==0)
	{
		$qry = "INSERT INTO system.tbl_berechtigung(berechtigung_kurzbz, beschreibung)
				VALUES('addon/lvevaluierung_mail','AddOn LVEvaluierung - zusätzlicher Mailempfänger für die OE');";

		if(!$db->db_query($qry))
			echo '<strong>Berechtigung: '.$db->db_last_error().'</strong><br>';
		else
			echo 'Neue Berechtigung addon/lvevaluierung_mail hinzugefuegt!<br>';
	}
}


//CREATE TABLE tbl_lvevaluierung_jahresabschluss
if(!@$db->db_query("SELECT 1 FROM addon.tbl_lvevaluierung_jahresabschluss LIMIT 1"))
{
	$qry = "CREATE TABLE addon.tbl_lvevaluierung_jahresabschluss
        (
            lvevaluierung_jahresabschluss_id integer,
            oe_kurzbz varchar (32),
            studienjahr_kurzbz varchar (16),
            ergebnisse text,
            verbesserungen text,
            freigegeben boolean NOT NULL DEFAULT false,
            insertamum timestamp DEFAULT now(),
            insertvon varchar(32),
            updateamum timestamp DEFAULT now(),
            updatevon varchar(32)
        );

        ALTER TABLE addon.tbl_lvevaluierung_jahresabschluss ADD CONSTRAINT pk_addon_lvevaluierung_jahresabschluss_lvevaluierung_jahresabschluss_id PRIMARY KEY (lvevaluierung_jahresabschluss_id);

    	CREATE SEQUENCE addon.tbl_lvevaluierung_jahresabschluss_lvevaluierung_jahresabschluss_id_seq
    	INCREMENT BY 1
    	NO MAXVALUE
    	NO MINVALUE
    	CACHE 1;

        ALTER TABLE addon.tbl_lvevaluierung_jahresabschluss ALTER COLUMN lvevaluierung_jahresabschluss_id SET DEFAULT nextval('addon.tbl_lvevaluierung_jahresabschluss_lvevaluierung_jahresabschluss_id_seq');
        ALTER TABLE addon.tbl_lvevaluierung_jahresabschluss ADD CONSTRAINT fk_addon_lvevaluierung_jahresabschluss_oe_kurzbz FOREIGN KEY (oe_kurzbz) REFERENCES public.tbl_organisationseinheit(oe_kurzbz) ON DELETE RESTRICT ON UPDATE CASCADE;
        ALTER TABLE addon.tbl_lvevaluierung_jahresabschluss ADD CONSTRAINT fk_addon_lvevaluierung_jahresabschluss_studienjahr_kurzbz FOREIGN KEY (studienjahr_kurzbz) REFERENCES public.tbl_studienjahr(studienjahr_kurzbz) ON DELETE RESTRICT ON UPDATE CASCADE;

        GRANT SELECT, UPDATE, INSERT, DELETE ON addon.tbl_lvevaluierung_jahresabschluss TO web;
        GRANT SELECT, UPDATE, INSERT, DELETE ON addon.tbl_lvevaluierung_jahresabschluss TO vilesci;
        GRANT SELECT, UPDATE ON addon.tbl_lvevaluierung_jahresabschluss_lvevaluierung_jahresabschluss_id_seq TO web;
        GRANT SELECT, UPDATE ON addon.tbl_lvevaluierung_jahresabschluss_lvevaluierung_jahresabschluss_id_seq TO vilesci;

        ";

	if(!$db->db_query($qry))
		echo '<strong>tbl_lvevaluierung_jahresabschluss: '.$db->db_last_error().'</strong><br>';
	else
		echo 'Neue Tabelle tbl_lvevaluierung_jahresabschluss hinzugefuegt<br>';
}


//Neue Berechtigung für das Addon hinzufügen
if($result = $db->db_query("SELECT * FROM system.tbl_berechtigung WHERE berechtigung_kurzbz='addon/lvevaluierung_rektorat'"))
{
	if($db->db_num_rows($result) == 0)
	{
		$qry = "INSERT INTO system.tbl_berechtigung(berechtigung_kurzbz, beschreibung)
				VALUES('addon/lvevaluierung_rektorat','AddOn LVEvaluierung - Rechte für Rektorat');";

		if(!$db->db_query($qry))
			echo '<strong>Berechtigung: '.$db->db_last_error().'</strong><br>';
		else
			echo 'Neue Berechtigung addon/lvevaluierung_rektorat hinzugefuegt!<br>';
	}
}


// Spalte lv_aufgeteilt in addon.tbl_lvevaluierung
if(!@$db->db_query("SELECT lv_aufgeteilt FROM addon.tbl_lvevaluierung LIMIT 1"))
{
	$qry = "ALTER TABLE addon.tbl_lvevaluierung ADD COLUMN lv_aufgeteilt boolean NOT NULL DEFAULT false;";

	if(!$db->db_query($qry))
		echo '<strong>tbl_lvevaluierung.lv_aufgeteilt: '.$db->db_last_error().'</strong><br>';
		else
			echo 'Neue Spalte lv_aufgeteilt in addon.tbl_lvevaluierung hinzugefuegt<br>';
}

// Spalte lektor_uid in addon.tbl_lvevaluierung_code
if(!@$db->db_query("SELECT lektor_uid FROM addon.tbl_lvevaluierung_code LIMIT 1"))
{
	$qry = "ALTER TABLE addon.tbl_lvevaluierung_code ADD COLUMN lektor_uid varchar(32);
            ALTER TABLE addon.tbl_lvevaluierung_code ADD CONSTRAINT fk_lvevaluierung_code_lektor_uid FOREIGN KEY (lektor_uid) REFERENCES public.tbl_benutzer(uid) ON DELETE RESTRICT ON UPDATE CASCADE;";

	if(!$db->db_query($qry))
		echo '<strong>tbl_lvevaluierung_code.lektor_uid: '.$db->db_last_error().'</strong><br>';
    else
        echo 'Neue Spalte lektor_uid in addon.tbl_lvevaluierung_code hinzugefuegt<br>';
}


echo '<br>Aktualisierung abgeschlossen<br><br>';
echo '<h2>Gegenprüfung</h2>';


// Liste der verwendeten Tabellen / Spalten des Addons
$tabellen=array(
	"addon.tbl_lvevaluierung"  => array("lvevaluierung_id","lehrveranstaltung_id","studiensemester_kurzbz","startzeit","endezeit","dauer","codes_ausgegeben","insertamum","insertvon","updateamum","updatevon","verpflichtend","lv_isaufgeteilt"),
	"addon.tbl_lvevaluierung_code"  => array("lvevaluierung_code_id","code","startzeit","endezeit","lvevaluierung_id"),
	"addon.tbl_lvevaluierung_frage"  => array("lvevaluierung_frage_id","typ","bezeichnung","aktiv","sort"),
	"addon.tbl_lvevaluierung_frage_antwort"  => array("lvevaluierung_frage_antwort_id","lvevaluierung_frage_id","bezeichnung","sort","wert"),
	"addon.tbl_lvevaluierung_antwort"  => array("lvevaluierung_antwort_id","lvevaluierung_code_id","lvevaluierung_frage_id","lvevaluierung_frage_antwort_id","antwort"),

	"addon.tbl_lvevaluierung_selbstevaluierung" => array("lvevaluierung_selbstevaluierung_id","lvevaluierung_id","uid","freigegeben","persoenlich","gruppe","entwicklung","weiterbildung_bedarf", "weiterbildung"),
    "addon.tbl_lvevaluierung_jahresabschluss" => array("lvevaluierung_jahresabschluss_id", "oe_kurzbz", "studienjahr_kurzbz", "ergebnisse", "verbesserungen", "freigegeben", "insertamum", "insertvon", "updateamum", "updatevon")
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
