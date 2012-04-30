<?php
/*******************************************************************************
 * File:   editpage.php
 * Class:  CEditPage
 * Purpose: Allows editing of uploaded content.
 *
 * Copyright (C) 2009 Blaine Myers
 ******************************************************************************/
require_once('content_edit_base.php');

class CEditContentPage extends CContentEditBase
{
	public function CEditContentPage()
	{
		parent::CPageBase('Edit', true, 5);
	}

	protected function DisplayContent()
	{
		print("<h1>Edit Content</h1>\n");

		//No matter what _get will contain an id, if one wasn't
		//specified, the id will be zero and the page will e invalid.
		if(!isset($_GET['id']))
			$_GET['id'] = $_POST['id'];



		if($_POST['stage']==0)
			$this->DisplayStage0();
		else if($_POST['stage']==1)
			$this->DisplayStage1();

	}

	private function DisplayStage0()
	{
		//Query for th einformation from the selected content.
		$qry = 'select * from tblContent where id='.$_GET['id'];
		$res = $this->DoQuery($qry);

		if(false == $res || $res->num_rows != 1)
		{
			print("<p>An invalid content item was specified.</p>\n");
			return;
		}

		$row = $res->fetch_assoc();
		$_POST['id']          = $_GET['id'];
		$_POST['series']      = $row['txtSeries'];
		$_POST['volume']      = $row['fVolume'];
		$_POST['title']       = $row['txtTitle'];
		$_POST['authorlast']  = $row['txtAuthorLast'];
		$_POST['authorfirst'] = $row['txtAuthorFirst'];
		$_POST['desc']        = $this->HTMLBreaksToNewlines($row['txtDesc']);
		$_POST['month']       = $row['nPrDateMonth'];
		$_POST['day']         = $row['nPrDateDay'];
		$_POST['year']        = $row['nPrDateYear'];
		$res->free();
		
		//Now get the genres.
		$qry = 'select idGenre from tblContentGenres where idContent='.$_GET['id'];
		$res = $this->DoQuery($qry);

		if($res == true)
		{
			for($i = 0; $i < $res->num_rows; $i++)
			{
				$row = $res->fetch_assoc();
				$_POST['genre_'.$row['idGenre']] = 'on';
			}
			$res->free();
		}

		$this->ShowTable(CreateHREF(PAGE_EDITC, 'id='.$_GET['id']), 'Update', 'Delete');
	}

	private function DisplayStage1()
	{
		$this->RefreshPostData();

		$qry = 'select * from tblContent where id='.$_GET['id'];
		$res = $this->DoQuery($qry);

		if($res == false)
		{
			print("<p>An error occured while trying to update the data, please
				try again later.</p>\n");

			return;
		}

		print("<p>Updating data...</p>\n");

		$row = $res->fetch_assoc();
		$res->free();


		//If the delete button was pressed just delete the content:
		if(isset($_POST['Delete']))
		{
			@ unlink($row['txtFile']);
			$qry = 'delete from tblContent where id='.$_GET['id'];
			$this->DoQuery($qry);
			$qry = 'delete from tblContentGenres where idContent='.$_GET['id'];
			$this->DoQuery($qry);
			$this->DoQuery('delete from tblComment where idContent='.$_GET['id']);
			print("<p>Deleted the content. Return to the <a href=\"index.php\">main page</a>.</p>");
			return;
		}


		$_POST['dest_filename'] = $row['txtFile'];
		
		if(!$this->IsPostDataValid(false))
		{
			$this->DisplayStage0();
			return;
		}

		//If a new file was specified, replaced the old one.
		if(strlen($_FILES['ufile']['name'])>0)
		{
			//Change the extension of the filename if it needs to be different.
			preg_match('/(.*)[\.$]/', $row['txtFile'], $regs);
			$_POST['dest_filename'] = $regs[1];
			preg_match('/\..*$/', $_FILES['ufile']['name'], $regs);
			$_POST['dest_filename'].=$regs[0];

			printf("Replacing %s with %s as %s.\n",
				$row['txtFile'],
				$_FILES['ufile']['name'],
				$_POST['dest_filename']);
				$_POST['file_type'] = $_FILES['ufile']['type'];
			//First just unlink the old file:
			@ unlink($row['txtFile']);

			$this->CopyTempFileToDest($_POST['temp_filename'], $_POST['dest_filename']);
		}

		//Update all other data:

		//Note that the columns updated do not include the original publish date
		//the published date remains the original date that the content was
		//published.
		$straColumns = array(
							'txtSeries',
							'fVolume',
							'txtTitle',
							'txtAuthorLast',
							'txtAuthorFirst',
							'txtDesc',
							'nPrDateYear',
							'nPrDateMonth',
							'nPrDateDay',
							'dtUpdated',
							'txtFile',
							'txtFileType');


		$this->PrepareDataForMySQL();

		$straValues = array(
			$_POST['series'],
			$_POST['volume'],
			$_POST['title'],
			$_POST['authorlast'],
			$_POST['authorfirst'],
			$_POST['desc'],
			$_POST['year'],
			$_POST['month'],
			$_POST['day'],
			"now()",
			$_POST['dest_filename'],
			$_POST['file_type']);

		$qry = 'update tblContent set ';

		for($i = 0; $i<11; $i++)
		{
			$qry.= sprintf("%s=%s", $straColumns[$i], $straValues[$i]);
			if($i!=10)
				$qry.=', ';
		}

		$qry.=' where id='.$_GET['id'];

		print("<p>Updating content...</p>");
		$this->DoQuery($qry);

		$this->InsertGenres($_GET['id']);
		$this->UpdateSort($_GET['id']);

		printf("<p>Updating complete. Return the the <a href=%s>content page</a></p>",
			CreateHREF(PAGE_CONTENT, 'id='.$_GET['id']));
	}

}
?>
