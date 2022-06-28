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
	public $new=true;
	public $result = array();

	public $lvevaluierung_id;
	public $startzeit;
	public $endezeit;
	public $dauer='00:30:00';
	public $studiensemester_kurzbz;
	public $lehrveranstaltung_id;
	public $codes_ausgegeben;
	public $insertamum;
	public $insertvon;
	public $updateamum;
	public $updatevon;
	public $verpflichtend=false;
	public $lv_aufgeteilt=false;
	public $codes_gemailt = false;

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
				$this->codes_ausgegeben = $row->codes_ausgegeben;
				$this->insertamum = $row->insertamum;
				$this->insertvon = $row->insertvon;
				$this->updateamum = $row->updateamum;
				$this->updatevon = $row->updatevon;
				$this->verpflichtend = $this->db_parse_bool($row->verpflichtend);
                $this->lv_aufgeteilt = $this->db_parse_bool($row->lv_aufgeteilt);
                $this->codes_gemailt = $this->db_parse_bool($row->codes_gemailt);

				$this->new = false;

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

	public function validate()
	{
		$dauer = explode(':',$this->dauer);

		if(isset($dauer[0]) && $dauer[0]>23)
		{
			$this->errormsg = 'Stunde darf nicht groesser als 23 sein';
			return false;
		}
		if(isset($dauer[1]) && $dauer[1]>59)
		{
			$this->errormsg = 'Minute darf nicht groesser als 59 sein';
			return false;
		}
		if(isset($dauer[2]) && $dauer[2]>59)
		{
			$this->errormsg = 'Sekunde darf nicht groesser als 59 sein';
			return false;
		}

		if($this->codes_ausgegeben!='')
		{
			if(!is_numeric($this->codes_ausgegeben))
			{
				$this->errormsg = 'Die Anzahl ausgegebener Codes muss eine gültige Zahl sein';
				return false;
			}
		}
		return true;
	}

	/**
	 * Speichert die Evaluierung
	 * @return boolean true wenn ok, false im Fehlerfall
	 */
	public function save()
	{
		if(!$this->validate())
			return false;

		if($this->new)
		{
			$qry = 'BEGIN;INSERT INTO addon.tbl_lvevaluierung(lehrveranstaltung_id, studiensemester_kurzbz,
					startzeit, endezeit, dauer, codes_ausgegeben, insertamum, insertvon,
					updateamum, updatevon, verpflichtend, lv_aufgeteilt, codes_gemailt) VALUES('.
					$this->db_add_param($this->lehrveranstaltung_id, FHC_INTEGER).','.
					$this->db_add_param($this->studiensemester_kurzbz).','.
					$this->db_add_param($this->startzeit).','.
					$this->db_add_param($this->endezeit).','.
					$this->db_add_param($this->dauer).','.
					$this->db_add_param($this->codes_ausgegeben).','.
					$this->db_add_param($this->insertamum).','.
					$this->db_add_param($this->insertvon).','.
					$this->db_add_param($this->updateamum).','.
					$this->db_add_param($this->updatevon).','.
					$this->db_add_param($this->verpflichtend, FHC_BOOLEAN).','.
                    $this->db_add_param($this->lv_aufgeteilt, FHC_BOOLEAN).','.
                    $this->db_add_param($this->codes_gemailt, FHC_BOOLEAN).');';
		}
		else
		{
			$qry = 'UPDATE addon.tbl_lvevaluierung SET '.
					' lehrveranstaltung_id='.$this->db_add_param($this->lehrveranstaltung_id, FHC_INTEGER).','.
					' studiensemester_kurzbz='.$this->db_add_param($this->studiensemester_kurzbz).','.
					' startzeit='.$this->db_add_param($this->startzeit).','.
					' endezeit='.$this->db_add_param($this->endezeit).','.
					' dauer='.$this->db_add_param($this->dauer).', '.
					' codes_ausgegeben='.$this->db_add_param($this->codes_ausgegeben, FHC_INTEGER).', '.
					' updateamum='.$this->db_add_param($this->updateamum).', '.
					' updatevon='.$this->db_add_param($this->updatevon).', '.
					' verpflichtend='.$this->db_add_param($this->verpflichtend, FHC_BOOLEAN).', '.
                    ' lv_aufgeteilt='.$this->db_add_param($this->lv_aufgeteilt, FHC_BOOLEAN).', '.
                    ' codes_gemailt='.$this->db_add_param($this->codes_gemailt, FHC_BOOLEAN).
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
				$this->codes_ausgegeben = $row->codes_ausgegeben;
				$this->insertamum = $row->insertamum;
				$this->insertvon = $row->insertvon;
				$this->verpflichtend = $this->db_parse_bool($row->verpflichtend);
                $this->lv_aufgeteilt = $this->db_parse_bool($row->lv_aufgeteilt);
                $this->codes_gemailt = $this->db_parse_bool($row->codes_gemailt);
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
	 * Prueft ob bereits eine Evaluierung fuer dieses Studiensemester/LV existiert
	 * @param $lehrveranstaltung_id
	 * @param $studiensemester_kurzbz
	 * @return boolean true wenn vorhanden, false wenn nicht vorhanden oder Fehler
	 */
	public function exists($lehrveranstaltung_id, $studiensemester_kurzbz)
	{
		$qry = "SELECT
					*
				FROM
					addon.tbl_lvevaluierung
				WHERE
					lehrveranstaltung_id=".$this->db_add_param($lehrveranstaltung_id, FHC_INTEGER)."
					AND studiensemester_kurzbz=".$this->db_add_param($studiensemester_kurzbz);
		if($result = $this->db_query($qry))
		{
			if($this->db_num_rows($result)>0)
			{
				return true;
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

	/**
	 * Loescht eine Evaluierung
	 *
	 * @param $lvevaluierung_id ID der Evaluierung die entfernt werden soll.
	 * @return boolean true wenn erfolgreich, false im Fehlerfall.
	 */
	public function delete($lvevaluierung_id)
	{
		$qry = "DELETE FROM addon.tbl_lvevaluierung
			WHERE lvevaluierung_id=".$this->db_add_param($lvevaluierung_id, FHC_INTEGER);

		if($this->db_query($qry))
		{
			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Löschen der Evaluierung';
			return false;
		}
	}
}
?>
