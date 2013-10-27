<?php

require_once('table_page_history.php');

class CTablePage extends CTable
{
	const TITLE_COLUMN = 'txtTitle';
	
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
		
		$Title = $strTitle;
		$Body = $strBody;
		
		$strSlug   = '"'.addslashes($strSlug).'"';
		$strTitle  = '"'.addslashes($strTitle).'"';
		$strCached = '"'.addslashes($Cached->GetHTML()).'"';
			
		$data = array
		(
			 'txtSlug'      => $strSlug,
			 self::TITLE_COLUMN     => $strTitle,
			 'txtBodyHTMLCache' => $strCached,
			 'idVersion_Current' => 1,
		);
			
		$PageId = $this->DoInsert($data);
		
		if( $PageId > 0 )
		{
			$History = new CTablePageHistory();
			$History->InsertHistory($PageId, $Title, $Body);
		}
	}
	
	public function UpdatePage($nID, $strSlug, $strTitle, $strBody)
	{
		$Cached = new CRCMarkup($strBody);
		
		$Title = $strTitle;
		$Body = $strBody;
		
		$History = new CTablePageHistory();
		$LatestVersion = $History->InsertHistory($nID, $Title, $Body );
		
		$strSlug   = '"'.addslashes($strSlug).'"';
		$strTitle  = '"'.addslashes($strTitle).'"';
		$strBody   = '"'.addslashes($strBody).'"';
		$strCached = '"'.addslashes($Cached->GetHTML()).'"';
		
		$data = array
		(
			 'txtSlug'  => $strSlug,
			 self::TITLE_COLUMN => $strTitle,
			 'txtBodyHTMLCache' => $strCached,
			 'idVersion_Current' => $LatestVersion,
		);
		
		$this->DoUpdate($nID, $data);		
	}
	
	public function DeletePage($unkIdOrSlug)
	{
		
	}
	
	public function GetPages()
	{
		$items = 'id,txtSlug,'.self::TITLE_COLUMN;
		$this->DoSelect($items, '', self::TITLE_COLUMN );
		return $this->m_rows;
	}
	
	public function GetPage($unkIdOrSlug , $Version = null )
	{
		$out = null;
		
		if( null == $Version )
		{
			$items = 'id,txtSlug as slug,'.self::TITLE_COLUMN.' as title,txtBodyHTMLCache as formatted';

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
		}
		else
		{
			//Get the version of the page.
		}
		
		return $out;
	}
	
	public function GetContentForEdit($unkIdOrSlug)
	{	
		$items = 'id , idVersion_Current';
		
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
			$History = new CTablePageHistory();
			$Page = $History->GetPage($out['id'], $out['idVersion_Current']);
			return $Page;
		}
		return null;
	}
	
	public function ResetCache()
	{
		//Basically there better be a id, and txtBodyHTMLCache.
		//Get all the ids
		$this->DoSelect('id,idVersion_Current');
		
		$ids = $this->m_rows;
		
		for($i=0; $i<count($ids); $i++)
		{
			$nID = (int)$ids[$i]['id'];
			$Version = (int)$ids[$i]['idVersion_Current'];
			
			$History = new CTablePageHistory();
			$Item = $History->GetPage($nID, $Version);
			$RCMarkup = new CRCMarkup($Item['txtBody']);
			$sRC = $RCMarkup->GetHTML();
			$data = array
			(
				 'txtBodyHTMLCache' => '"'.addslashes($sRC).'"',
			);
			$this->DoUpdate($nID, $data);
		}
	}
}

?>
