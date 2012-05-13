<?php

class CTablePage extends CTable
{
	public function CTablePage()
	{
		parent::CTable('tblPage');
	}
	
	public function IsSlugTaken($strSlug)
	{
		$this->DoSelect('id', 'txtSlug="'.$strSlug.'"');
		return count($this->m_rows) != 0;
	}
	
	public function CreatePage($strSlug, $strTitle, $strBody)
	{
		$Cached = new CRCMarkup($strBody);
		
		$strSlug   = '"'.addslashes($strSlug).'"';
		$strTitle  = '"'.addslashes($strTitle).'"';
		$strBody   = '"'.addslashes($strBody).'"';
		$strCached = '"'.addslashes($Cached->GetHTML()).'"';
		
		$data = array
		(
			 'txtSlug'      => $strSlug,
			 'txtTitle'     => $strTitle,
			 'txtBody'   => $strBody,
			 'txtBodyHTMLCache' => $strCached,
		);
		
		$this->DoInsert($data);
	}
	
	public function UpdatePage($nID, $strSlug, $strTitle, $strBody)
	{
		$Cached = new CRCMarkup($strBody);
		
		$strSlug   = '"'.addslashes($strSlug).'"';
		$strTitle  = '"'.addslashes($strTitle).'"';
		$strBody   = '"'.addslashes($strBody).'"';
		$strCached = '"'.addslashes($Cached->GetHTML()).'"';
		
		$data = array
		(
			 'txtSlug'  => $strSlug,
			 'txtTitle' => $strTitle,
			 'txtBody'  => $strBody,
			 'txtBodyHTMLCache' => $strCached,
		);
		
		$this->DoUpdate($nID, $data);
	}
	
	public function DeletePage($unkIdOrSlug)
	{
		
	}
	
	public function GetPages()
	{
		$items = 'id,txtSlug,txtTitle';
		$this->DoSelect($items, '', 'txtTitle');
		return $this->m_rows;
	}
	
	public function GetPage($unkIdOrSlug)
	{
		$items = 'id,txtSlug as slug,txtTitle as title,txtBody as body,txtBodyHTMLCache as formatted';
		
		if('integer' == gettype($unkIdOrSlug))
		{
			$selection = 'id='.$unkIdOrSlug;
		}
		else if ('string' == gettype($unkIdOrSlug))
		{
			$selection = 'txtSlug="'.$unkIdOrSlug.'"';
		}
		else
		{
			assert(false);
		}
		$this->DoSelect($items, $selection);
		
		//echo 'There are this many rows: '.count($this->m_rows);
		
		$out = (0 == count($this->m_rows)) ? null : $this->m_rows[0];
		$this->m_rows = null;
		if(null != $out)
		{

		}
		return $out;
	}
}

?>
