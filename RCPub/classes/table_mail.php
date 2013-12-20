<?php

require_once( 'table_user.php' );
class CTableMail extends CTable
{

	public function CTableMail()
	{
		parent::CTable( 'tblMessage' );
	}

	public function GetNumUnreadMessage( $nUser )
	{
		assert( 'integer' == gettype( $nUser ) );

		$this->DoSelect( 'id' , 'idUser_To='.$nUser.' and bRead=0' );
		return count( $this->m_rows );
	}

	public function GetMessageList( $nUser )
	{
		assert( 'integer' == gettype( $nUser ) );

		$this->DoSelect(
			'* , date_format(dtSent, "%a %c/%e/%Y %l:%i %p") as dt , if(txtName is not null, concat(txtName, " [", txtEmail, "]"), txtEmail) as txtDispName' , 'idUser_To='.$nUser , 'dtSent desc' );

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

	public function MarkAsRead( $nUser , $nMsg )
	{
		assert( 'integer' == gettype( $nUser ) );
		assert( 'integer' == gettype( $nMsg ) );

		$data = array
			(
			'bRead' => '1' ,
		);

		$this->DoUpdate( $nMsg , $data , 'idUser_To='.$nUser );
	}

	public function DeleteMessage( $nUser , $nMsg )
	{
		assert( 'integer' == gettype( $nUser ) );
		assert( 'integer' == gettype( $nMsg ) );
		$this->DoDelete( $nMsg , 'idUser_To='.$nUser );
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
		{
			$msg = sprintf( "From: %s\nReply Email: %s\nSubject: %s\n\n%s" , $strName , $strFromEmail , $strSubject , $strMessage );

			$headers = 'From: '.$strFromEmail."\r\n".
				'Reply-To: '.$strFromEmail."\r\n".
				'X-Mailer: PHP/'.phpversion();

			mail( $UserInfo[ 'txtEmail' ] , 'RC Mail: '.$strSubject , $msg , $headers );
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
			'dtSent' => 'now()' ,
		);

		$this->DoInsert( $insert );
		return true;
	}

}

?>
