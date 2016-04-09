<?php
/*******************************************************************************
 * File:   uploadpage.php
 * Class:  CUploadPage
 * Purpose: Page and code for uploading new content.
 *
 * Copyright (C) 2009 Blaine Myers
 ******************************************************************************/
require_once('page_base.php');

abstract class CContentEditBase extends CPageBase
{
	protected function RefreshPostData()
	{
		//The idea is to copy data in from POST and remove slashes.
		//Slashes will always be manually added right before they are
		//inserted into the datables.

		//Always strip slashes from some of the post items, because slashes will
		//be manually added when inserted into the database.
		if(get_magic_quotes_gpc())
		{
			$_POST['series']      = htmlspecialchars(stripslashes($_POST['series']));
			$_POST['title']       = htmlspecialchars(stripslashes($_POST['title']));
			$_POST['desc']        = htmlspecialchars(stripslashes($_POST['desc']));
			$_POST['authorlast']  = htmlspecialchars(stripslashes($_POST['authorlast']));
			$_POST['authorfirst'] = htmlspecialchars(stripslashes($_POST['authorfirst']));
		}
	}

	protected function PrepareDataForMySQL()
	{
		//Format the descriptoin with some html.
		$_POST['desc'] = $this->NewlinesToHTMLBreaks($_POST['desc']);
		
		//Now add slashes to necessary fields.
		$_POST['series']        = $this->StringOrNull(addslashes($_POST['series']));
		$_POST['title']         = $this->StringOrNull(addslashes($_POST['title']));
		$_POST['volume']        = $this->StringOrNull($_POST['volume']);
		$_POST['desc']          = '"'.addslashes($_POST['desc']).'"';
		$_POST['authorlast']    = '"'.addslashes($_POST['authorlast']).'"';
		$_POST['authorfirst']   = '"'.addslashes($_POST['authorfirst']).'"';
		$_POST['dest_filename'] = '"'.addslashes($_POST['dest_filename']).'"';
		$_POST['file_type']     = '"'.addslashes($_POST['file_type']).'"';
		//Format the date:
		$_POST['year'] = $_POST['year']==0?'null':'"'.$_POST['year'].'"';
		$_POST['month'] = $_POST['month']==0?'null':'"'.$_POST['month'].'"';
		$_POST['day'] = $_POST['day']==0?'null':'"'.$_POST['day'].'"';
	}

	protected function InsertGenres($nID)
	{
		$this->DoQuery('delete from tblContentGenres where idContent='.$nID);

		//Now insert all the genre's.
		foreach($_POST as $key => $value)
		{
			if(!strncmp('genre_', $key, 6) && $value==='on')
			{
				$qry = sprintf(
					'insert into tblContentGenres (idContent, idGenre) values (%d, %d)',
					$nID, (int)substr($key, 6));

				//printf("%s.<br/>\n", $qry);
				$this->DoQuery($qry);
			}
		}
	}

	protected function UpdateSort($nID)
	{
		//This sort is hardly perfect as it doesn't account for decimal
		//place volumes, but it does create a sort that is generally in the
		//order that should be displayed.
		//What I would like is a sort where the volume looks something like
		//this: ###.###, but this is difficult to accomplish without using php.
		//Currently if the content has no series, and the title starts with "The "
		//then the the will not be factored into the sort.
		$qry = 'update tblContent set txtSort=';
		$qry.= 'if(txtSeries is not null,
									concat(
										trim(LEADING "The " FROM txtSeries),
										" ",
										lpad(cast(ifnull(fVolume, 0) as unsigned integer), 3, "0"),
										" ",
										ifnull(txtTitle, "")
									),
									trim(LEADING "The " FROM txtTitle))';
		$qry.= ' where id='.$nID;

		$this->DoQuery($qry);

		//The date sort is formated so that dates without months will be
		//shown before dates with months, etc.
		$qry = 'update tblContent set txtSortDate=';
		$qry.= 'concat(lpad(if(nPrDateYear is not null, nPrDateYear, 9999), 4, "0"),
					lpad(if(nPrDateMonth is not null, nPrDateMonth, 99), 2, "0"),
					lpad(if(nPrDateDay is not null, nPrDateDay, 99), 2, "0"))';
		$qry.= 'where id ='.$nID;

		$this->DoQuery($qry);
	}

	private function StringOrNull($str)
	{
		if(strlen($str)>0)
		{
			return '"'.$str.'"';
		}
		else
			return 'null';
	}

	protected function IsPostDataValid($bNeedFile=true)
	{
		$bTitleV = true;
		$bFileV  = true;
		$bDateV  = true;
		$bVolumeV = true;

		if( (strlen($_POST['series'])<1) && (strlen($_POST['title'])<1))
		{
			$this->ShowWarning('A series or title must be specified.');
			$bTitleV = false;
		}

		//Do the temp file copy and save the temp filename as a post
		//variable.
		$_POST['temp_filename'] = $this->CopyUploadToTempFile($bNeedFile);

		if(!$_POST['temp_filename'] && $bNeedFile)
		{
			$this->ShowWarning('The file upload failed.');
			$bFileV = false;
		}

		if(!checkdate($_POST['month'], $_POST['day'], $_POST['year']) && $_POST['day']!=0 && $_POST['month']!=0)
		{
			$strDate = sprintf('%s %s, %s', date('F', mktime(0, 0, 0, $_POST['month'], 0, 0)), $_POST['day'], $_POST['year']);
			$this->ShowWarning('The specified date: '.$strDate.' is not valid.');
			$bDateV = false;
		}

		if(strlen($_POST['volume'])>0 && !is_numeric($_POST['volume']))
		{
			$this->ShowWarning('Volume must be a number or blank.');
			$bVolumeV = false;
		}

		return $bTitleV && $bFileV && $bDateV && $bVolumeV;
	}



	//This copies the file to a temp location and returns the destination file
	//name.
	protected function CopyUploadToTempFile($bShowError=true)
	{
		$FILE = $_FILES['ufile'];

		if($FILE['error']!=0)
		{
			if($bShowError)$this->ShowWarning('Error '.$FILE['error'].': Could not upload file.');
			return false;
		}

		printf("<p>Uploaded \"%s\" as \"%s\" (%d bytes) of type %s.</p>\n",
				$FILE['name'],
				$FILE['tmp_name'],
				$FILE['size'],
				$FILE['type']);

		$_POST['file_type'] = $FILE['type'];
		if($FILE['type'] !== 'application/pdf')
		{
			$this->ShowWarning('PDF is the recommended file type.');
		}

		print("<p>Creating temporary file...</p>\n");

		if(!is_uploaded_file($FILE['tmp_name']))
		{
			print("<p style=\"color:red\">Error: The file wasn't uploaded.<p/>");
			return false;
		}

		$strTempName = tempnam(sys_get_temp_dir(), 'rc');

		if(!move_uploaded_file($FILE['tmp_name'], $strTempName))
		{
			$this->ShowWarning('Error: Could not move the file.');
			return false;
		}

		return $strTempName;
	}

	protected function CopyTempFileToDest($strFileTemp, $strFileDest)
	{
		print('Saving '.$strFileTemp.'to '.$strFileDest.'...<br/>');
		if(!copy($strFileTemp, $strFileDest))
		{
			$this->ShowWarning('Failed to copy file.');
			return false;
		}
		//The os will eventually get rid of this anyway, but delete
		//it just in case.
		unlink($strFileTemp);
		return true;
	}

	protected function ShowTable($strAction, $strButton1, $strButton2)
	{
		//Just have a table with each element.
		?>
		<form action=<?php print($strAction)?> method="POST" enctype="multipart/form-data">
		<table>
		<tr>
		<th>Series</th><td><input type="text" name="series" value=<?php printf('"%s"', $_POST['series'])?>/></td>
		<th>Volume</th><td><input type="text" name="volume" value=<?php printf('"%s"', $_POST['volume'])?>/></td>
		</tr>
		<tr>
		<th>Title</th>
		<td colspan="3"><input type="text" name="title" value=<?php printf('"%s"', $_POST['title'])?>/></td>
		</tr>
		<tr>
		<th>Author (Last, First)</th>
		<td colspan = "3">
		<input
			style="width:45%"
			type="text"
			name="authorlast"
			value=<?php printf('"%s"', $_POST['authorlast'])?>
			/>, <input
				style="width:45%"
				type="text"
				name="authorfirst"
				value=<?php printf('"%s"', $_POST['authorfirst'])?>/>
		</td>
		</tr>
		<tr>
		<th>Description</th>
		<td colspan="3">
		<textarea
			style="height:200px;width:100%"
			name="desc" cols="80" rows="20"><?php printf('%s', $_POST['desc'])?></textarea>
		</td>
		</tr>
		<tr>
		<th>Date</th>
		<td>
		<?php $this->DisplayDateSelection() ?>
		</td>
		<th>File</th>
		<td>
		<input type="file" name="ufile"/>
		</td>
		</tr>
		<tr>
		<th>Genre(s)</th><td colspan="3"><?php $this->DisplayGenreCBs() ?></td>
		</tr>
		<tr>
			<td style="text-align:center" colspan="4">
			<?php
			if($strButton1 != null)
				printf('<input class="button" type="submit" name="%s" value="%s"/>', $strButton1, $strButton1);
			if($strButton2 != null)
				printf('<input class="button" type="submit" name="%s" value="%s"/>', $strButton2, $strButton2);
			?>
			</td>
		</tr>
		</table>
		<!-- Need to specify that we're moving to the next stage. -->
		<input type="hidden" name="stage" value="1"/>
		</form>
		<?php
	}

	private function DisplayGenreCBs()
	{
		//The page should never get here if it wasn't connected to the database,
		//so go ahead and query it. The idea is to generate a list of checkboxes
		//of each genre type, with a name equivelant to the genre id.
		$strQuery = 'select id, txtDesc from tblGenre order by txtDesc';

		$res = $this->DoQuery($strQuery);

		if($res == true)
		{
			for($i = 0; $i < $res->num_rows; $i++)
			{
				$row = $res->fetch_assoc();
				//Write the checkbox, one on each line,
				//also check it if it was previously checked.

				$strName = 'genre_'.$row['id'];

				printf(
					"<input class=\"checkbox\" type=\"checkbox\" name=\"%s\" %s>%s</input><br/>\n",
					$strName,
					$_POST[$strName]==='on'?'checked="checked"':'',
					$row['txtDesc']
					);

			}

			$res->free();
		}
	}

	private function DisplayDateSelection()
	{
		//printf('%s-%s-%s', $_POST['month'], $_POST['day'], $_POST['year']);
		?>
		<select name="month" size="1">
			<?php
			for($i=0; $i<=12; $i++){

				printf("<option value=\"%d\" %s>%s</option>\n",
					$i,
					$_POST['month']==$i?'selected':'',
					$i!=0?date("F", mktime(0, 0, 0, $i+1, 0, 0)):'');

			}
			?>
		</select>
		<select name="day" size="1">
		<?php
		//Display the number of days, always 31.
		for($i=0; $i<=31; $i++)
		{
			printf("<option value=\"%d\" %s>%s</option>\n", $i, $_POST['day']==$i?'selected':'', $i!=0?$i:'');
		}
		?>
		</select>
		<select name="year" size="1">
		<?php
		//Display the years, start with current year, and go to 1900.
		$nStart = (int)date('Y');
		$nStop  = 1900;
		for($i=$nStart; $i>=$nStop; $i--)
		{
			printf("<option value=\"%d\" %s>%d</option>\n", $i, $_POST['year']==$i?'selected':'', $i);
		}
		?>
		</select>
		<?php
	}

}
?>
