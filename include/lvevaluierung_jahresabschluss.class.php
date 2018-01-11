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
 * Authors: Cristina Hainberger <hainberg@technikum-wien.at>
 */

require_once(dirname(__FILE__).'/../../../include/basis_db.class.php');

class lvevaluierung_jahresabschluss extends basis_db
{
    public $new = true;
    public $result = array();
    
    public $lvevaluierung_jahresabschluss_id;   //integer
    public $oe_kurzbz;                          //string
    public $studienjahr_kurzbz;                 //string
    public $ergebnisse;                         //string
    public $verbesserungen;                     //string
    public $freigegeben = false;                //boolean
    public $insertamum;                         //date
    public $insertvon;                          //string
	public $updateamum;                         //date
	public $updatevon;                          //string
    
    
    /**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
	}
    
    /**
     * Loads LV Evaluierungs Abschlussbericht by ID
     * @param type $lvevaluierung_jahresabschluss_id
     * * @return boolean true if succeeded, false if not succeeded
     */
    public function load($lvevaluierung_jahresabschluss_id)
    {
        	if(!is_numeric($lvevaluierung_jahresabschluss_id))
		{
			$this->errormsg = 'ID ist ungueltig';
			return false;
		}

		$qry = "SELECT
					*
				FROM
					addon.tbl_lvevaluierung_jahresabschluss
				WHERE
					lvevaluierung_jahresabschluss_id=".$this->db_add_param($lvevaluierung_jahresabschluss_id, FHC_INTEGER);

		if($result = $this->db_query($qry))
		{
			if($row = $this->db_fetch_object($result))
			{
				$this->lvevaluierung_jahresabschluss_id = $row->lvevaluierung_jahresabschluss_id;
				$this->oe_kurzbz = $row->oe_kurzbz;
				$this->studienjahr_kurzbz = $row->studienjahr_kurzbz;
				$this->ergebnisse = $row->ergebnisse;
				$this->verbesserungen = $row->verbesserungen;
				$this->freigegeben = $row->freigegeben;
				$this->insertamum = $row->insertamum;
				$this->insertvon = $row->insertvon;
				$this->updateamum = $row->updateamum;
				$this->updatevon = $row->updatevon;

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
    
    public function getAllJahresabschluesse()
    {
        
        $qry = "SELECT
					*
				FROM
					addon.tbl_lvevaluierung_jahresabschluss
                ORDER BY oe_kurzbz, studienjahr_kurzbz";
 
        if($result = $this->db_query($qry))
        {
            while ($row = $this->db_fetch_object($result))
			{
				$obj = new lvevaluierung_jahresabschluss();

				$obj->lvevaluierung_jahresabschluss_id = $row->lvevaluierung_jahresabschluss_id;
				$obj->oe_kurzbz = $row->oe_kurzbz;
				$obj->studienjahr_kurzbz = $row->studienjahr_kurzbz;
				$obj->ergebnisse = $row->ergebnisse;
				$obj->verbesserungen = $row->verbesserungen;
				$obj->freigegeben = $row->freigegeben;
				$obj->insertamum = $row->insertamum;
				$obj->insertvon = $row->insertvon;
				$obj->updateamum = $row->updateamum;
				$obj->updatevon = $row->updatevon;

				$this->result[] = $obj;
//                var_dump($obj);
			}         
        }
        else 
        {
            $this->errormsg = 'Fehler beim laden der Daten';
            return false;
        }
        
    }
    
    /**
     * Loads all LV Evaluierungs Abschlussberichte of a specific Organisationseinheit
     * @param type string $oe_kurzbz
     * @param type string $studienjahr_kurzbz
     * @return boolean true if succeeded, false if not succeeded
     */
    public function getByOeStudienjahr($oe_kurzbz, $studienjahr_kurzbz = '')
    {
		$qry = "SELECT
					*
				FROM
					addon.tbl_lvevaluierung_jahresabschluss
				WHERE
					oe_kurzbz=".$this->db_add_param($oe_kurzbz, FHC_STRING);
        
        if ($studienjahr_kurzbz != '')
            $qry .= "AND studienjahr_kurzbz=" . $this->db_add_param ($studienjahr_kurzbz, FHC_STRING);

		if($result = $this->db_query($qry))
		{
			while($row = $this->db_fetch_object($result))
			{
                $obj = new stdClass();
                
				$obj->lvevaluierung_jahresabschluss_id = $row->lvevaluierung_jahresabschluss_id;
				$obj->oe_kurzbz = $row->oe_kurzbz;
				$obj->studienjahr_kurzbz = $row->studienjahr_kurzbz;
				$obj->ergebnisse = $row->ergebnisse;
				$obj->verbesserungen = $row->verbesserungen;
				$obj->freigegeben = $row->freigegeben;
				$obj->insertamum = $row->insertamum;
				$obj->insertvon = $row->insertvon;
				$obj->updateamum = $row->updateamum;
				$obj->updatevon = $row->updatevon;

				$obj->new = false;
                
                $this->result[]= $obj;
				
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
     * Loads all LV Evaluierungs Abschlussberichte of a specific studienjahr
     * @param type string $oe_kurzbz
     * @param type string $studienjahr_kurzbz
     * @return boolean true if succeeded, false if not succeeded
     */
    public function getByStudienjahr($studienjahr_kurzbz)
    {
		$qry = "SELECT
					*
				FROM
					addon.tbl_lvevaluierung_jahresabschluss
				WHERE
					studienjahr_kurzbz=" . $this->db_add_param ($studienjahr_kurzbz, FHC_STRING);

		if($result = $this->db_query($qry))
		{
			while($row = $this->db_fetch_object($result))
			{
                $obj = new stdClass();
                
				$obj->lvevaluierung_jahresabschluss_id = $row->lvevaluierung_jahresabschluss_id;
				$obj->oe_kurzbz = $row->oe_kurzbz;
				$obj->studienjahr_kurzbz = $row->studienjahr_kurzbz;
				$obj->ergebnisse = $row->ergebnisse;
				$obj->verbesserungen = $row->verbesserungen;
				$obj->freigegeben = $row->freigegeben;
				$obj->insertamum = $row->insertamum;
				$obj->insertvon = $row->insertvon;
				$obj->updateamum = $row->updateamum;
				$obj->updatevon = $row->updatevon;

				$obj->new = false;
                
                $this->result[]= $obj;
				
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
     * Saves LV Evaluierungs Abschlussberichte (insert and update)
     * @return boolean true if succeeded, false if not succeeded
     */
    public function save()
    {
       if($this->new)
		{
			$qry = 'BEGIN;
                    INSERT INTO addon.tbl_lvevaluierung_jahresabschluss(
                        oe_kurzbz, 
                        studienjahr_kurzbz,
                        ergebnisse,
                        verbesserungen,
                        freigegeben,
                        insertamum, 
                        insertvon,
                        updateamum, 
                        updatevon) 
                    VALUES('.
					$this->db_add_param($this->oe_kurzbz).','.
					$this->db_add_param($this->studienjahr_kurzbz).','.
					$this->db_add_param($this->ergebnisse).','.
					$this->db_add_param($this->verbesserungen).','.
					$this->db_add_param($this->freigegeben, FHC_BOOLEAN).','.
					$this->db_add_param($this->insertamum).','.
					$this->db_add_param($this->insertvon).','.
					$this->db_add_param($this->updateamum).','.
					$this->db_add_param($this->updatevon).');';
		}
		else
		{
			$qry = 'UPDATE addon.tbl_lvevaluierung_jahresabschluss SET '.
					' oe_kurzbz='.$this->db_add_param($this->oe_kurzbz).','.
					' studienjahr_kurzbz='.$this->db_add_param($this->studienjahr_kurzbz).','.
					' ergebnisse='.$this->db_add_param($this->ergebnisse).','.
					' verbesserungen='.$this->db_add_param($this->verbesserungen).','.
					' freigegeben='.$this->db_add_param($this->freigegeben, FHC_BOOLEAN).', '.
					' updateamum='.$this->db_add_param($this->updateamum).', '.
					' updatevon='.$this->db_add_param($this->updatevon).
					' WHERE lvevaluierung_jahresabschluss_id='.$this->db_add_param($this->lvevaluierung_jahresabschluss_id); //FHC_integer
		}
        
		if($this->db_query($qry))
		{
			if($this->new)
			{
				//naechste ID aus der Sequence holen
				$qry_currval = "SELECT currval('addon.tbl_lvevaluierung_jahresabschluss_lvevaluierung_jahresabschluss_id_seq') as id;";
                
				if($this->db_query($qry_currval))
				{
					if($row = $this->db_fetch_object())
					{
						$this->lvevaluierung_jahresabschluss_id = $row->id;
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
     * Checks if at least 1 LV Evaluierungs Abschlussberichte exists for a given
     * Organisationseinheit and Studienjahr
     * @param type string $oe_kurzbz
     * @param type string $studienjahr_kurzbz
     * @return boolean true if succeeded, false if not succeeded
     */
    public function exists($oe_kurzbz, $studienjahr_kurzbz)
    {
        $qry = "
                    SELECT 
                        1
                    FROM
                        addon.tbl_lvevaluierung_jahresabschluss
                    WHERE
                        oe_kurzbz=".$this->db_add_param($oe_kurzbz)." AND
                        studienjahr_kurzbz=" . $this->db_add_param($studienjahr_kurzbz) . "";
      
        
        if($result = $this->db_query($qry))
        {
            if($this->db_num_rows($result) > 0)
                return true;               
            else
                return false;           
        }
        else 
        {
            $this->errormsg = 'Fehler beim laden der Daten';
            return false;
        }
    }
    
}
