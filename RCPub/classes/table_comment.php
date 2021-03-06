<?php

class CTableComment extends CTable
{

	public function CTableComment()
	{
		parent::CTable( 'tblComment' );
	}

	public function InsertComment( $ContentId , $CommentText , $Name , $Email , $PageTitle, $OwnerId )
	{
		assert( 'integer' == gettype( $ContentId ) );
		assert( 'string' == gettype( $CommentText ) );
		assert( 'string' == gettype( $Name ) );
		assert( 'string' == gettype( $Email ) );
		
		$CommentText = substr( $CommentText , 0 , MAX_COMMENT_LEN );
		$Name = substr( strip_tags( $Name ) , 0, 50 );
		$Email = substr( $Email , 0, 50 );
		
		$Cached = new CRCMarkup( $CommentText );
		
		$CommentTextForEmail = $CommentText;

		$CommentText = '"'.addslashes( $CommentText ).'"';
		$CachedText = '"'.addslashes( $Cached->GetHTML() ).'"';
		$Name = '"'.addslashes( $Name ).'"';
		$Email = '"'.addslashes( $Email ).'"';
		$UserId = RCSession_GetUserProp( 'user_id' );

		$Insert = array
			(
			'idContent' => $ContentId ,
			'idUser' => $UserId ,
			'txtName' => $Name ,
			'txtEmail' => $Email ,
			'txtComment' => $CommentText ,
			'txtCommentFormat' => $CachedText ,
			'dtPosted' => 'now()' ,
			'bApproved' => 'false' ,
		);

		$this->DoInsert( $Insert );
		
		//Email about comment:
		if( 0 != $OwnerId )
		{
			$TableUser = new CTableUser();
			$UserInfo = $TableUser->GetUserInfo($OwnerId);
			if( null != $UserInfo )
			{
				$msg = sprintf( "From: %s\nEmail %s\nComment: %s\n" , $Name , $Email , $CommentTextForEmail );

				$headers = 'From: '.$Email."\r\n".
					'Reply-To: '.$Email."\r\n".
					'X-Mailer: PHP/'.phpversion();

				mail( $UserInfo[ 'txtEmail' ] , 'New Comment: '.$PageTitle , $msg , $headers );
				//RCError_PushError('Notified '.$UserInfo['txtEmail']);
			}
		}
	}

	public function GetFormattedCommentsForPage( $PageId , $OnlyApproved = true )
	{
		assert( 'integer' == gettype( $PageId ) );
		//TODO: Should re-add bApproved to the filter.
		$Condition = 'idContent='.$PageId;
		if( $OnlyApproved )
		{
			$Condition .= ' && bApproved';
		}
		
		$this->DoSelect( 'id, idUser , bApproved, txtCommentFormat, txtName, date_format(dtPosted, "%W %M %D, %Y @ %r") as dt' , $Condition, 'dtPosted desc' );
		return $this->m_rows;
	}
	
	public function GetFormattedNotApprovedComments()
	{
		$this->DoSelect( 'id, idUser , idContent, bApproved, txtCommentFormat, txtName, date_format(dtPosted, "%W %M %D, %Y @ %r") as dt' , '!bApproved', 'dtPosted desc' );
		return $this->m_rows;
	}

	public function DeleteComment( $Id )
	{
		assert( 'integer' == gettype( $Id ) );
		$this->DoDelete( $Id );
	}
	
	public function ApproveComment( $Id )
	{
		assert( 'integer' == gettype( $Id ) );
		$data = array( 'bApproved' => '1' );
		$this->DoUpdate( $Id , $data );
	}

	public function ResetCache()
	{
		//Basically there better be a id, and txtBodyHTMLCache.
		//Get all the ids
		//$this->DoSelect('id');
		//
		//$ids = $this->m_rows;
		//
		//for($i=0; $i<count($ids); $i++)
		//{
		//	$nID = (int)$ids[$i]['id'];
		//	
		//	$this->DoSelect('txtBody', 'id='.$nID);
		//	$sRC = $this->m_rows[0]['txtBody'];
		//	$RCMarkup = new CRCMarkup($sRC);
		//	$sRC = $RCMarkup->GetHTML();
		//	$data = array
		//	(
		//		 'txtBodyHTMLCache' => '"'.addslashes($sRC).'"',
		//	);
		//	$this->DoUpdate($nID, $data);
		//}
	}

}

?>