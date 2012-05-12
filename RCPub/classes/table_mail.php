<?php

require_once( 'table_base.php' );
require_once( 'table_user.php' );

class CTableMail extends CTable
{
	public function CTableMail()
	{
		parent::CTable('tblMessage');
	}
	
	public function GetNumUnreadMessage($nUser)
	{
		assert('integer' == gettype($nUser));
		
		$this->DoSelect('id', 'idUser_To='.$nUser.' and bRead=0');
		return count($this->m_rows);
	}
	
	public function PostMail($nFromUser, $nToUser, $strName, $strFromEmail, $strSubject, $strMessage)
	{
		assert('integer' == gettype($nToUser));
		
		//We make sure the $nToUser is a real user.
		$UserTable = new CTableUser();
		
		//This will assert and bail if the user wasn't valid.
		$UserInfo = $UserTable->GetUserInfo($nToUser);
		
		//First send the message by acutal mail:
		{
			$msg = sprintf("From: %s\nReply Email: %s\nSubject: %s\n\n%s",
				$strName,
				$strFromEmail,
				$strSubject,
				$strMessage);

			$headers = 'From: '.$strFromEmail."\r\n" .
				'Reply-To: '.$strFromEmail. "\r\n" .
				'X-Mailer: PHP/'.phpversion();
			
			mail($UserInfo['txtEmail'], 'RC Mail: '.$strSubject, $msg, $headers);
		}
		
		//Modify the message so that it has paragraph markers
		//replace all newlines with <br/>s.
		$strMessage = str_replace(array("\r\n", "\n", "\r"), '<br/>', $strMessage);
		
		$strName      = '"'.addslashes($strName).'"';
		$strFromEmail = '"'.addslashes($strFromEmail).'"';
		$strSubject   = '"'.addslashes($strSubject).'"';
		$strMessage   = '"'.addslashes($strMessage).'"';
		
		$insert = array
		(
			 'idUser_To' => $nToUser,
			 'idUser_From' => (null == $nFromUser)?'null':$nFromUser,
			 'txtName' => $strName,
			 'txtEmail'  => $strFromEmail,
			 'txtSubject' => $strSubject,
			 'txtMessage' => $strMessage,
			 'bRead' => '0',
			 'dtSent' => 'now()',
		);
		
		$this->DoInsert($insert);
	}
	
	/*
	public function GetStory($nID)
	{
		assert('integer' == gettype($nID));		
		$this->DoSelect('id,date_format(dtPosted, "%M %e, %Y") as dt,txtTitle,txtBody,txtBodyHTMLCache as formatted', 'id='.$nID);
		
		$out = (0 == count($this->m_rows)) ? null : $this->m_rows[0];
		$this->m_rows = null;
		return $out;
	}
	
		
	public function ObtainRecentNews($count)
	{
		//$res = $this->DoQuery('select txtTitle, date_format(dtPosted, "%M %e, %Y") as dt, txtBody from tblNews order by dtPosted desc limit '.$nNewsStories);
		$this->DoSelect('txtTitle as title, date_format(dtPosted, "%M %e, %Y") as date, txtBody as body, txtBodyHTMLCache as formatted', '', 'dtPosted desc', (int)$count);
		return $this->m_rows;
	}
	*/
}

?>
