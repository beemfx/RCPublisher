<?php

require( 'table_base.php' );

class CTableNews extends CTable
{
	public function CTableNews($db)
	{
		parent::CTable($db, 'tblNews');
	}
	
	public function GetStory($nID)
	{
		assert('integer' == gettype($nID));		
		$this->DoSelect('id,date_format(dtPosted, "%M %e, %Y") as dt,txtTitle,txtBody', 'id='.$nID);
		
		$out = (0 == count($this->m_rows)) ? null : $this->m_rows[0];
		$this->m_rows = null;
		if(null != $out)$out['formatted'] = preg_replace('/\r?\n/s' , '<br />', $out['txtBody']);
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
		$title = '"'.addslashes($title).'"';
		$body  = '"'.addslashes($body).'"';
		
		//Should probably also create a cached version.
		
		$data = array
		(
			 'txtTitle' => $title,
			 'txtBody'  => $body,
		);
		
		$this->DoUpdate($nID, $data);
	}
	
	public function InsertStory($title, $body)
	{
		$title = '"'.addslashes($title).'"';
		$body  = '"'.addslashes($body).'"';
		
		$insert = array
		(
			 'txtTitle' => $title,
			 'txtBody'  => $body,
			 'dtPosted' => 'now()' 
		);
		
		$this->DoInsert($insert);
	}
	
	public function ObtainRecentNews($count)
	{
		//$res = $this->DoQuery('select txtTitle, date_format(dtPosted, "%M %e, %Y") as dt, txtBody from tblNews order by dtPosted desc limit '.$nNewsStories);
		return $this->DoSelect('txtTitle, date_format(dtPosted, "%M %e, %Y") as dt, txtBody', '', 'dtPosted desc', (int)$count);
	}
	
	public function GetRecentNewsStory($n)
	{			
		assert('integer' == gettype($n));
		assert($n < count($this->m_rows));
		
		$story['title'] = $this->m_rows[$n]['txtTitle'];
		$story['date']  = $this->m_rows[$n]['dt'];
		$story['body']  = $this->m_rows[$n]['txtBody'];
		$story['formatted'] = preg_replace('/\r?\n/s' , '<br />', $story['body']);
		
		return $story;
	}
	
	public function GetYears()
	{
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
