<?php

class CTableComment extends CTable
{
	public function CTableComment()
	{
		parent::CTable('tblComment');
	}
	
	public function InsertComment( $ContentId , $CommentText , $Name , $Email )
	{
		assert( 'integer' == gettype($ContentId) );
		assert( 'string' == gettype($CommentText) );
		assert( 'string' == gettype($Name) );
		assert( 'string' == gettype($Email) );
		
		
		$Cached = new CRCMarkup($CommentText);
		
		$CommentText = '"'.addslashes($CommentText).'"';
		$CachedText = '"'.addslashes($Cached->GetHTML()).'"';
		$Name = '"'.addslashes($Name).'"';
		$Email = '"'.addslashes($Email).'"';
		$UserId = RCSession_GetUserProp('user_id');
			
		$Insert = array
		(
			'idContent'        => $ContentId   ,
			'idUser'           => $UserId      ,
			'txtName'          => $Name        ,
			'txtEmail'         => $Email       ,
			'txtComment'       => $CommentText ,
			'txtCommentFormat' => $CachedText  ,
			'dtPosted'		    => 'now()'      ,
			'bApproved'        => 'false'      ,
		);
		
		$this->DoInsert($Insert);
	}
	
	public function GetFormattedCommentsForPage( $PageId )
	{
		assert( 'integer' == gettype($PageId) );
		$this->DoSelect( 'id, idUser , txtCommentFormat, txtName, date_format(dtPosted, "%W %M %D, %Y @ %r") as dt' , 'not bApproved and idContent='.$PageId , 'dtPosted desc' );
		return $this->m_rows;
	}
	
	public function DeleteComment( $Id )
	{
		assert( 'integer' == gettype($Id) );
		$this->DoDelete( $Id );
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