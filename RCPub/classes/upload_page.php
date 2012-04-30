<?php
/*******************************************************************************
 * File:   uploadpage.php
 * Class:  CUploadPage
 * Purpose: Page and code for uploading new content.
 *
 * Copyright (C) 2009 Blaine Myers
 ******************************************************************************/
require_once('content_edit_base.php');

class CUploadPage extends CContentEditBase
{
	public function CUploadPage()
	{
		parent::CPageBase('Upload', true, 5);
	}

	protected function DisplayContent()
	{
		print("<h1>Upload New Content</h1>\n");
		print('<div style="margin:1em">');
		$this->RefreshPostData();

		//Just use a table for the upload, content will be gotten using post.
		//There are three upload stages, enter content, confirm, and the
		//actuall upload. The stage is specified in the get.

		//In the final version, there should be a check to make sure a valid
		//user is logged on to upload new content, note that if the stage
		//wasn't posted the default value will be zero, so this always works.

		switch($_POST['stage'])
		{
			default:
			case 0:
				$this->DisplayStage0();
				break;
			case 1:
				//IsPostDataValid checks the posted data,
				//and also copies the uploaded file to a temporary
				//location, that value is saved in $_POST['temp_filename'].
				if($this->IsPostDataValid())
				{
					$this->DisplayStage1();
				}
				else
				{
					$this->DisplayStage0();
				}
				break;
			case 2:
				$this->DisplayStage2();
				break;
		}
		print('</div>');
	}

	private function DisplayStage0()
	{
		$this->ShowTable(CreateHREF(PAGE_UPLOAD), 'Next', null);
	}

	private function DisplayStage1()
	{
		//Form a full title, based on whether a title or series was or wasn't
		//specified.
		$strFullTitle = '';
		//Once here the file has already been loaded, and information has
		//been verified to be correct.
		if(strlen($_POST['series'])>0)
		{
			$strFullTitle .= $_POST['series'];
			if(strlen($_POST['volume'])>0)
			{
				$strFullTitle .= ' '.$_POST['volume'];
			}
		}

		if(strlen($_POST['title'])>0)
		{
			if(strlen($_POST['series'])>0)
				$strFullTitle .= ': ';

			$strFullTitle .= $_POST['title'];
		}
		
		printf(
			"<h2>%s by %s %s</h2>\n",
			$strFullTitle,
			$_POST['authorfirst'],
			$_POST['authorlast']);
		printf("<p>%s</p>\n", $_POST['desc']);
		print("<p>Genres:<ul>");
		foreach($_POST as $key => $value)
		{
			if(!strncmp('genre_', $key, 6) && $value==='on')
			printf("<li>%s</li>\n", $this->GenreToName(substr($key, 6)));
		}
		print("</ul></p>\n");
		$strDate = date('M j, Y',mktime(0, 0, 0, $_POST['month'], $_POST['day'], $_POST['year']));
		printf(
			"<p>Produced %s, and posted %s</p>\n",
			$strDate,
			date('M j, Y'));

		//Need to create the destination filename:
		$_POST['dest_filename'] = $this->CreateDestFilename($_FILES['ufile']['name']);
		printf('<p>File: "%s"</p>', $_POST['dest_filename']);
		
		//Update to the next stage.
		$_POST['stage'] = 2;
		?>

		<form method="post" enctype="multipart/form-data" action=<?php print CreateHREF(PAGE_UPLOAD)?>>
		<?php
		//Alright we need to pass all posted information to the next page:
		foreach($_POST as $key => $value)
		{
			printf(
				"<input type=\"hidden\" name=\"%s\" value=\"%s\"/>\n",
				$key, $value);
		}
		?>
		<input class="button" type="submit" value="Confirm"/>
		</form>
		<?php
	}

	private function DisplayStage2()
	{
		$bRes = $this->CopyTempFileToDest($_POST['temp_filename'], $_POST['dest_filename']);

		$this->PrepareDataForMySQL();

		$strColumns = 'idUser,
							txtSeries,
							fVolume,
							txtTitle,
							txtAuthorLast,
							txtAuthorFirst,
							txtDesc,
							nPrDateYear,
							nPrDateMonth,
							nPrDateDay,
							txtFile,
							txtFileType,
							dtPublished,
							dtUpdated';
		$strValues = sprintf('%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, now(), now()',
							$_SESSION['user_id'],
							$_POST['series'],
							$_POST['volume'],
							$_POST['title'],
							$_POST['authorlast'],
							$_POST['authorfirst'],
							$_POST['desc'],
							$_POST['year'],
							$_POST['month'],
							$_POST['day'],
							$_POST['dest_filename'],
							$_POST['file_type']);


		$this->DoQuery('lock tables tblContent write');
		$qry = 'insert into tblContent ('.$strColumns.') values ('.$strValues.')';
		$this->DoQuery($qry);
		$nID = $this->m_db->insert_id;
		$this->DoQuery('unlock tables');
		
		printf("Inserted at %d.<br/>\n", $nID);

		$this->InsertGenres($nID);
		$this->UpdateSort($nID);

		printf("<p>Go to the new <a href=%s>content page</a>.</p>\n",
			CreateHREF(PAGE_CONTENT, 'id='.$nID));

		//Show now post a news story about the posting.
		//Should twitter it too maybe.
	}

	private function CreateDestFilename($strFilename)
	{
		$this->DoQuery('lock tables tblGlobalSettings write');

		//We want to query the database for the next filename,
		//increment the next filename in the database, and then
		//genreate the filename with the appropriate extension.
		$strFinal = $this->GetGlobalSetting('nNextUL');
		$nNewNumber = (int)$strFinal + 1;
		$this->ChangeGlobalSetting('nNextUL', $nNewNumber);

		$this->DoQuery('unlock tables');

		//Now go ahead and add the appropriate extions to the filename.
		preg_match('/\..*$/', $strFilename, $regs);
		//$strFinal = getcwd().'/files/'.$strFinal.$regs[0];
		$strFinal = 'files/'.$strFinal.$regs[0];

		return $strFinal;
	}

}
?>
