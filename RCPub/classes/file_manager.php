<?php

require_once('table_base.php');

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

		$res->free();
		
		return $Out;
	}
	
	public function ResolveSlugToURL($strSlug)
	{
		$qry = 'select concat(txtLocalPath,"/",txtName,".",txtExt) as txtPath from tblFiles where txtSlug="'.$strSlug.'"';
		
		$res = $this->DoQuery($qry);
		if(!$res)
		{
			return '';	
		}
		
		if($res->num_rows == 0)
		{
				$sOut = '';
		}
		else
		{
			$row = $res->fetch_assoc();	
			$sOut = $this->GetURLFileRoot().$row['txtPath'];
		}
		$res->free();
		return $sOut;
	}
	
	public function ResolveSlugToServerName($strSlug)
	{
		$qry = 'select concat(txtLocalPath,"/",txtSlug,".",txtExt) as txtPath from tblFiles where txtSlug="'.$strSlug.'"';
		
		$res = $this->DoQuery($qry);
		if(!$res)
		{
			return '';	
		}
		if($res->num_rows == 0)
		{
				$sOut = '';
		}
		else
		{
			$row = $res->fetch_assoc();
			$sOut = $this->GetServerFileRoot().$row['txtPath'];
		}
		$res->free();
		return $sOut;
	}
	
	public function ListFiles()
	{
		$this->DoSelect('concat(txtLocalPath,"/",txtSlug,".",txtExt) as txtPath', '', 'txtSlug');
				
		print '<ul>';
		for($i=0; $i < count($this->m_rows); $i++)
		{
			$row = $this->m_rows[$i];//$res->fetch_assoc();
			
			//Lets verify the file exists.
			$bExists = file_exists($this->GetServerFileRoot().$row['txtPath']);
			
			if($bExists)
				printf('<li><a href="%s">%s</a>', $this->GetURLFileRoot().$row['txtPath'], $this->GetServerFileRoot().$row['txtPath']);
			else
				printf('<li>WARNING: %s does not exist.', $this->GetURLFileRoot().$row['txtPath']);
		}
		print '</ul>';
	}
	
	public function ClearDatabase()
	{
		print 'Perging database... This is final and cannot be undone. Files are not deleted, they must be deleted manually.';
		$qry = 'delete from tblFiles';
		$this->DoQuery($qry);
	}
	
	public function InsertFileIntoSQL($strSlug, $strExt, $strType,$strPath,$strDesc)
	{
		//Now add slashes to necessary fields.
		$strSlug = '"'.addslashes($strSlug).'"';
		$strExt  = '"'.addslashes($strExt).'"';
		$strType = '"'.addslashes($strType).'"';
		$strPath = '"'.addslashes($strPath).'"';
		$strDesc = '"'.addslashes($strDesc).'"';
		
		$strColumns = 'txtSlug,txtName,txtExt,txtType,dt,txtLocalPath,txtDesc';
		$strValues = sprintf('%s,%s,%s,%s,%s,%s,%s',
			$strSlug,
			$strSlug,
			$strExt,
			$strType,
			'now()',
			$strPath,
			$strDesc);
		
		$this->DoQuery('lock tables tblFiles write');
		$qry = 'insert into tblFiles ('.$strColumns.') values ('.$strValues.')';
		$this->DoQuery($qry);
		$nID = $this->m_db->insert_id;
		$this->DoQuery('unlock tables');
		return $nID;
	}
	
	public function DoesFileExist($strSlug)
	{
		$qry = 'select txtSlug from tblFiles where txtSlug="'.$strSlug.'"';
		
		$res = $this->DoQuery($qry);
		if(!$res)
		{
			return false;		
		}
		
		$bExists = 1 == $res->num_rows;
		
		$res->free();
		return $bExists;
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
		global $GLOBAL_SETTINGS_FILEPATH;
		$strFinalPath = $_SERVER['DOCUMENT_ROOT'].'/'.$GLOBAL_SETTINGS_FILEPATH.'/'.$strPathDest;
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
		global $GLOBAL_SETTINGS_FILEPATH;
		return $_SERVER['DOCUMENT_ROOT'].'/'.$GLOBAL_SETTINGS_FILEPATH.'/';
	}
	
	protected static function GetURLFileRoot()
	{
		global $GLOBAL_SETTINGS_FILEPATH;
		$p = $_SERVER['HTTPS'] ? "https" : "http";
		return $p.'://'.$_SERVER['HTTP_HOST'].'/'.$GLOBAL_SETTINGS_FILEPATH.'/';
	}
	
	var $m_db;
};

?>
