<?php
/*******************************************************************************
 * File:   clearpage.php
 * Class:  CClearPage
 * Purpose: Temporary page used for clearing the databes.
 *
 * Copyright (C) 2009 Blaine Myers
 ******************************************************************************/
require_once('page_base.php');

class CClearPage extends CPageBase
{
	public function CClearPage()
	{
		parent::CPageBase('Clear', true, 5);
	}

	protected function DisplayContent()
	{
		//$this->CreateFakeNews();
		//$this->DoClear();
	}

	private function DoClear()
	{
		print('Clearing databases...<br/>');
		$this->DoQuery('delete from tblContent');
		//Reset the auto increment.
		$this->DoQuery('ALTER TABLE tblContent AUTO_INCREMENT=1');
		$this->DoQuery('delete from tblContentGenres');
		$this->DoQuery('delete from tblNews');
      $this->DoQuery('delete from tblMessage');
		$this->DoQuery('delete from tblComment');			 

		//Delete all files in the files directory.
		$hDir = opendir('./files/');
		while(false !== ($strFile = readdir($hDir)))
		{
			if(!is_dir($strFile))
			{
				echo 'File: ', $strFile, "<br/>\n";
				unlink('./files/'.$strFile);
			}
		}
		closedir($hDir);
	}
	
	private function CreateFakeDateInQuotes()
	{
		return '"'.rand(1983, 2008).'-'.rand(1, 12).'-'.rand(1, 28).'"';
	}

	private function CreateFakeNews()
	{
		$this->DoQuery('delete from tblNews where idUser=22');

		$strNews = '<p>So some new stuff is happing, it is good to know that is the case.<br/><br/>Some more news sutff is going on.</p>';

		for($i=0; $i<0; $i++)
		{
			$qry = 'insert into tblNews (dtPosted, txtTitle, txtBody, idUser)
					values
					('.$this->CreateFakeDateInQuotes().', "News Title Here", "'.$strNews.'", 22)';

			$this->DoQuery($qry);
		}
	}

	private function CreateDummyData()
	{

		$strDesc = '<p>Some really long descriptioin, that goes on and one, and it is trying to be
at least one paragraph, so that it is multiple lines long, yes is really shold be.</p>';

		//For testing create some fake data.
		for($i=0; $i<300; $i++)
		{
			$title = $this->CreateTestTitle($i);

			$strQ = 'insert into tblContent
				(txtSeries,
				fVolume,
				txtTitle,
				txtAuthorLast,
				txtAuthorFirst,
				txtDesc,
				dtProduced,
				txtFile,
				dtPublished,
				dtUpdated)
			values ('.$title[0].', '.$title[1].', '.$title[2].', "Everett", "Jack", "'.$strDesc.'", "
			'.rand(1900, 2009).'-'.rand(1, 12).'-'.rand(1, 28).'", "files/somefile.pdf",
			now(),
			"'.rand(2009, 2009).'-'.rand(1, 12).'-'.rand(1, 28).'")';
			//print($strQ);
			//print("<br/>\n");
			$res = $this->DoQuery($strQ);
			/*
			//Also go ahead and assign 0 to 3 genres per title.
			for($j = 0; $j < 3; $j++)
			{
				if(rand(0, 1)==1)
				{
					$strQ = sprintf('insert into tblContentGenres (idContent, idGenre) values (%d, %d)',
						$i+1,
						rand(1, 7));

					$res = $this->m_db->query($strQ);
					if(!$res)
						echo $this->m_db->error, "<br/>\n";
				}
			}
			*/
		}
	}

	private function CreateTestTitle($i)
	{
		$title[0] = '"Test"';
		$title[1] = sprintf('"%d"', $i+1);
		$title[2] = '"Title"';

		return $title;
	}
}
?>
