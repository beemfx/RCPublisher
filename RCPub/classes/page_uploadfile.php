<?php
/*******************************************************************************
 * File:   page_uploadfile.php
 * Class:  CPageUploadfile
 * Purpose: Page and code for uploading or grabbing files.
 *
 * Copyright (C) 2012 Beem Software
 ******************************************************************************/
require_once('page_base.php');

class CPageUploadFile extends CPageBase
{
	public function CPageUploadFile()
	{
		parent::CPageBase('File Manager', true, 5);
	}

	protected function DisplayContent()
	{		
		if(!isset($_REQUEST['mode']))$_REQUEST['mode'] = 'list';
		if($_REQUEST['mode'] == 'list')
		{
			$F = new CFileManager();
			?>
			<h1>File Manager</h1>
			<div style="margin:1em">
			<a href=<?php echo CreateHREF(PAGE_UPLOADFILE,'mode=upload')?>>Upload File</a>
			<?php
			$F->ListFiles();
			?>
			</div>
			<?php
			return;
		}
		else if($_REQUEST['mode'] == 'upload')
		{
			print("<h1>Upload New File</h1>\n");
			print('<div style="margin:1em">');

			switch($_POST['stage'])
			{
			default:
			case 0:
				$this->DisplayForm();
				break;
			case 1:
				$this->DisplayConfirm();
				break;
			case 2:
				$this->DisplayComplete();
				break;
			}
			print('</div>');
		}
	}

	private function DisplayForm()
	{
		?>
		<form action=<?php print(CreateHREF(PAGE_UPLOADFILE, 'mode=upload'))?> method="POST" enctype="multipart/form-data">
		<table>
		<tr>
		<th>File</th>
		<td>
		<input type="file" name="ufile"/>
		</td>
		</tr>
		<tr>
		<th>URL (Cannot be used with File.)</th>
		<td>
		<input type="text" name="urlfile"/>
		</td>
		</tr>
		<tr>
		<th>New Filename (Optional)</th>
		<td><input type="text" name="unewfilename" value="<?php echo $_POST['unewfilename']?>"/></td>
		</tr>
		<tr>
		<th>Description</th>
		<td colspan="3">
		<textarea
			style="height:200px;width:100%"
			name="udesc" cols="80" rows="20"><?php printf('%s', $_POST['udesc'])?></textarea>
		</td>
		</tr>
		<tr>
			<td style="text-align:center" colspan="4">
			<input class="button" type="submit" name="Next" value="Next"/>	
			</td>
		</tr>
		</table>
		<!-- Need to specify that we're moving to the next stage. -->
		<input type="hidden" name="stage" value="1"/>
		</form>
		<?php
	}

	private function DisplayConfirm()
	{
		//Form a full title, based on whether a title or series was or wasn't
		//specified.
		
		$nURLLen = strlen($_POST['urlfile']);
		$nFileNLen = strlen($_FILES['ufile']['name']);
		
		if(0 != ($nURLLen*$nFileNLen))
		{
			$this->ShowWarning('WARNING: You may upload a file or grab a file from a website, but not both.');
			$this->DisplayForm();
			return;
		}
		
		$bUpload = $nFileNLen > 0;
		
		if($bUpload)
		{
			$Info = $_FILES['ufile']['name'];
		}
		else
		{
			$Info = pathinfo($_POST['urlfile'], PATHINFO_BASENAME);
		}
		
		printf('Gathing file information about %s...<br/>', $Info);
		$sFilename = strlen($_POST['unewfilename']) != 0 ? $_POST['unewfilename'] : $Info;

		if(!preg_match('/[a-zA-Z0-9]{2,20}\.[a-zA-Z0-9]{1,10}/', $sFilename))
		{
			$this->ShowWarning( 'WARNING: Invalid filename. Filenames must be at least 2 characters long, contain exactly one \'.\' followed by an extension that is at least 1 character long, and may be no more than 20 characters long witha  10 character extension. It may contain only letters and numbers, no spaces or other symbols are allowed.');
			$this->DisplayForm();
			return;
		}
		$sFilenameParts = preg_split('/\./', $sFilename);
		$_POST['uslug'] = $sFilename = strtolower($sFilenameParts[0]);
		$_POST['uext']  = strtoupper($sFilenameParts[1]);
		
		$F = new CFileManager();

		if($F->DoesFileExist($_POST['uslug']))
		{
			$this->ShowWarning('WARNING: A file with the slug '.$_POST['uslug'].' already exists please rename the file to something else.');
			$this->DisplayForm();
			return;
		}

		$_POST['upath'] = $sFilename[0].'/'.$sFilename[1];	
		printf("Uploading %s to %s<br/>" , $_FILES['ufile']['name'], $_POST['upath']);

		if($bUpload)
		{
			$_POST['ucontenttype'] = $_FILES['ufile']['type'];
		}
		else
		{
			//$FILE = fopen( $_POST['urlfile'], 'r');
			
			$_POST['ucontenttype'] = 'unknown';//mime_content_type($_POST['urlfile']);// $FILE['type'];
			//fclose($FILE);
		}
		$_POST['stage'] = 2;
		if($bUpload)
		{
			$_POST['utempfile'] = $F->CopyUploadToTempFile($_FILES['ufile']);
		}
		else
		{
			$_POST['utempfile'] = $F->CopyURLFileToTempFile($_POST['urlfile']);
		}
		
		if(!$_POST['utempfile'])
		{
			$this->ShowWarning( 'Failed to create temporary file.');
			$this->DisplayForm();
			return;	
		}
		?>

		<form method="post" enctype="multipart/form-data" action=<?php print CreateHREF(PAGE_UPLOADFILE, 'mode=upload')?>>
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
	

	private function DisplayComplete()
	{
		printf('Saving file...<br/>');
		$bCopied = CFileManager::CopyTempFileToDest($_POST['utempfile'], $_POST['upath'], $_POST['uslug'], $_POST['uext']);
	
		if(!$bCopied)return;
		
		print('Inserting entry into database...<br/>');
		
		$F = new CFileManager();
		$nID = $F->InsertFileIntoSQL($_POST['uslug'], $_POST['uext'], $_POST['ucontenttype'],$_POST['upath'],$_POST['udesc']);
		
		printf("Inserted at %d.<br/>\n", $nID);
	}



}
?>
