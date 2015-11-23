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

class lvevaluierung extends basis_db
{
	private $new=true;
	public $result = array();

	public $lvevaluierung_id;
	public $startzeit;
	public $endezeit;
	public $dauer='00:10:00';
	public $studiensemester_kurzbz;
	public $lehrveranstaltung_id;

    /**
	 * Konstruktor
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Laedt eine Evaluierung
	 * @param $lvevaluierung_id
	 * @return boolean true wenn ok, false im Fehlerfall
	 */
	public function load($lvevaluierung_id)
	{
		if(!is_numeric($lvevaluierung_id))
		{
			$this->errormsg = 'ID ist ungueltig';
			return false;
		}

		$qry = "SELECT
					*
				FROM
					addon.tbl_lvevaluierung
				WHERE
					lvevaluierung_id=".$this->db_add_param($lvevaluierung_id, FHC_INTEGER);

		if($result = $this->db_query($qry))
		{
			if($row = $this->db_fetch_object($result))
			{
				$this->lvevaluierung_id = $row->lvevaluierung_id;
				$this->startzeit = $row->startzeit;
				$this->endezeit = $row->endezeit;
				$this->dauer = $row->dauer;
				$this->lehrveranstaltung_id = $row->lehrveranstaltung_id;
				$this->studiensemester_kurzbz = $row->studiensemester_kurzbz;
				$this->new=false;

				return true;
			}
			else
			{
				$this->errormsg = 'Eintrag wurde nicht gefunden';
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
	 * Speichert die Evaluierung
	 * @return boolean true wenn ok, false im Fehlerfall
	 */
	public function save()
	{
		if($this->new)
		{
			$qry = 'BEGIN;INSERT INTO addon.tbl_lvevaluierung(lehrveranstaltung_id,studiensemester_kurzbz, startzeit, endezeit, dauer) VALUES('.
					$this->db_add_param($this->lehrveranstaltung_id, FHC_INTEGER).','.
					$this->db_add_param($this->studiensemester_kurzbz).','.
					$this->db_add_param($this->startzeit).','.
					$this->db_add_param($this->endezeit).','.
					$this->db_add_param($this->dauer).');';
		}
		else
		{
			$qry = 'UPDATE addon.tbl_lvevaluierung SET '.
					' lehrveranstaltung_id='.$this->db_add_param($this->lehrveranstaltung_id, FHC_INTEGER).','.
					' studiensemester_kurzbz='.$this->db_add_param($this->studiensemester_kurzbz).','.
					' startzeit='.$this->db_add_param($this->startzeit).','.
					' endezeit='.$this->db_add_param($this->endezeit).','.
					' dauer='.$this->db_add_param($this->dauer).' '.
					' WHERE lvevaluierung_id='.$this->db_add_param($this->lvevaluierung_id, FHC_INTEGER);
		}

		if($this->db_query($qry))
		{
			if($this->new)
			{
				//naechste ID aus der Sequence holen
				$qry="SELECT currval('addon.tbl_lvevaluierung_lvevaluierung_id_seq') as id;";
				if($this->db_query($qry))
				{
					if($row = $this->db_fetch_object())
					{
						$this->lvevaluierung_id = $row->id;
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
	 * Prueft ob die Evaluierung aktuell freigeschalten ist.
	 * @param $lvevaluierung_id
	 * @return true wenn offen, false wenn geschlossen
	 */
	public function isOffen($lvevaluierung_id)
	{
		if($this->load($lvevaluierung_id))
		{
			$dtstartzeit = new DateTime($this->startzeit);
			$dtende = new DateTime($this->endezeit);
			$dtnow = new DateTime();

			if($dtstartzeit<=$dtnow && $dtende>=$dtnow)
				return true;
			else
				return false;
		}
		else
			return false;
	}

	/**
	 * Laedt Evaluierung zu einer Lehrveranstaltung / Studiensemester
	 * @param $lehrveranstaltung_id
	 * @param $studiensemester_kurzbz
	 * @return boolean
	 */
	public function getEvaluierung($lehrveranstaltung_id, $studiensemester_kurzbz)
	{
		$qry = "SELECT
				*
			FROM
				addon.tbl_lvevaluierung
			WHERE
				lehrveranstaltung_id=".$this->db_add_param($lehrveranstaltung_id, FHC_INTEGER).'
				AND studiensemester_kurzbz='.$this->db_add_param($studiensemester_kurzbz);

		if($result = $this->db_query($qry))
		{
			if($row = $this->db_fetch_object($result))
			{
				$this->lvevaluierung_id = $row->lvevaluierung_id;
				$this->startzeit = $row->startzeit;
				$this->endezeit = $row->endezeit;
				$this->dauer = $row->dauer;
				$this->lehrveranstaltung_id = $row->lehrveranstaltung_id;
				$this->studiensemester_kurzbz = $row->studiensemester_kurzbz;
				$this->new=false;

				return true;
			}
			else
			{
				$this->errormsg = 'Eintrag wurde nicht gefunden';
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
