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

class lvevaluierung_frage extends basis_db
{
	private $new=true;
	public $result = array();
	public $lvevaluierung_frage_id;
	public $lvevaluierung_frage_antwort_id;
	public $bezeichnung = array();
	public $aktiv=true;
	public $typ='text';
	public $sort=1;
	public $wert=1;
	public $lehrmodus_kurzbz;

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
	public function load($lvevaluierung_frage_id)
	{
		$sprache = new sprache();
		$qry = "SELECT
					*, ".$sprache->getSprachQuery('bezeichnung')."
				FROM
					addon.tbl_lvevaluierung_frage
				WHERE
					lvevaluierung_frage_id=".$this->db_add_param($lvevaluierung_frage_id, FHC_INTEGER);

		if($result = $this->db_query($qry))
		{
			if($row = $this->db_fetch_object($result))
			{
				$this->lvevaluierung_frage_id = $row->lvevaluierung_frage_id;
				$this->typ = $row->typ;
				$this->aktiv = $this->db_parse_bool($row->aktiv);
				$this->sort = $row->sort;
				$this->bezeichnung = $sprache->parseSprachResult('bezeichnung',$row);
				$this->new = false;
				$this->lehrmodus_kurzbz = $row->lehrmodus_kurzbz;

				return true;
			}
			else
			{
				$this->errormsg = 'Eintrag wurde nicht gefunden';
				return true;
			}
		}
		else
		{
			$this->errormsg = 'Fehler beim Laden der Daten';
			return false;
		}
	}

	/**
	 * Laedt die Fragen
	 * @param $aktiv boolean gibt an ob
	 */
	public function getFragen($aktiv=true)
	{
		$sprache = new sprache();
		$qry = "SELECT
					lvevaluierung_frage_id, typ, aktiv, sort, lehrmodus_kurzbz, ".
					$sprache->getSprachQuery('bezeichnung')."
				FROM
					addon.tbl_lvevaluierung_frage ";
		if($aktiv===false)
			$qry.=" WHERE aktiv=false";

		$qry.=" ORDER BY sort";

		if($result = $this->db_query($qry))
		{
			while($row = $this->db_fetch_object($result))
			{
				$obj = new lvevaluierung_frage();

				$obj->lvevaluierung_frage_id = $row->lvevaluierung_frage_id;
				$obj->typ = $row->typ;
				$obj->aktiv = $this->db_parse_bool($row->aktiv);
				$obj->sort = $row->sort;
				$obj->bezeichnung = $sprache->parseSprachResult('bezeichnung',$row);
				$obj->new = false;
				$obj->lehrmodus_kurzbz = $row->lehrmodus_kurzbz;

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
	 * Speichert einen Eintrag
	 */
	public function save()
	{
		if($this->new)
		{
			$qry = "BEGIN;INSERT INTO addon.tbl_lvevaluierung_frage(typ,";

			foreach($this->bezeichnung as $key=>$value)
			{
				$idx = sprache::$index_arr[$key];
				$qry.=" bezeichnung[$idx],";
			}

			$qry.=' aktiv, sort, lehrmodus_kurzbz) VALUES('.
					$this->db_add_param($this->typ).',';

			reset($this->bezeichnung);
			foreach($this->bezeichnung as $key=>$value)
				$qry.=$this->db_add_param($value).',';

			$qry.= $this->db_add_param($this->aktiv, FHC_BOOLEAN).','.
			$this->db_add_param($this->sort, FHC_INTEGER) .','.
			$this->db_add_param($this->lehrmodus_kurzbz).');';
		}
		else
		{
			$qry = "UPDATE addon.tbl_lvevaluierung_frage SET ".
					" typ=".$this->db_add_param($this->typ).",".
					" lehrmodus_kurzbz =".$this->db_add_param($this->lehrmodus_kurzbz, FHC_STRING).",".
					" aktiv=".$this->db_add_param($this->aktiv, FHC_BOOLEAN).",";

			foreach($this->bezeichnung as $key=>$value)
			{
				$idx = sprache::$index_arr[$key];
				$qry.=" bezeichnung[$idx]=".$this->db_add_param($value).",";
			}

			$qry.=" sort=".$this->db_add_param($this->sort, FHC_INTEGER)."
			WHERE lvevaluierung_frage_id=".$this->db_add_param($this->lvevaluierung_frage_id);
		}

		if($this->db_query($qry))
		{

			if($this->new)
			{
				//naechste ID aus der Sequence holen
				$qry="SELECT currval('addon.tbl_lvevaluierung_frage_lvevaluierung_frage_id_seq') as id;";
				if($this->db_query($qry))
				{
					if($row = $this->db_fetch_object())
					{
						$this->lvevaluierung_frage_id = $row->id;
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
	 * Speichert Antwortmoeglichkeiten
	 */
	public function saveAntwort()
	{
		if($this->new)
		{
			$qry = "INSERT INTO addon.tbl_lvevaluierung_frage_antwort(lvevaluierung_frage_id,";

			foreach($this->bezeichnung as $key=>$value)
			{
				$idx = sprache::$index_arr[$key];
				$qry.=" bezeichnung[$idx],";
			}

			$qry.=' sort, wert) VALUES('.
					$this->db_add_param($this->lvevaluierung_frage_id).',';

			reset($this->bezeichnung);
			foreach($this->bezeichnung as $key=>$value)
				$qry.=$this->db_add_param($value).',';

			$qry.= $this->db_add_param($this->sort, FHC_INTEGER).','.
				$this->db_add_param($this->wert, FHC_INTEGER).');';
		}
		else
		{
			$qry = "UPDATE addon.tbl_lvevaluierung_frage_antwort SET ".
					" wert=".$this->db_add_param($this->wert).",";

			foreach($this->bezeichnung as $key=>$value)
			{
				$idx = sprache::$index_arr[$key];
				$qry.=" bezeichnung[$idx]=".$this->db_add_param($value).",";
			}

			$qry.=" sort=".$this->db_add_param($this->sort, FHC_INTEGER)."
			WHERE lvevaluierung_frage_antwort_id=".$this->db_add_param($this->lvevaluierung_frage_antwort_id, FHC_INTEGER);
		}

		if($this->db_query($qry))
		{
			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Speichern der Daten';
			return false;
		}
	}

	/**
	 * Loescht eine Frage
	 * @param $lvevaluierung_frage_id ID der Frage
	 * @return true wenn ok, false im Fehlerfall
	 */
	public function delete($lvevaluierung_frage_id)
	{
		// Wenn bereits Antworten gegeben wurden, wird das loeschen verhindert
		$qry = "SELECT
					count(*) as anzahl
				FROM
					addon.tbl_lvevaluierung_antwort
				WHERE
					lvevaluierung_frage_id=".$this->db_add_param($lvevaluierung_frage_id, FHC_INTEGER);

		if($result = $this->db_query($qry))
		{
			if($row = $this->db_fetch_object($result))
			{
				if($row->anzahl>0)
				{
					$this->errormsg = 'Fehler beim Löschen der Frage. Es sind bereits Antworten zu dieser Frage vorhanden';
					return false;
				}
			}
		}

		// Wenn Antwortmoeglichkeiten vorhanden sind, dann werden diese mitentfernt

		$qry = "
			DELETE FROM addon.tbl_lvevaluierung_frage_antwort WHERE lvevaluierung_frage_id=".$this->db_add_param($lvevaluierung_frage_id, FHC_INTEGER).";
			DELETE FROM addon.tbl_lvevaluierung_frage WHERE lvevaluierung_frage_id=".$this->db_add_param($lvevaluierung_frage_id, FHC_INTEGER);

		if($this->db_query($qry))
			return true;
		else
		{
			$this->errormsg = 'Fehler beim Löschen der Frage';
			return false;
		}
	}

	/**
	 * Laedt die Antwortmoeglichkeiten zu einer Frage
	 * @param $lvevaluierung_frage_id
	 * @return boolean
	 */
	public function loadAntworten($lvevaluierung_frage_id)
	{
		$sprache = new sprache();
		$qry = "SELECT *,".
				$sprache->getSprachQuery('bezeichnung')." FROM addon.tbl_lvevaluierung_frage_antwort
		WHERE lvevaluierung_frage_id=".$this->db_add_param($lvevaluierung_frage_id)."
		ORDER BY sort";

		if($result = $this->db_query($qry))
		{
			while($row = $this->db_fetch_object($result))
			{
				$obj = new lvevaluierung_frage();
				$obj->lvevaluierung_frage_antwort_id = $row->lvevaluierung_frage_antwort_id;
				$obj->sort = $row->sort;
				$obj->wert = $row->wert;
				$obj->lvevaluierung_frage_id = $row->lvevaluierung_frage_id;
				$obj->bezeichnung = $sprache->parseSprachResult('bezeichnung',$row);
				$obj->new = false;
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
	 * Loescht eine Antwort
	 * @param $lvevaluierung_frage_antwort_id
	 * @return boolean
	 */
	public function deleteAntwort($lvevaluierung_frage_antwort_id)
	{
		$qry = "DELETE FROM addon.tbl_lvevaluierung_frage_antwort WHERE lvevaluierung_frage_antwort_id=".$this->db_add_param($lvevaluierung_frage_antwort_id, FHC_INTEGER);

		if($this->db_query($qry))
		{
			return true;
		}
		else
		{
			$this->errormsg = 'Fehler beim Loeschen der Antwort';
			return false;
		}
	}

	/**
	 * Laedt eine Antwortmoeglichkeit
	 * @param $lvevaluierung_frage_antwort_id
	 * @return boolean
	 */
	public function loadAntwort($lvevaluierung_frage_antwort_id)
	{
		$sprache = new sprache();
		$qry = "SELECT *,".
				$sprache->getSprachQuery('bezeichnung')." FROM addon.tbl_lvevaluierung_frage_antwort
		WHERE lvevaluierung_frage_antwort_id=".$this->db_add_param($lvevaluierung_frage_antwort_id);

		if($result = $this->db_query($qry))
		{
			if($row = $this->db_fetch_object($result))
			{
				$this->lvevaluierung_frage_antwort_id = $row->lvevaluierung_frage_antwort_id;
				$this->sort = $row->sort;
				$this->wert = $row->wert;
				$this->lvevaluierung_frage_id = $row->lvevaluierung_frage_id;
				$this->bezeichnung = $sprache->parseSprachResult('bezeichnung',$row);
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
}
?>
