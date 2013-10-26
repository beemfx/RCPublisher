<?php

class CTableNews extends CTable
{
	public function CTableNews()
	{
		parent::CTable('tblNews');
	}
	
	public function GetStory($nID)
	{
		assert('integer' == gettype($nID));		
		$this->DoSelect('id,date_format(dtPosted, "%M %e, %Y") as dt,txtTitle,txtBody,txtBodyHTMLCache as formatted', 'id='.$nID);
		
		$out = (0 == count($this->m_rows)) ? null : $this->m_rows[0];
		$this->m_rows = null;
		return $out;
	}
	
	public function GetArchiveByYear($year)
	{	
		$this->DoSelect(
			'id,date_format(dtPosted, "%M") as dtMonth,date_format(dtPosted, "%b %d, %Y") as dt, txtTitle',
			'date_format(dtPosted, "%Y")="'.$year.'"',
			'dtPosted desc');
		
		$rows =  $this->m_rows;
		$this->m_rows = null;
		return $rows;
	}
	
	public function UpdateStory($nID, $title, $body)
	{
		$Cached = new CRCMarkup($body);
			
		$title = '"'.addslashes($title).'"';
		$body  = '"'.addslashes($body).'"';
		$strCached = '"'.addslashes($Cached->GetHTML()).'"';
		
		$UserId = RCSession_GetUserProp('user_id');
				
		$data = array
		(
			 'idUser' => $UserId,
			 'txtTitle' => $title,
			 'txtBody'  => $body,
			 'txtBodyHTMLCache' => $strCached,
		);
		
		$this->DoUpdate($nID, $data);
	}
	
	public function InsertStory($title, $body)
	{
		$Cached = new CRCMarkup($body);
		
		$title = '"'.addslashes($title).'"';
		$body  = '"'.addslashes($body).'"';
		$strCached = '"'.addslashes($Cached->GetHTML()).'"';
		$UserId = RCSession_GetUserProp('user_id');
		
		$insert = array
		(
			'idUser' => $UserId,
			 'txtTitle' => $title,
			 'txtBody'  => $body,
			 'dtPosted' => 'now()',
			 'txtBodyHTMLCache' => $strCached,
		);
		
		$this->DoInsert($insert);
	}
	
	public function ObtainRecentNews($count)
	{
		//$res = $this->DoQuery('select txtTitle, date_format(dtPosted, "%M %e, %Y") as dt, txtBody from tblNews order by dtPosted desc limit '.$nNewsStories);
		$this->DoSelect('txtTitle as title, date_format(dtPosted, "%M %e, %Y") as date, txtBody as body, txtBodyHTMLCache as formatted', '', 'dtPosted desc', (int)$count);
		return $this->m_rows;
	}
		
	public function GetYears()
	{
		$years = null;
		
		$nRows = $this->DoSelect('distinct(date_format(dtPosted, \'%Y\')) as dtYear', '', 'dtYear desc');
		
		for($i = 0; $i < $nRows; $i++)
		{
			$years[$i] = $this->m_rows[$i]['dtYear'];
		}
		
		$this->m_rows = null;
		
		return $years;
	}
}

?>
