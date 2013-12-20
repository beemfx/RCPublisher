<?php
/* * *****************************************************************************
 * File:   page_uploadfile.php
 * Class:  CPageUploadfile
 * Purpose: Page and code for uploading or grabbing files.
 *
 * Copyright (C) 2012 Beem Software
 * **************************************************************************** */
require_once('page_base.php');
class CPageUploadFile extends CPageBase
{

	public function CPageUploadFile()
	{
		parent::CPageBase( 'File Manager' );
	}

	protected function IsPageAllowed()
	{
		return RCSession_IsPermissionAllowed( RCSESSION_CREATEFILE ) || RCSession_IsPermissionAllowed( RCSESSION_MODIFYFILE ) || RCSession_IsPermissionAllowed( RCSESSION_DELETEFILE );
	}
	
	protected function GetContentHeader()
	{
		return 'File Manager';
	}

	protected function DisplayContent()
	{
		if( !isset( $_REQUEST[ 'mode' ] ) )
			$_REQUEST[ 'mode' ] = 'list';
		if( $_REQUEST[ 'mode' ] == 'list' )
		{
			$F = new CFileManager();
			?>
			<div style="margin:1em">
				<a href=<?php echo CreateHREF( PAGE_UPLOADFILE , 'mode=upload' ) ?>>Upload File</a>
				<?php
				$F->ListFiles();
				?>
			</div>
			<?php
			return;
		}
		else if( $_REQUEST[ 'mode' ] == 'upload' )
		{
			print('<div style="margin:1em">' );

			if( RCSession_IsPermissionAllowed( RCSESSION_CREATEFILE ) )
			{

				$Stage = RCWeb_GetPost( 'stage' , 0 );

				switch( $Stage )
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
			}
			else
			{
				echo 'You do not have permissions to upload files.';
			}
			print('</div>' );
		}
	}

	private function DisplayForm()
	{
		?>
		<form action=<?php print(CreateHREF( PAGE_UPLOADFILE , 'mode=upload' ) ) ?> method="POST" enctype="multipart/form-data">
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
					<td><input type="text" name="unewfilename" value="<?php echo RCWeb_GetPost( 'unewfilename' , '' ) ?>"/></td>
				</tr>
				<tr>
					<th>Description</th>
					<td colspan="3">
						<textarea
							style="height:200px;width:100%"
							name="udesc" cols="80" rows="20"><?php printf( '%s' , RCWeb_GetPost( 'udesc' , '' ) ) ?></textarea>
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

		$nURLLen = strlen( RCWeb_GetPost( 'urlfile' ) );
		$nFileNLen = strlen( $_FILES[ 'ufile' ][ 'name' ] );

		if( 0 != ($nURLLen * $nFileNLen) )
		{
			RCError_PushError( 'WARNING: You may upload a file or grab a file from a website, but not both.' , 'warning' );
			$this->DisplayForm();
			return;
		}

		$bUpload = $nFileNLen > 0;

		if( $bUpload )
		{
			$Info = $_FILES[ 'ufile' ][ 'name' ];
		}
		else
		{
			$Info = pathinfo( RCWeb_GetPost( 'urlfile' ) , PATHINFO_BASENAME );
		}

		printf( 'Gathing file information about %s...<br/>' , $Info );
		$sFilename = strlen( RCWeb_GetPost( 'unewfilename' ) ) != 0 ? RCWeb_GetPost( 'unewfilename' ) : $Info;

		if( !preg_match( '/[a-zA-Z0-9]{2,20}\.[a-zA-Z0-9]{1,10}/' , $sFilename ) )
		{
			RCError_PushError( 'WARNING: Invalid filename. Filenames must be at least 2 characters long, contain exactly one \'.\' followed by an extension that is at least 1 character long, and may be no more than 20 characters long witha  10 character extension. It may contain only letters and numbers, no spaces or other symbols are allowed.' , 'warning' );
			$this->DisplayForm();
			return;
		}
		$sFilenameParts = preg_split( '/\./' , $sFilename );
		$sFilename = strtolower( $sFilenameParts[ 0 ] );
		RCWeb_SetPost( 'uslug' , $sFilename );
		RCWeb_SetPost( 'uext' , strtoupper( $sFilenameParts[ 1 ] ) );

		$F = new CFileManager();

		if( $F->DoesFileExist( RCWeb_GetPost( 'uslug' ) ) )
		{
			RCError_PushError( 'WARNING: A file with the slug '.RCWeb_GetPost( 'uslug' ).' already exists please rename the file to something else.' , 'warning' );
			$this->DisplayForm();
			return;
		}

		RCWeb_SetPost( 'upath' , $sFilename[ 0 ].'/'.$sFilename[ 1 ] );
		printf( "Uploading %s to %s<br/>" , $_FILES[ 'ufile' ][ 'name' ] , RCWeb_GetPost( 'upath' ) );

		if( $bUpload )
		{
			RCWeb_SetPost( 'ucontenttype' , $_FILES[ 'ufile' ][ 'type' ] );
		}
		else
		{
			//$FILE = fopen( RCWeb_GetPost('urlfile'), 'r');

			RCWeb_SetPost( 'ucontenttype' , 'unknown' );

			switch( strtoupper( RCWeb_GetPost( 'uext' ) ) )
			{
				case 'PNG':
				case 'BMP':
				case 'JPG':
				case 'GIF':
				case 'TGA':
				case 'JPEG':
				case 'XMB':
					RCWeb_SetPost( 'ucontenttype' , 'image/unknown' );
					break;
			}
			//fclose($FILE);
		}
		RCWeb_SetPost( 'stage' , 2 );
		if( $bUpload )
		{
			RCWeb_SetPost( 'utempfile' , $F->CopyUploadToTempFile( $_FILES[ 'ufile' ] ) );
		}
		else
		{
			RCWeb_SetPost( 'utempfile' , $F->CopyURLFileToTempFile( RCWeb_GetPost( 'urlfile' ) ) );
		}

		if( !RCWeb_GetPost( 'utempfile' ) )
		{
			RCError_PushError( 'Failed to create temporary file.' , 'warning' );
			$this->DisplayForm();
			return;
		}
		?>

		<form method="post" enctype="multipart/form-data" action=<?php print CreateHREF( PAGE_UPLOADFILE , 'mode=upload' ) ?>>
			<?php
			//Alright we need to pass all posted information to the next page:
			foreach( $_POST as $key => $value )
			{
				printf(
					"<input type=\"hidden\" name=\"%s\" value=\"%s\"/>\n" , $key , $value );
			}
			?>
			<input class="button" type="submit" value="Confirm"/>
		</form>
		<?php
	}

	private function DisplayComplete()
	{
		printf( 'Saving file...<br/>' );
		$bCopied = CFileManager::CopyTempFileToDest( RCWeb_GetPost( 'utempfile' ) , RCWeb_GetPost( 'upath' ) , RCWeb_GetPost( 'uslug' ) , RCWeb_GetPost( 'uext' ) );

		if( !$bCopied )
			return;

		print('Inserting entry into database...<br/>' );

		$F = new CFileManager();
		$nID = $F->InsertFileIntoSQL( RCWeb_GetPost( 'uslug' ) , RCWeb_GetPost( 'uext' ) , RCWeb_GetPost( 'ucontenttype' ) , RCWeb_GetPost( 'upath' ) , RCWeb_GetPost( 'udesc' ) );

		printf( "Inserted at %d.<br/>\n" , $nID );
	}

}
?>
