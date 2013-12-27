<?php

class CFileManager extends CTable
{

	public function CFileManager()
	{
		parent::CTable( 'tblFiles' );
	}

	public function ReCreateAllThumbs()
	{
		$this->DoSelect( 'txtSlug' );

		$Slugs = $this->m_rows;

		for( $i = 0; $i < count( $Slugs ); $i++ )
		{
			$this->ReCreateThumbFor( $Slugs[ $i ][ 'txtSlug' ] );
		}
	}

	public function DeleteAllThumbs()
	{
		$this->DoSelect( 'txtSlug' );

		$Slugs = $this->m_rows;

		for( $i = 0; $i < count( $Slugs ); $i++ )
		{
			$this->DeleteThumbFor( $Slugs[ $i ][ 'txtSlug' ] );
		}
	}

	public function DeleteThumbFor( $strSlug )
	{
		$Info = $this->ResolveFileInfo( $strSlug );

		//Only create a thumb if it's an image.
		if( file_exists( $Info[ 'path' ].'.thumb.jpg' ) )
		{
			unlink( $Info[ 'path' ].'.thumb.jpg' );
		}
	}

	protected function ReCreateThumbFor( $strSlug )
	{
		$Info = $this->ResolveFileInfo( $strSlug );

		//Only create a thumb if it's an image.
		$ConvertPath = RCSettings_GetSetting( 'txtConvertPath' );
		$ThumbSize = ( int ) RCSettings_GetSetting( 'nThumbnailWidth' );
		$Quality = ( int ) RCSettings_GetSetting( 'nThumbnailQuality' );

		if( preg_match( '/image/' , $Info[ 'type' ] ) )
		{
			if( strlen( $ConvertPath ) > 0 )
			{
				$sCmd = sprintf( '%s %s -resize %s -quality %s %s.thumb.jpg' , $ConvertPath , $Info[ 'path' ] , $ThumbSize , $Quality , $Info[ 'path' ] );
				system( $sCmd );
			}
			else
			{
				//If we don't have ImageMagick convert, just copy.
				copy( $Info[ 'path' ] , $Info[ 'path' ].'.thumb.jpg' );
			}
		}
	}

	public function InsureThumbFor( $strSlug )
	{
		$Info = $this->ResolveFileInfo( $strSlug );

		if( !file_exists( $Info[ 'path' ].'.thumb.jpg' ) )
		{
			$this->ReCreateThumbFor( $strSlug );
		}
	}

	public function ResolveFileInfo( $strSlug )
	{
		$this->DoSelect( 'concat(txtName,".",txtExt) as txtFilename, concat(txtLocalPath,"/",txtName,".",txtExt) as txtPath, txtExt, txtType, txtDesc' , 'txtSlug="'.$strSlug.'"' );

		if( count( $this->m_rows ) == 0 )
		{
			return null;
		}

		$row = $this->m_rows[ 0 ];

		$Out[ 'filename' ] = $row[ 'txtFilename' ];
		$Out[ 'url' ] = $this->GetURLFileRoot().$row[ 'txtPath' ];
		$Out[ 'path' ] = $this->GetServerFileRoot().$row[ 'txtPath' ];
		$Out[ 'desc' ] = $row[ 'txtDesc' ];
		$Out[ 'type' ] = $row[ 'txtType' ];
		$Out[ 'ext' ] = $row[ 'txtExt' ];

		return $Out;
	}

	public function ResolveSlugToURL( $strSlug )
	{
		$this->DoSelect( 'concat(txtLocalPath,"/",txtName,".",txtExt) as txtPath' , 'txtSlug="'.$strSlug.'"' );

		if( count( $this->m_rows ) == 0 )
		{
			$sOut = '';
		}
		else
		{
			$sOut = $this->GetURLFileRoot().$this->m_rows[ 0 ][ 'txtPath' ];
		}

		return $sOut;
	}

	public function ResolveSlugToServerName( $strSlug )
	{
		$this->DoSelect( 'concat(txtLocalPath,"/",txtSlug,".",txtExt) as txtPath' , 'txtSlug="'.$strSlug.'"' );

		if( count( $this->m_rows ) == 0 )
		{
			$sOut = '';
		}
		else
		{
			$sOut = $this->GetServerFileRoot().$this->m_rows[ 0 ][ 'txtPath' ];
		}

		return $sOut;
	}

	public function ListFiles()
	{
		$this->DoSelect( 'txtSlug, txtName, txtExt, txtDesc, txtType, concat(txtLocalPath,"/",txtSlug,".",txtExt) as txtPath' , '' , 'txtSlug' );
		print '<table>';
		print '<tr><th>Thumbnail</th><th>Slug</th><th>Type</th><th>Description</th><th>File Path</th></tr>';
		for( $i = 0; $i < count( $this->m_rows ); $i++ )
		{
			print '<tr>';
			$row = $this->m_rows[ $i ];

			$ImagePreview = '';

			if( preg_match( '/image\/.*/' , $row[ 'txtType' ] ) )
			{
				$ImagePreview = sprintf( '<img src="%s%s.thumb.jpg" width="150" />' , $this->GetURLFileRoot() , $row[ 'txtPath' ] );
			}

			printf( '<td>%s</td>' , $ImagePreview );

			printf( '<td><b>%s</b></td>' , $row[ 'txtSlug' ] );
			printf( '<td>%s<br />(%s)</td>' , $row[ 'txtExt' ] , $row[ 'txtType' ] );
			printf( '<td>%s</td>' , $row[ 'txtDesc' ] );

			//Lets verify the file exists.
			$bExists = file_exists( $this->GetServerFileRoot().$row[ 'txtPath' ] );

			if( $bExists )
				printf( '<td><a href="%s">%s</a></td>' , $this->GetURLFileRoot().$row[ 'txtPath' ] , $row[ 'txtPath' ] );
			else
				printf( '<td>WARNING: %s does not exist.</td>' , $this->GetURLFileRoot().$row[ 'txtPath' ] );

			print '</ul>';
		}
		print '</table>';
	}

	public function InsertFileIntoSQL( $strSlug , $strExt , $strType , $strPath , $strDesc )
	{
		//Now add slashes to necessary fields.
		$strSlug = '"'.addslashes( $strSlug ).'"';
		$strExt = '"'.addslashes( $strExt ).'"';
		$strType = '"'.addslashes( $strType ).'"';
		$strPath = '"'.addslashes( $strPath ).'"';
		$strDesc = '"'.addslashes( $strDesc ).'"';

		$data = array
			(
			'txtSlug' => $strSlug ,
			'txtName' => $strSlug ,
			'txtExt' => $strExt ,
			'txtType' => $strType ,
			'txtLocalPath' => $strPath ,
			'dt' => 'now()' ,
			'txtDesc' => $strDesc ,
		);

		$nID = $this->DoInsert( $data );

		return $nID;
	}

	public function DoesFileExist( $strSlug )
	{
		$this->DoSelect( 'txtSlug' , 'txtSlug="'.$strSlug.'"' );

		return 1 == count( $this->m_rows );
	}

	public static function CopyUploadToTempFile( $FILE )
	{
		if( $FILE[ 'error' ] != 0 )
		{
			RCError_PushError( 'Error '.$FILE[ 'error' ].': Could not upload file.' , 'error' );
			return false;
		}

		print("<p>Creating temporary file...</p>\n" );

		if( !is_uploaded_file( $FILE[ 'tmp_name' ] ) )
		{
			print("<p style=\"color:red\">Error: The file wasn't uploaded.<p/>" );
			return false;
		}

		$strTempName = tempnam( sys_get_temp_dir() , 'rc' );

		printf( "<p>Moving file to temp location \"%s\" as \"%s\" (%d bytes) of type %s to %s.</p>\n" , $FILE[ 'name' ] , $FILE[ 'tmp_name' ] , $FILE[ 'size' ] , $FILE[ 'type' ] , $strTempName );

		if( !move_uploaded_file( $FILE[ 'tmp_name' ] , $strTempName ) )
		{
			RCError_PushError( 'Error: Could not move the file.' , 'error' );
			return false;
		}

		return $strTempName;
	}

	public static function CopyURLFileToTempFile( $strURL )
	{

		print("<p>Creating temporary file...</p>\n" );

		$strTempName = tempnam( sys_get_temp_dir() , 'rc' );

		printf( "<p>Moving file to temp location \"%s\" as \"%s\".</p>\n" , $strURL , $strTempName );

		if( !copy( $strURL , $strTempName ) )
		{
			RCError_PushError( 'Error: Could not move the file.' , 'error' );
			return false;
		}

		return $strTempName;
	}

	public static function CopyTempFileToDest( $strFileTemp , $strPathDest , $strSlug , $strExt )
	{
		//The final destination path is made up of a combination of the root
		//directory and the settings path.
		global $g_rcFilepath;
		$strFinalPath = $_SERVER[ 'DOCUMENT_ROOT' ].'/'.$g_rcFilepath.'/'.$strPathDest;
		//First thing to do is make sure the destination path exists.
		if( !is_dir( $strFinalPath ) )
		{
			printf( 'Creating direcotry %s<br/>' , $strFinalPath );
			mkdir( $strFinalPath , 0777 , true );
		}

		//form the full filename:
		$strFullPath = $strFinalPath.'/'.$strSlug.'.'.$strExt;

		print('Saving '.$strFileTemp.'to '.$strFullPath.'...<br/>' );

		if( file_exists( $strFullPath ) )
		{
			RCError_PushError( 'This file already exists, overwriting, but the database may be corrupted.' , 'error' );
			unlink( $strFullPath );
		}
		if( !copy( $strFileTemp , $strFullPath ) )
		{
			RCError_PushError( 'Failed to copy file.' , 'error' );
			return false;
		}
		//The os will eventually get rid of this anyway, but delete
		//it just in case.
		unlink( $strFileTemp );
		return true;
	}

	protected static function GetServerFileRoot()
	{
		global $g_rcFilepath;
		return $_SERVER[ 'DOCUMENT_ROOT' ].'/'.$g_rcFilepath.'/';
	}

	protected static function GetURLFileRoot()
	{
		global $g_rcFilepath;
		$p = !empty( $_SERVER[ 'HTTPS' ] ) ? ('on' == $_SERVER[ 'HTTPS' ] ? "https" : "http" ) : "http";
		return $p.'://'.$_SERVER[ 'HTTP_HOST' ].'/'.$g_rcFilepath.'/';
	}

	var $m_db;

}

;
?>
