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
 * Authors: Andreas Österreicher <andreas.oesterreicher@technikum-wien.at>,
 *          Cristina Hainberger <cristina.hainberg@technikum-wien.at>
 */
require_once(dirname(__FILE__).'/../../../include/basis_db.class.php');

class lvevaluierung_selbstevaluierung extends basis_db
{
	private $new=true;
	public $result = array();

	public $lvevaluierung_selbstevaluierung_id;
	public $lvevaluierung_id;
	public $uid;
	public $freigegeben=false;
	public $persoenlich;
	public $gruppe;
	public $entwicklung;
	public $weiterbildung_bedarf;
	public $weiterbildung;
	public $insertamum;
	public $insertvon;
	public $updateamum;
	public $updatevon;

    /**
	 * Konstruktor
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Laedt eine Selbstevaluierung
	 * @param $lvevaluierung_selbstevaluierung_id
	 * @return boolean true wenn ok, false im Fehlerfall
	 */
	public function load($lvevaluierung_selbstevaluierung_id)
	{
		if(!is_numeric($lvevaluierung_selbstevaluierung_id))
		{
			$this->errormsg = 'ID ist ungueltig';
			return false;
		}

		$qry = "SELECT
					*
				FROM
					addon.tbl_lvevaluierung_selbstevaluierung
				WHERE
					lvevaluierung_selbstevaluierung_id=".$this->db_add_param($lvevaluierung_selbstevaluierung_id, FHC_INTEGER);

		if($result = $this->db_query($qry))
		{
			if($row = $this->db_fetch_object($result))
			{
				$this->lvevaluierung_selbstevaluierung_id = $row->lvevaluierung_selbstevaluierung_id;
				$this->lvevaluierung_id = $row->lvevaluierung_id;
				$this->uid = $row->uid;
				$this->freigegeben = $this->db_parse_bool($row->freigegeben);
				$this->persoenlich = $row->persoenlich;
				$this->gruppe = $row->gruppe;
				$this->entwicklung = $row->entwicklung;
				$this->weiterbildung_bedarf = $this->db_parse_bool($row->weiterbildung_bedarf);
				$this->weiterbildung = $row->weiterbildung;
				$this->insertamum = $row->insertamum;
				$this->insertvon = $row->insertvon;
				$this->updateamum = $row->updateamum;
				$this->updatevon = $row->updatevon;
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
	 * Speichert die Selbstevaluierung
	 * @return boolean true wenn ok, false im Fehlerfall
	 */
	public function save()
	{
		if($this->new)
		{
			$qry = 'BEGIN;INSERT INTO addon.tbl_lvevaluierung_selbstevaluierung(lvevaluierung_id, uid, freigegeben,
					gruppe,  persoenlich, entwicklung, weiterbildung_bedarf, weiterbildung, insertamum, insertvon, updateamum, updatevon) VALUES('.
					$this->db_add_param($this->lvevaluierung_id, FHC_INTEGER).','.
					$this->db_add_param($this->uid).','.
					$this->db_add_param($this->freigegeben, FHC_BOOLEAN).','.
					$this->db_add_param($this->gruppe).','.
					$this->db_add_param($this->persoenlich).','.
					$this->db_add_param($this->entwicklung).','.
					$this->db_add_param($this->weiterbildung_bedarf, FHC_BOOLEAN).','.
					$this->db_add_param($this->weiterbildung).','.
					$this->db_add_param($this->insertamum).','.
					$this->db_add_param($this->insertvon).','.
					$this->db_add_param($this->updateamum).','.
					$this->db_add_param($this->updatevon).');';
		}
		else
		{
			$qry = 'UPDATE addon.tbl_lvevaluierung_selbstevaluierung SET '.
					' lvevaluierung_id='.$this->db_add_param($this->lvevaluierung_id, FHC_INTEGER).','.
					' uid='.$this->db_add_param($this->uid).','.
					' freigegeben='.$this->db_add_param($this->freigegeben, FHC_BOOLEAN).','.
					' gruppe='.$this->db_add_param($this->gruppe).','.
					' persoenlich='.$this->db_add_param($this->persoenlich).', '.
					' entwicklung='.$this->db_add_param($this->entwicklung).', '.
					' weiterbildung_bedarf='.$this->db_add_param($this->weiterbildung_bedarf, FHC_BOOLEAN).', '.
					' weiterbildung='.$this->db_add_param($this->weiterbildung).', '.
					' updateamum='.$this->db_add_param($this->updateamum).', '.
					' updatevon='.$this->db_add_param($this->updatevon).' '.
					' WHERE lvevaluierung_selbstevaluierung_id='.$this->db_add_param($this->lvevaluierung_selbstevaluierung_id, FHC_INTEGER);
		}

		if($this->db_query($qry))
		{
			if($this->new)
			{
				//naechste ID aus der Sequence holen
				$qry="SELECT currval('addon.tbl_lvevaluierung_selbstevaluierung_lvevaluierung_selbstevaluierung_id_seq') as id;";
				if($this->db_query($qry))
				{
					if($row = $this->db_fetch_object())
					{
						$this->lvevaluierung__selbstevaluierung_id = $row->id;
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
	 * Laedt Selbstevaluierung zu einer Lehrveranstaltung
	 * @param $lvevaluierung_id
	 * @return boolean
	 */
	public function getSelbstevaluierung($lvevaluierung_id)
	{
		$qry = "SELECT
				*
			FROM
				addon.tbl_lvevaluierung_selbstevaluierung
			WHERE
				lvevaluierung_id=".$this->db_add_param($lvevaluierung_id, FHC_INTEGER);

		if($result = $this->db_query($qry))
		{
			if($row = $this->db_fetch_object($result))
			{
				$this->lvevaluierung_selbstevaluierung_id = $row->lvevaluierung_selbstevaluierung_id;
				$this->lvevaluierung_id = $row->lvevaluierung_id;
				$this->uid = $row->uid;
				$this->freigegeben = $this->db_parse_bool($row->freigegeben);
				$this->persoenlich = $row->persoenlich;
				$this->gruppe = $row->gruppe;
				$this->entwicklung = $row->entwicklung;
				$this->weiterbildung_bedarf = $this->db_parse_bool($row->weiterbildung_bedarf);
				$this->weiterbildung = $row->weiterbildung;
				$this->insertamum = $row->insertamum;
				$this->insertvon = $row->insertvon;
				$this->updateamum = $row->updateamum;
				$this->updatevon = $row->updatevon;
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
	 * Laedt Lehrveranstaltungen, die selbstevaluiert wurden, zu einem bestimmten Studiengang und Studienjahr
	 * @param $studiengang_kz, $ws (wintersemester), $ss (sommersemester)
	 * @return associative array
	 */
    public function getLVwhereSelbstevaluierungen($studiengang_kz, $ws, $ss)
    {       
         $qry = '
            SELECT lv.bezeichnung, lv.orgform_kurzbz, lv.lehrveranstaltung_id, lv.semester, lvev.lvevaluierung_id
            FROM lehre.tbl_lehrveranstaltung lv
            JOIN addon.tbl_lvevaluierung lvev USING (lehrveranstaltung_id)
            JOIN addon.tbl_lvevaluierung_selbstevaluierung lvsev USING (lvevaluierung_id)
            WHERE lv.studiengang_kz = ' . $this->db_add_param($studiengang_kz, FHC_INTEGER) . '
            AND (lvev.studiensemester_kurzbz = ' . $this->db_add_param($ws, FHC_STRING) . ' OR lvev.studiensemester_kurzbz = ' . $this->db_add_param($ss, FHC_STRING) . ')
            AND lvsev.freigegeben = true
            ORDER BY lv.bezeichnung';

        if ($result = $this->db_query($qry)) 
        {
            $selbstev_arr = array(); 
            while ($row = $this->db_fetch_object($result)) 
            {
                $selbstev_arr['bezeichnung'][] = $row->bezeichnung;
                $selbstev_arr['orgform_kurzbz'][] = $row->orgform_kurzbz;
				$selbstev_arr['lehrveranstaltung_id'][] = $row->lehrveranstaltung_id;
				$selbstev_arr['semester'][] = $row->semester;
				$selbstev_arr['lvevaluierung_id'][] = $row->lvevaluierung_id;
            }
            $this->result = $selbstev_arr;
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