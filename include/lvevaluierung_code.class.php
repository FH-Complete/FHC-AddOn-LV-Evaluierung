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

class lvevaluierung_code extends basis_db
{
	private $new=true;
	public $result = array();

	public $lvevaluierung_id;
	public $code;
	public $startzeit;
	public $endezeit;
    public $lektor_uid = '';

    /**
	 * Konstruktor
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Laedt einen Code anhand der ID
	 * @param $lvevaluierung_code_id
	 * @return boolean true wenn ok, false im Fehlerfall
	 */
	public function load($lvevaluierung_code_id)
	{
		$qry = "SELECT * FROM addon.tbl_lvevaluierung_code WHERE lvevaluierung_code_id=".$this->db_add_param($lvevaluierung_code_id, FHC_INTEGER);

		if($result = $this->db_query($qry))
		{
			if($row = $this->db_fetch_object($result))
			{
				$this->lvevaluierung_code_id = $row->lvevaluierung_code_id;
				$this->code = $row->code;
				$this->startzeit = $row->startzeit;
				$this->endezeit = $row->endezeit;
				$this->lvevaluierung_id = $row->lvevaluierung_id;
                $this->lektor_uid = $row->lektor_uid;
				$this->new=false;
				return true;
			}
			else
			{
				$this->errormsg = 'ID ist ungueltig';
				return false;
			}
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}
	}

	/**
	 * Laedt einen Code-Datensatz
	 * @param $code
	 * @return boolean true wenn ok, false im Fehlerfall
	 */
	public function getCode($code)
	{
		$qry = "SELECT * FROM addon.tbl_lvevaluierung_code WHERE code=".$this->db_add_param($code);

		if($result = $this->db_query($qry))
		{
			if($row = $this->db_fetch_object($result))
			{
				$this->lvevaluierung_code_id = $row->lvevaluierung_code_id;
				$this->code = $row->code;
				$this->startzeit = $row->startzeit;
				$this->endezeit = $row->endezeit;
				$this->lvevaluierung_id = $row->lvevaluierung_id;
                $this->lektor_uid = $row->lektor_uid;
				$this->new=false;
				return true;
			}
			else
			{
				$this->errormsg = 'Code ist ungueltig';
				return false;
			}
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}
	}

	/**
	 * Speichert eine Code
	 * @return boolean true wenn ok, false im Fehlerfall
	 */
	public function save()
	{
		if($this->new)
		{
			$qry = "BEGIN;INSERT INTO addon.tbl_lvevaluierung_code(code, startzeit, endezeit, lvevaluierung_id, lektor_uid) VALUES(".
				$this->db_add_param($this->code).','.
				$this->db_add_param($this->startzeit).','.
				$this->db_add_param($this->endezeit).','.
				$this->db_add_param($this->lvevaluierung_id, FHC_INTEGER).',' .
                $this->db_add_param($this->lektor_uid).');';
		}
		else
		{
			$qry = 'UPDATE addon.tbl_lvevaluierung_code SET '.
					'code='.$this->db_add_param($this->code).','.
					'startzeit='.$this->db_add_param($this->startzeit).','.
					'endezeit='.$this->db_add_param($this->endezeit).','.
					'lvevaluierung_id='.$this->db_add_param($this->lvevaluierung_id, FHC_INTEGER).','.
                    'lektor_uid='.$this->db_add_param($this->lektor_uid).'
					WHERE lvevaluierung_code_id='.$this->db_add_param($this->lvevaluierung_code_id, FHC_INTEGER);
		}
		if($this->db_query($qry))
		{
			if($this->new)
			{
				//naechste ID aus der Sequence holen
				$qry="SELECT currval('addon.tbl_lvevaluierung_code_lvevaluierung_code_id_seq') as id;";
				if($this->db_query($qry))
				{
					if($row = $this->db_fetch_object())
					{
						$this->lvevaluierung_code_id = $row->id;
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
			{
				return true;
			}
		}
		else
		{
			$this->errormsg = 'Fehler beim Speichern der Daten';
			return false;
		}
	}

	/**
	 * Laedt Codes zu einer Evaluierung
	 * @param $lvevaluierung_id
	 * @return boolean true wenn ok, false im Fehlerfall
	 */
	public function loadCodes($lvevaluierung_id)
	{
		$qry = 'SELECT * FROM addon.tbl_lvevaluierung_code
				WHERE lvevaluierung_id='.$this->db_add_param($lvevaluierung_id, FHC_INTEGER);

		if($result = $this->db_query($qry))
		{
			while($row = $this->db_fetch_object($result))
			{
				$obj = new stdClass();

				$obj->lvevaluierung_code_id = $row->lvevaluierung_code_id;
				$obj->code = $row->code;
				$obj->startzeit = $row->startzeit;
				$obj->endezeit = $row->endezeit;
				$obj->lvevaluierung_id = $row->lvevaluierung_id;
                $this->lektor_uid = $row->lektor_uid;
				$obj->new=false;

				$this->result[] = $obj;
			}
			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}
	}

	/**
	 * Generiert Zugangscodes fuer eine Evaluierung
	 * @param $lvevaluierung_id
	 * @return boolean true wenn ok, false im Fehlerfall
	 */
	public function generateCodes($lvevaluierung_id)
	{
		$lvevaluierung = new lvevaluierung();
		if(!$lvevaluierung->load($lvevaluierung_id))
			return false;

		$lv = new lehrveranstaltung();
		$teilnehmer = $lv->getStudentsOfLv($lvevaluierung->lehrveranstaltung_id, $lvevaluierung->studiensemester_kurzbz);
		$anzahl_studierende=numberOfElements($teilnehmer);

		$this->loadCodes($lvevaluierung_id);
		if(numberOfElements($this->result)>=$anzahl_studierende)
			return true;
		else
		{
			$anzahl_codes = $anzahl_studierende - numberOfElements($this->result);

			for($i=0;$i<$anzahl_codes;$i++)
			{
				$code = new lvevaluierung_code();
				$code->code = $this->getUniqueCode();
				$code->lvevaluierung_id = $lvevaluierung_id;
				$code->save();
			}
			return true;
		}

		return true;
	}

	/**
	 * Liefert einen noch nicht vorhandenen eindeutigen Code
	 * @return code
	 */
	protected function getUniqueCode()
	{
		$found=false;
		while(!$found)
		{

			$possibleChars = "123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ";
		    $code = '';

		    for($i = 0; $i < 11; $i++)
			{
		        $rand = rand(0, strlen($possibleChars) - 1);
		        $code .= substr($possibleChars, $rand, 1);
		    }

			if($this->exists($code)===false)
				$found=true;
		}
		return $code;
	}

	/**
	 * Prueft ob eine Code bereits vorhanden ist
	 * @param $code Code
	 * @return false wenn code nicht vorhanden ist, ID des Codes wenn vorhanden
	 */
	public function exists($code)
	{
		$qry = "SELECT lvevaluierung_code_id FROM addon.tbl_lvevaluierung_code WHERE code=".$this->db_add_param($code);

		if($result = $this->db_query($qry))
		{
			if($row = $this->db_fetch_object($result))
			{
				return $row->lvevaluierung_code_id;
			}
			else
			{
				return false;
			}
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}
	}
}
?>
