<?php

abstract class CTable
{
	public function CTable($sTableName)
	{
		global $g_rcPrefix;
		assert(gettype($sTableName) == 'string');
		$this->m_db = RCSql_GetDb();
		$this->m_sTableName = $g_rcPrefix.$sTableName;
		
		$this->m_res = null;
		$this->m_rows = null;
	}
	
	private $m_db;
	protected $m_sTableName;
	protected $m_rows;
	
	protected function DoUpdate($nID, $data, $strAdditionalQualifier ='')
	{		
		assert('integer' == gettype($nID));
		
		$cols = array();
		foreach($data as $key=>$val)
		{
			$cols[] = $key.'='.$val;
		}
		$qry = 'update '.$this->m_sTableName.' set '.implode(', ', $cols).' where id='.$nID;
		if(strlen($strAdditionalQualifier) > 0)
		{
			$qry .= ' and '.$strAdditionalQualifier;
		}
		
		$this->DoQuery($qry);
	}
	
	public function ResetCache()
	{
		//Basically there better be a id, txtBody, and txtBodyHTMLCache.
		//Get all the ids
		$this->DoSelect('id');
		
		$ids = $this->m_rows;
		
		for($i=0; $i<count($ids); $i++)
		{
			$nID = (int)$ids[$i]['id'];
			
			$this->DoSelect('txtBody', 'id='.$nID);
			$sRC = $this->m_rows[0]['txtBody'];
			$RCMarkup = new CRCMarkup($sRC);
			$sRC = $RCMarkup->GetHTML();
			$data = array
			(
				 'txtBodyHTMLCache' => '"'.addslashes($sRC).'"',
			);
			$this->DoUpdate($nID, $data);
		}
	}
	
	protected function DoDelete($nID, $strAdditionalQualifier ='')
	{
		assert('integer' == gettype($nID));
		$qry = 'delete from '.$this->m_sTableName.' where id='.$nID;
		if(strlen($strAdditionalQualifier) > 0)
		{
			$qry .= ' and '.$strAdditionalQualifier;
		}
		$this->DoQuery($qry);
	}
	
	protected function DoInsert($data)
	{
		//"INSERT INTO table (".implode(',',array_keys($_fields)).") VALUES (".implode(',',array_values($_fields)).")");
		$qry = 'insert into '.$this->m_sTableName.' ('.implode(',',array_keys($data)).') values ('.implode(',',array_values($data)).')';
		
		$this->DoQuery($qry);
		return $this->m_db->insert_id;
	}
	
	//Do select attempts to get the specifed items. If it fails it returns 0.
	//If there were 0 matching rows it returns 0. If it returns 0 EndSelect
	//should not be called. Otherwise EndSelect should be called before doing
	//another query. Between calls to do select and endselect. The rows may
	//be gotten.
	protected function DoSelect($items, $where = '', $order = '', $limit = '')
	{
		$this->m_rows = null;
		
		$qry = 'select '.$items.' from '.$this->m_sTableName;
		if(strlen($where) > 0)
			$qry .= ' where '.$where;
		
		if(strlen($order) > 0)
			$qry .= ' order by '.$order;
		
		if('integer' == gettype($limit))
			$qry .= ' limit '.$limit;
		
		$res = $this->DoQuery($qry);
		
		if(!$res)
		{
			//An invalid query. The error message will have been printed in
			//DoQuery.
			return 0;	
		}
		
		$nRows = $nRows = $res->num_rows;
		
		for($i = 0; $i < $nRows; $i++)
		{
			$row = $res->fetch_assoc();
			$this->m_rows[$i] = $row;
		}

		$res->free();
		
		return $nRows;
	}
	
	
	protected function DoQuery($qry)
	{
		$res = $this->m_db->query($qry);
		if(!$res)
		{
			print($qry."<br/>\n");
			printf("MySQL Querry Error: %s.<br/>\n", $this->m_db->error);
		}
		return $res;
	}
}

?>
