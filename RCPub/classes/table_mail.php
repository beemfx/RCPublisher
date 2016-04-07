<?php

require_once( 'table_user.php' );
require_once( 'phpmailer/PHPMailerAutoload.php' );

class CTableMail extends CTable
{

	public function CTableMail()
	{
		parent::CTable( 'tblMessage' );
	}

	public function GetNumUnreadMessage( $nUser )
	{
		assert( 'integer' == gettype( $nUser ) );

		$this->DoSelect( 'id' , 'idUser_To='.$nUser.' and bRead=0 and not bDeleted' );
		return count( $this->m_rows );
	}

	public function GetMessageList( $nUser )
	{
		assert( 'integer' == gettype( $nUser ) );

		$this->DoSelect(
			'* , date_format(dtSent, "%a %c/%e/%Y %l:%i %p") as dt , if(txtName is not null, concat(txtName, " [", txtEmail, "]"), txtEmail) as txtDispName' , 'not bDeleted and idUser_To='.$nUser , 'dtSent desc' );

		return $this->m_rows;
	}

	public function GetMessage( $nUser , $nMsg )
	{
		assert( 'integer' == gettype( $nUser ) );
		assert( 'integer' == gettype( $nMsg ) );

		$this->DoSelect(
			'*, date_format(dtSent, "%a %c/%e/%Y %l:%i %p") as dt, if(txtName is not null, concat(txtName, " [", txtEmail, "]"), txtEmail) as txtDispName' , 'idUser_To='.$nUser.' and id='.$nMsg );

		return count( $this->m_rows ) != 1 ? false : $this->m_rows[ 0 ];
	}

	public function GetUnsentMessages()
	{
		$this->DoSelect( '*' , 'bExtMailed=0' );
		return $this->m_rows;
	}

	public function MarkAsRead( $nUser , $nMsg )
	{
		assert( 'integer' == gettype( $nUser ) );
		assert( 'integer' == gettype( $nMsg ) );

		$data = array( 'bRead' => '1' , );

		$this->DoUpdate( $nMsg , $data , 'idUser_To='.$nUser );
	}

	public function MarkAsMailed( $nUser , $nMsg )
	{
		assert( 'integer' == gettype( $nUser ) );
		assert( 'integer' == gettype( $nMsg ) );

		$data = array( 'bExtMailed' => '1' , );

		$this->DoUpdate( $nMsg , $data , 'idUser_To='.$nUser );
	}

	public function DeleteMessage( $nUser , $nMsg )
	{
		assert( 'integer' == gettype( $nUser ) );
		assert( 'integer' == gettype( $nMsg ) );
		$ActualDelete = false;
		if( $ActualDelete )
		{
			$this->DoDelete( $nMsg , 'idUser_To='.$nUser );
		}
		else
		{
			$data = array ( 'bDeleted' => '1' , );
			$this->DoUpdate( $nMsg , $data , 'idUser_To='.$nUser );
		}
	}
	
	public function PostMail( $nFromUser , $nToUser , $strName , $strFromEmail , $strSubject , $strMessage )
	{
		assert( 'integer' == gettype( $nToUser ) );
		
		$strMessage = strip_tags(substr( $strMessage, 0, MAX_EMAIL_LEN ));
		$strName = strip_tags(substr( $strName , 0, 25 ));
		$strFromEmail = substr( $strFromEmail, 0,  40 );
		$strSubject = strip_tags(substr( $strSubject, 0, 100 ));
		
		//We make sure the $nToUser is a real user.
		$UserTable = new CTableUser();

		//This will assert and bail if the user wasn't valid.
		$UserInfo = $UserTable->GetUserInfo( $nToUser );

		//First send the message by actual mail:
		if( false ) // This is now done in a cron job.
		{
			$msg = sprintf( "From: %s\nReply Email: %s\nSubject: %s\n\n%s" , $strName , $strFromEmail , $strSubject , $strMessage );	
			$this->PostMail_SendRealEmail( $UserInfo[ 'txtEmail' ] , $strFromEmail , $strName , 'RC Mail: '.$strSubject , $msg );
		}

		//Modify the message so that it has paragraph markers
		//replace all newlines with <br/>s.
		$strMessage = str_replace( array( "\r\n" , "\n" , "\r" ) , '<br/>' , $strMessage );

		$strName = '"'.addslashes( $strName ).'"';
		$strFromEmail = '"'.addslashes( $strFromEmail ).'"';
		$strSubject = '"'.addslashes( $strSubject ).'"';
		$strMessage = '"'.addslashes( $strMessage ).'"';

		$insert = array
		(
			'idUser_To' => $nToUser ,
			'idUser_From' => (null == $nFromUser) ? 'null' : $nFromUser ,
			'txtName' => $strName ,
			'txtEmail' => $strFromEmail ,
			'txtSubject' => $strSubject ,
			'txtMessage' => $strMessage ,
			'bRead' => '0' ,
			'bDeleted' => '0' ,
			'bExtMailed' => '0' ,
			'dtSent' => 'now()' ,
		);

		$this->DoInsert( $insert );
		return true;
	}
	
	public function PostMail_SendRealEmail( $ToAddr , $FromAddr , $FromPerson , $Subject , $Body )
	{
		if( '0' == RCSettings_GetSetting( 'bUsePhpMail' ) )
		{
			$this->PostMail_SendRealEmail_PhpMailer( $ToAddr , $FromAddr , $FromPerson , $Subject , $Body );
		}
		else
		{
			$this->PostMail_SendRealEmail_Mail( $ToAddr , $FromAddr , $FromPerson , $Subject , $Body );
		}
	}

	private function PostMail_SendRealEmail_Mail( $ToAddr , $FromAddr , $FromPerson , $Subject , $Body )
	{
		$headers = 'From: '.$FromAddr."\r\n".
				'Reply-To: '.$FromAddr."\r\n".
				'X-Mailer: PHP/'.phpversion();
			
		mail( $ToAddr , $Subject , $Body , $headers );
	}
	
	private function PostMail_SendRealEmail_PhpMailer( $ToAddr , $FromAddr , $FromPerson , $Subject , $Body )
	{
		$mail = new PHPMailer;

		//$mail->SMTPDebug = 3;                               // Enable verbose debug output

		$mail->isSMTP();                                      // Set mailer to use SMTP
		$mail->Host = RCSettings_GetSetting( 'txtEmailServer' ); // Specify main and backup SMTP servers
		$mail->SMTPAuth = strlen(RCSettings_GetSetting( 'txtEmailUsername' )) > 0;                               // Enable SMTP authentication
		$mail->Username = RCSettings_GetSetting( 'txtEmailUsername' );                 // SMTP username
		$mail->Password = RCSettings_GetSetting( 'txtEmailPassword' );                           // SMTP password
		$mail->SMTPSecure = RCSettings_GetSetting( 'txtEmailEncryption' );                            // Enable TLS encryption, `ssl` also accepted
		$mail->Port = RCSettings_GetSetting( 'txtEmailPort' );                                 // TCP port to connect to

		$mail->From = $FromAddr;
		$mail->FromName = $FromPerson;
		$mail->addAddress($ToAddr , 'RC Software Mailer');     // Add a recipient
		$mail->addReplyTo($FromAddr, $FromPerson);
		//$mail->addCC('cc@example.com');
		//$mail->addBCC('bcc@example.com');

		//$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
		//$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
		$mail->isHTML(true);                                  // Set email format to HTML

		$mail->Subject = $Subject;
		$mail->Body    = $Body;
		$mail->AltBody = strip_tags($Body);

		if(!$mail->send()) 
		{
			$ErrString = 'Message could not be sent.' . 'Mailer Error: ' . $mail->ErrorInfo;
			RCError_PushError( $ErrString );
			//echo $ErrString;
		} 
		else 
		{
			//echo 'Message has been sent';
		}
	}
}

?>
