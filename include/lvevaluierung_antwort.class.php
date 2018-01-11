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
require_once(dirname(__FILE__).'/../../../include/basis_db.class.php');
require_once(dirname(__FILE__).'/../../../include/sprache.class.php');

class lvevaluierung_antwort extends basis_db
{
	private $new=true;
	public $result = array();
	public $lvevaluierung_antwort_id;
	public $lvevaluierung_frage_id;
	public $lvevaluierung_code_id;
	public $lvevaluierung_frage_antwort_id;
	public $antwort;

    /**
	 * Konstruktor
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Laedt eine Frage
	 * @param $lvevaluierung_frage_id
	 * @return boolean
	 */
	public function load($lvevaluierung_antwort_id)
	{
		$this->errormsg = 'not implemented';
		return false;
	}


	/**
	 * Speichert eine Antwort
	 */
	public function save()
	{
		if($this->new)
		{
			$qry = "BEGIN;INSERT INTO addon.tbl_lvevaluierung_antwort(lvevaluierung_code_id,
				lvevaluierung_frage_id, lvevaluierung_frage_antwort_id, antwort) VALUES ( ".
				$this->db_add_param($this->lvevaluierung_code_id, FHC_INTEGER).','.
				$this->db_add_param($this->lvevaluierung_frage_id, FHC_INTEGER).','.
				$this->db_add_param($this->lvevaluierung_frage_antwort_id, FHC_INTEGER).','.
				$this->db_add_param($this->antwort).');';
		}
		else
		{
			$this->errormsg='update not implemented';
			return false;
		}

		if($this->db_query($qry))
		{
			if($this->new)
			{
				//naechste ID aus der Sequence holen
				$qry="SELECT currval('addon.tbl_lvevaluierung_antwort_lvevaluierung_antwort_id_seq') as id;";
				if($this->db_query($qry))
				{
					if($row = $this->db_fetch_object())
					{
						$this->lvevaluierung_antwort_id = $row->id;
						$this->db_query('COMMIT');
						return true;
					}
					else
					{
						$this->db_query('ROLLBACK');
						$this->errormsg = "Fehler beim Auslesen der Sequence";
						return false;
					}
				}
				else
				{
					$this->db_query('ROLLBACK');
					$this->errormsg = 'Fehler beim Auslesen der Sequence';
					return false;
				}
			}
			else
				return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Speichern der Daten';
			return false;
		}
	}

	/**
	 * Laedt die Antworten einer LVEvaluierung
	 * @param integer $lvevaluierung_id ID der Evaluierung, die geladen werden soll
	 * @param integer $code Optional. Code, dessen Ergebnisse geladen werden sollen
	 * @return boolean true wenn ok, false im Fehlerfall
	 */
	public function loadAntworten($lvevaluierung_id, $code_id='', $uid = '')
	{

		$qry = "SELECT
					tbl_lvevaluierung_antwort.*,
					tbl_lvevaluierung_frage_antwort.wert as wert
				FROM
					addon.tbl_lvevaluierung_code
					JOIN addon.tbl_lvevaluierung_antwort USING(lvevaluierung_code_id)
					JOIN addon.tbl_lvevaluierung_frage USING(lvevaluierung_frage_id)
					LEFT JOIN addon.tbl_lvevaluierung_frage_antwort USING(lvevaluierung_frage_antwort_id)
				WHERE
					tbl_lvevaluierung_code.lvevaluierung_id=".$this->db_add_param($lvevaluierung_id, FHC_INTEGER);
							
				if ($code_id != '')
					$qry .= " AND lvevaluierung_code_id=".$this->db_add_param($code_id, FHC_INTEGER);
                
                if ($uid != '')
					$qry .= " AND lektor_uid=".$this->db_add_param($uid, FHC_STRING);
					
				$qry .= " ORDER BY tbl_lvevaluierung_frage.sort";

		if($result = $this->db_query($qry))
		{
			while($row = $this->db_fetch_object($result))
			{
				$obj = new stdClass();
				$obj->lvevaluierung_antwort_id = $row->lvevaluierung_antwort_id;
				$obj->lvevaluierung_frage_id = $row->lvevaluierung_frage_id;
				$obj->lvevaluierung_code_id = $row->lvevaluierung_code_id;
				$obj->lvevaluierung_frage_antwort_id = $row->lvevaluierung_frage_antwort_id;
				$obj->wert = $row->wert;
				$obj->antwort = $row->antwort;

				$this->result[$row->lvevaluierung_frage_id][] = $obj;
			}
			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}
	}
}
?>
