<?php

class CFileManager extends CTable
{
	public function CFileManager()
	{
		parent::CTable('tblFiles');
	}
	
	public function ResolveFileInfo($strSlug)
	{
		$this->DoSelect('concat(txtName,".",txtExt) as txtFilename, concat(txtLocalPath,"/",txtName,".",txtExt) as txtPath, txtExt, txtType, txtDesc', 'txtSlug="'.$strSlug.'"');
		
		if(count($this->m_rows) == 0)
		{
			return null;
		}
		
		$row = $this->m_rows[0];
		
		$Out['filename'] = $row['txtFilename'];
		$Out['url'] = $this->GetURLFileRoot().$row['txtPath'];
		$Out['path'] = $this->GetServerFileRoot().$row['txtPath'];
		$Out['desc'] = $row['txtDesc'];
		$Out['type'] = $row['txtType'];
		$Out['ext'] = $row['txtExt'];
		
		return $Out;
	}
	
	public function ResolveSlugToURL($strSlug)
	{
		$this->DoSelect('concat(txtLocalPath,"/",txtName,".",txtExt) as txtPath', 'txtSlug="'.$strSlug.'"');
		
		if(count($this->m_rows) == 0)
		{
			$sOut = '';
		}
		else
		{
			$sOut = $this->GetURLFileRoot().$this->m_rows[0]['txtPath'];
		}
		
		return $sOut;
	}
	
	public function ResolveSlugToServerName($strSlug)
	{
		$this->DoSelect( 'concat(txtLocalPath,"/",txtSlug,".",txtExt) as txtPath' , 'txtSlug="'.$strSlug.'"');
		
		if(count($this->m_rows) == 0)
		{
			$sOut = '';
		}
		else
		{
			$sOut = $this->GetServerFileRoot().$this->m_rows[0]['txtPath'];
		}

		return $sOut;
	}
	
	public function ListFiles()
	{
		$this->DoSelect('concat(txtLocalPath,"/",txtSlug,".",txtExt) as txtPath', '', 'txtSlug');
				
		print '<ul>';
		for($i=0; $i < count($this->m_rows); $i++)
		{
			$row = $this->m_rows[$i];
			
			//Lets verify the file exists.
			$bExists = file_exists($this->GetServerFileRoot().$row['txtPath']);
			
			if($bExists)
				printf('<li><a href="%s">%s</a>', $this->GetURLFileRoot().$row['txtPath'], $this->GetServerFileRoot().$row['txtPath']);
			else
				printf('<li>WARNING: %s does not exist.', $this->GetURLFileRoot().$row['txtPath']);
		}
		print '</ul>';
	}
	
	public function InsertFileIntoSQL($strSlug, $strExt, $strType,$strPath,$strDesc)
	{
		//Now add slashes to necessary fields.
		$strSlug = '"'.addslashes($strSlug).'"';
		$strExt  = '"'.addslashes($strExt).'"';
		$strType = '"'.addslashes($strType).'"';
		$strPath = '"'.addslashes($strPath).'"';
		$strDesc = '"'.addslashes($strDesc).'"';
		
		$data = array
		(
			 'txtSlug' => $strSlug,
			 'txtName' => $strSlug,
			 'txtExt'  => $strExt,
			 'txtType' => $strType,
			 'txtLocalPath' => $strPath,
			 'dt' => 'now()',
			 'txtDesc' => $strDesc,
		);
		
		$this->DoInsert($data);
		
		$nID = $this->m_db->insert_id;

		return $nID;
	}
	
	public function DoesFileExist($strSlug)
	{
		$this->DoSelect('txtSlug' , 'txtSlug="'.$strSlug.'"');
		
		return 1 == count($this->m_rows);
	}
	
	public static function CopyUploadToTempFile($FILE)
	{
		if($FILE['error']!=0)
		{
			if($bShowError)$this->ShowWarning('Error '.$FILE['error'].': Could not upload file.');
			return false;
		}

		print("<p>Creating temporary file...</p>\n");

		if(!is_uploaded_file($FILE['tmp_name']))
		{
			print("<p style=\"color:red\">Error: The file wasn't uploaded.<p/>");
			return false;
		}

		$strTempName = tempnam(sys_get_temp_dir(), 'rc');
		
		printf("<p>Moving file to temp location \"%s\" as \"%s\" (%d bytes) of type %s to %s.</p>\n",
				$FILE['name'],
				$FILE['tmp_name'],
				$FILE['size'],
				$FILE['type'],
				$strTempName);

		if(!move_uploaded_file($FILE['tmp_name'], $strTempName))
		{
			$this->ShowWarning('Error: Could not move the file.');
			return false;
		}

		return $strTempName;
	}
	
	public static function CopyURLFileToTempFile($strURL)
	{

		print("<p>Creating temporary file...</p>\n");

		$strTempName = tempnam(sys_get_temp_dir(), 'rc');
		
		printf("<p>Moving file to temp location \"%s\" as \"%s\".</p>\n",
				$strURL,
				$strTempName);

		if(!copy($strURL, $strTempName))
		{
			$this->ShowWarning('Error: Could not move the file.');
			return false;
		}

		return $strTempName;
	}
	
	public static function CopyTempFileToDest($strFileTemp, $strPathDest, $strSlug, $strExt)
	{
		//The final destination path is made up of a combination of the root
		//directory and the settings path.
		global $g_rcFilepath;
		$strFinalPath = $_SERVER['DOCUMENT_ROOT'].'/'.$g_rcFilepath.'/'.$strPathDest;
		//First thing to do is make sure the destination path exists.
		if(!is_dir($strFinalPath))
		{
			printf('Creating direcotry %s<br/>', $strFinalPath);
			mkdir($strFinalPath, 0777, true);
		}
		
		//form the full filename:
		$strFullPath = $strFinalPath.'/'.$strSlug.'.'.$strExt;
		
		print('Saving '.$strFileTemp.'to '.$strFullPath.'...<br/>');
		
		if(file_exists($strFullPath))
		{
			$this->ShowWarning( 'This file already exists, overwriting, but the database may be corrupted.' );
			unlink($strFullPath);
		}
		if(!copy($strFileTemp, $strFullPath))
		{
			$this->ShowWarning('Failed to copy file.');
			return false;
		}
		//The os will eventually get rid of this anyway, but delete
		//it just in case.
		unlink($strFileTemp);
		return true;
	}
		
	protected static function GetServerFileRoot()
	{
		global $g_rcFilepath;
		return $_SERVER['DOCUMENT_ROOT'].'/'.$g_rcFilepath.'/';
	}
	
	protected static function GetURLFileRoot()
	{
		global $g_rcFilepath;
		$p = !empty($_SERVER['HTTPS']) ? "https" : "http";
		return $p.'://'.$_SERVER['HTTP_HOST'].'/'.$g_rcFilepath.'/';
	}
	
	var $m_db;
};

?>
