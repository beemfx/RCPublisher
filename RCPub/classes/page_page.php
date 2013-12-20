<?php
/* * *****************************************************************************
 * File:   page_page.php
 * Class:  CPagePage
 * Purpose: Miscellanious pages. This class is used to create edit and display miscellanious pages for the
 * website.
 * 
 * TODO: Should be able to delete pages, and display a list of all pages.
 *
 * Copyright (C) 2010 Blaine Myers
 * **************************************************************************** */
require_once('page_base.php');
require_once('table_page.php');
require_once('table_comment.php');
class CPagePage extends CPageBase
{

	const MODE_UNK = 0;
	const MODE_PAGE = 1;
	const MODE_EDIT = 2;
	const MODE_NEW = 3;
	const MODE_LIST = 4;
	const VERSION_DEFAULT = 0;

	private $m_strContent;
	private $m_strPageSlug;
	private $m_nID;
	private $m_nMode = self::MODE_UNK;
	private $m_Version = self::VERSION_DEFAULT;
	private $m_PageTable;
	private $m_nOwnerId;

	public function CPagePage()
	{
		parent::CPageBase( 'Misc Page' );
	}

	protected function IsPageAllowed()
	{
		return true;
	}

	protected function CanEdit()
	{
		return $this->m_PageTable->IsSlugTaken( $this->m_strPageSlug ) && RCSession_IsPermissionAllowed( RCSESSION_MODIFYPAGE );
	}

	protected function ProcessInput()
	{
		//If the stage was set then we are most likely dealing with a situation
		//where either an update or a new post occured.
		$this->m_strContent = RCWeb_GetPost( 'content' );
		//$this->m_strPageSlug = RCWeb_GetPost( 'pageslug' );
		$this->m_strTitle = RCWeb_GetPost( 'title' );
		$this->m_nID = ( int ) RCWeb_GetPost( 'id' );
		$this->m_nMode = (1 == RCWeb_GetPost( 'stage' )) ? self::MODE_EDIT : self::MODE_NEW;

		$IsSlugOriginal = $this->m_strPageSlug == RCWeb_GetPost( 'pageslug' ) || !$this->m_PageTable->IsSlugTaken( RCWeb_GetPost( 'pageslug' ) );


		if( !$IsSlugOriginal )
		{
			RCError_PushError( '"'.RCWeb_GetPost( 'pageslug' ).'" is already taken, please use a different slug.' );
			return;
		}
		else if( !preg_match( RCRX_PAGESLUG , RCWeb_GetPost( 'pageslug' ) ) )
		{
			//We want to make sure the slug is okay, it shoudld contain only letters numbers and underscores.			
			//If the slug isn't okay, we want to go back to the edit page.
			RCError_PushError( '"'.RCWeb_GetPost( 'pageslug' ).'" is an invalid slug. The slug may only contain alpha-numerica characters and underscores.' , 'warning' );
		}
		else
		{
			if( 1 == RCWeb_GetPost( 'stage' ) && $this->CanEdit() )
			{
				//We are updating an entry.
				$this->m_PageTable->UpdatePage( $this->m_nID , RCWeb_GetPost( 'pageslug' ) , $this->m_strTitle , $this->m_strContent, RCSession_GetUserProp( 'user_id' ) );
			}
			else if( 2 == RCWeb_GetPost( 'stage' ) && RCSession_IsPermissionAllowed( RCSESSION_CREATEPAGE ) )
			{
				if( $this->m_PageTable->IsSlugTaken( RCWeb_GetPost( 'pageslug' ) ) )
				{
					RCError_PushError( RCWeb_GetPost( 'pageslug' ).' is already taken, please use a different slug.' );
					return;
				}
				else
				{
					$this->m_PageTable->CreatePage( RCWeb_GetPost( 'pageslug' ) , $this->m_strTitle , $this->m_strContent, RCSession_GetUserProp( 'user_id' ) );
				}
			}

			$this->m_strPageSlug = RCWeb_GetPost( 'pageslug' );

			//Now that we've updated the table, we want the page to appear, so we set the mode to
			//page, and process the content.
			$strRedirect = 'Location: '.CreateHREF( PAGE_PAGE , 'p='.$this->m_strPageSlug , true );
			header( $strRedirect );
			exit;
		}
	}
	
	protected function DisplayPre_HandleCommentEditing()
	{
		//Technically this is pretty bad, since we can delete any comment from
		//anywhere, but at least the deleter comment needs permissions.
		$DoDelete = RCWeb_GetGet( 'dcomment' , null );
		$DoApprove = RCWeb_GetGet( 'acomment' , null );
		
		if( null == $DoDelete && null == $DoApprove )
		{
			return;
		}
		
		$TableComment = new CTableComment();
		
		if( null != $DoApprove && RCSession_IsPermissionAllowed( RCSESSION_MODIFYFEEDBACK ))
		{
			$TableComment->ApproveComment((int)$DoApprove);
			RCError_PushError( 'Comment approved.' , 'message' );
		}
		else if( null != $DoApprove )
		{
			RCError_PushError( 'You do not have permissions to approve comments.' , 'warning' );
		}
		
		if( null != $DoDelete && RCSession_IsPermissionAllowed( RCSESSION_DELETEFEEDBACK ))
		{
			$TableComment->DeleteComment((int)$DoDelete);
			RCError_PushError( 'Comment deleted.' , 'message' );
		}
		else if( null != $DoDelete )
		{
			RCError_PushError( 'You do not have permissions to delete feedback.' , 'warning' );
		}
		
		$strRedirect = 'Location: '.CreateHREF( PAGE_PAGE , strlen( $this->m_strPageSlug) > 0 ? 'p='.$this->m_strPageSlug: '' , true );
		header( $strRedirect );
		exit;
	}

	protected function DisplayPre()
	{
		
		$this->m_strTitle = '';
		$this->m_PageTable = new CTablePage();
		//The page slug should be passed in the p parameter.

		$this->m_strPageSlug = RCWeb_GetGet( 'p' );
		$this->m_Version = RCWeb_GetGet( 'v' , self::VERSION_DEFAULT );
		
		$this->DisplayPre_HandleCommentEditing();

		if( 0 != RCWeb_GetPost( 'stage' , 0 ) )
		{
			$this->ProcessInput();
		}
		else
		{
			if( !isset( $_GET [ 'p' ] ) )
			{
				$this->m_nMode = self::MODE_LIST;
				return;
			}
			//By default set the mode to page:
			$this->m_nMode = self::MODE_PAGE;
			//We need to know the mode for the page.
			$Mode = RCWeb_GetGet( 'mode' );
			if( null != $Mode )
			{
				if( !strcmp( 'edit' , $Mode ) )
				{
					$this->m_nMode = self::MODE_EDIT;
				}
				else if( !strcmp( 'new' , $Mode ) )
				{
					$this->m_nMode = self::MODE_NEW;
				}
			}

			//Correct states that aren't actually allowed.
			if( self::MODE_EDIT == $this->m_nMode && !$this->CanEdit() )
			{
				$this->m_nMode = self::MODE_PAGE;
			}

			if( self::MODE_NEW == $this->m_nMode && !RCSession_IsPermissionAllowed( RCSESSION_CREATEPAGE ) )
			{
				$this->m_nMode = self::MODE_PAGE;
			}


			//We now attempt to get the page.
			$Page = $this->m_PageTable->GetPage( $this->m_strPageSlug );

			if( self::VERSION_DEFAULT != $this->m_Version )
			{
				$HistoryTable = new CTablePageHistory();
				$Event = $HistoryTable->GetPage( ( int ) $Page[ 'id' ] , $this->m_Version );
				$RCMarkup = new CRCMarkup( $Event[ 'txtBody' ] );
				$Page[ 'formatted' ] = $RCMarkup->GetHTML();
				$Page[ 'title' ] = $Event[ 'txtTitle' ];
			}

			//Now if the mode was page, and the page didn't exist, and the user level
			//is high enough, we can do a new page instead.
			if( self::MODE_PAGE == $this->m_nMode && null == $Page && RCSession_IsPermissionAllowed( RCSESSION_CREATEPAGE ) )
			{
				$this->m_nMode = self::MODE_NEW;
				$this->m_nID = 0;
				$this->m_nOwnerId = 0;
			}
			else if( $Page == null )
			{
				$this->m_strTitle = 'Unknown Page';
				$this->m_strContent = "Couldn't find the specified page.";
				$this->m_nID = 0;
				$this->m_nOwnerId = 0;
				return;
			}

			//Bail out if the operation is not allowed.
			if
			(
				(!RCSession_IsPermissionAllowed( RCSESSION_MODIFYPAGE ) && self::MODE_EDIT == $this->m_nMode) ||
				(!RCSession_IsPermissionAllowed( RCSESSION_CREATEPAGE ) && self::MODE_NEW == $this->m_nMode)
			)
			{
				$this->m_nMode = self::MODE_PAGE;
				$this->m_strTitle = 'Unknown Page';
				$this->m_strContent = "Couldn't find the specified page.";
				$this->m_nID = 0;
				$this->m_nOwnerId = 0;
				return;
			}

			//Now if we are displaying a page, we process all the macros.
			if( self::MODE_PAGE == $this->m_nMode )
			{
				$this->m_strContent = $Page[ 'formatted' ];
				$this->m_strTitle = $Page[ 'title' ];
				$this->m_nID = ( int ) $Page[ 'id' ];
				$this->m_nOwnerId = (int)$Page['idOwner'];
			}
			else if( self::MODE_EDIT == $this->m_nMode )
			{
				$EditData = $this->m_PageTable->GetContentForEdit( ( int ) $Page[ 'id' ] , $this->m_Version );

				$this->m_strContent = $EditData[ 'txtBody' ];
				$this->m_strTitle = $EditData[ 'txtTitle' ];
				$this->m_nID = ( int ) $Page[ 'id' ];
				$this->m_nOwnerId = (int)$Page['idOwner'];
			}
			else if( self::MODE_NEW == $this->m_nMode )
			{
				$this->m_nOwnerId = RCSession_GetUserProp( 'user_id' );
			}

			//We now have all the info, so we can process comments.
			if( 0 != RCWeb_GetPost( 'comment_stage' ) )
			{
				$this->ProcessComment();
			}
		}
	}

	protected function DisplayEditPage()
	{
		//This function is to edit a currently created page.
		$this->DisplayEditForm();
	}

	protected function DisplayNewPage()
	{
		//This function is to create a new page.
		//$this->m_strContent = '';
		//$this->m_strTitle = '';
		echo 'Creating new page...';
		$this->DisplayEditForm();
	}

	protected function GetContentHeader()
	{
		$Out = '';

		switch( $this->m_nMode )
		{
			case self::MODE_PAGE : 
				$Out = $this->CreatePageHeader();
				break;
			case self::MODE_EDIT : 
				$Out = '';
				break;
			case self::MODE_NEW : 
				$Out = '';
				break;
			case self::MODE_LIST : 
				$Out = 'All Pages';
				break;
		}
		
		return $Out;
	}

	protected function DisplayPage()
	{
		$this->DisplayContentBlock();
		if( 0 != $this->m_nID )
		{
			$this->DisplayCommentBlock();
		}
	}

	protected function CreatePageHeader()
	{
		if( RCSession_IsPermissionAllowed( RCSESSION_MODIFYPAGE ) && 0 != $this->m_nID )
		{
			$strVersion = self::VERSION_DEFAULT == $this->m_Version ? '' : '&v='.$this->m_Version;
			$strEditLink = sprintf(
				' [<a href=%s>Edit</a>]' , CreateHREF( PAGE_PAGE , 'mode=edit&p='.$this->m_strPageSlug.$strVersion ) );
		}
		else
		{
			$strEditLink = '';
		}

		return sprintf( '%s%s' , $this->m_strTitle , $strEditLink );
	}

	protected function DisplayContentBlock()
	{
		echo '<div style="margin:0;padding:1em">'."\n";
		echo $this->m_strContent;
		echo '</div>';
	}
	
	protected function RenderComments( $Comments , $ForModeration = false )
	{
		for( $i = 0; $i < count( $Comments ); $i++ )
		{
			$c = $Comments[ $i ];

			echo '<div class="comment">';
			printf( '<h4>Comment from: %s [%s]</h4>' , $c[ 'txtName' ] , 0 <= $c[ 'idUser' ] ? 'Member' : 'Visitor'  );
			if( $ForModeration )
			{
				echo '<p><b>Page:</b> '.$this->m_PageTable->GetPageTitle( (int)$c['idContent'] );
			}
			echo '<p class="text">'.$c[ 'txtCommentFormat' ].'</p>';
			echo '<p class="date">'.$c[ 'dt' ].'</p>';
			$DLink = 'dcomment='.$c['id'];
			$ALink = 'acomment='.$c['id'];
			
			if( !$ForModeration )
			{
				$DLink .= '&p='.$this->m_strPageSlug;
				$ALink .= '&p='.$this->m_strPageSlug;
			}
			
			if( RCSession_IsPermissionAllowed( RCSESSION_DELETEFEEDBACK ) )
			{
				echo '[<a href='.CreateHREF( PAGE_PAGE , $DLink ).'>Delete</a>]';
			}
			if( RCSession_IsPermissionAllowed( RCSESSION_MODIFYFEEDBACK ) && !$c['bApproved'] )
			{
				echo '[<a href='.CreateHREF( PAGE_PAGE , $ALink ).'>Approve</a>]';
			}
			echo '</div>';
			echo "\n";
		}
	}

	protected function DisplayCommentBlock()
	{
		$Comment = new CTableComment();
		$ShowAllFeedback = RCSession_IsPermissionAllowed( RCSESSION_MODIFYFEEDBACK|RCSESSION_DELETEFEEDBACK );
		$Comments = $Comment->GetFormattedCommentsForPage( $this->m_nID , !$ShowAllFeedback );

		echo '<div id="comment_block">';
		echo '<h3>Comments</h3>';
		$this->RenderComments( $Comments );
		echo '</div>';

		if( RCSession_IsPermissionAllowed( RCSESSION_CREATEFEEDBACK ) )
		{
			$this->DisplayCommentBlock_LeaveFeedback();
		}
	}

	protected function DisplayCommentBlock_LeaveFeedback()
	{
		echo "\n";
		echo '<div id="comment_block_leave_feedback">';
		echo '<h3>Leave Feedback</h3>';
		echo 'Comments are limited to '.MAX_COMMENT_LEN.' characters, and do not appear until they are approved by a moderator. An email address is required to leave feedback, but it will not appear on this site.';
		$this->DisplayCommentBlock_LeaveFeedback_CommentForm();
		echo '</div>';
	}

	protected function DisplayCommentBlock_LeaveFeedback_CommentForm()
	{
		?>
		<form method="post" action=<?php print CreateHREF( PAGE_PAGE , 'p='.$this->m_strPageSlug ) ?>>
			<input type="hidden" name="comment_stage" value="1"/>
			<input type="hidden" name="comment_page_id" value="<?php echo $this->m_nID ?>"/>
			<span class="leave_comment_header">Name:</span>
			<?php
			if( RCSession_GetUserProp( 'user_id' ) >= 0 )
			{
				echo RCSession_GetUserProp( 'user_alias' );
			}
			else
			{
				echo '<input type="text" name="comment_name" value="'.RCWeb_GetPost( 'comment_name' , '' ).'"/>';
			}
			?>
			<br/>
			<span class="leave_comment_header">Email:</span>
			<?php
			if( RCSession_GetUserProp( 'user_id' ) >= 0 )
			{
				echo RCSession_GetUserProp( 'user_email' );
			}
			else
			{
				echo '<input type="text" name="comment_email" value="'.RCWeb_GetPost( 'comment_email' , '' ).'"/>';
			}
			?>
			<br/>
			<span class="leave_comment_header">Comment:</span>
			<textarea name="comment_comment" style="height:100px;width:100%" onKeyDown="RCTextArea_LimitText(this.form.comment_comment,this.form.comment_countdown,<?php echo MAX_COMMENT_LEN?>);" onKeyUp="RCTextArea_LimitText(this.form.comment_comment,this.form.comment_countdown,<?php echo MAX_COMMENT_LEN?>);"><?php print RCWeb_GetPost( 'comment_comment' , '' ); ?></textarea>
			You have <input readonly type="text" name="comment_countdown" size="3" value="<?php echo MAX_COMMENT_LEN?>"> characters left.
			<?php
			RCSpam_DisplayQuestion();
			echo( '<br/>' );
			RCSpam_DisplayResponseArea();
			echo( '<br/>' );
			?>
			<input class="button" type="submit" value="Post Comment"/>
		</form>
		<?php
	}

	protected function ProcessComment()
	{
		assert( 0 != $this->m_nID );
		if( $this->m_nID != ( int ) RCWeb_GetPost( 'comment_page_id' ) )
			return;

		$Name = RCWeb_GetPost( 'comment_name' );
		$Email = RCWeb_GetPost( 'comment_email' );
		if( strlen(RCWeb_GetPost( 'comment_comment' ) ) > MAX_COMMENT_LEN )
		{
			RCError_PushError( 'Your comment was too long and has been truncated.' , 'warning' );
		}
		$Comment = substr(RCWeb_GetPost( 'comment_comment' ), 0, MAX_COMMENT_LEN);

		if( RCSession_GetUserProp( 'user_id' ) >= 0 )
		{
			$Name = RCSession_GetUserProp( 'user_alias' );
			$Email = RCSession_GetUserProp( 'user_email' );
		}

		//Make sure the input is good. Then post.
		if( strlen( $Name ) < 1 )
		{
			RCError_PushError( 'A name is required to leave a comment.' , 'warning' );
			return;
		}

		if( !RCWeb_ValidateEmail( $Email ) )
		{
			RCError_PushError( 'The email address provided was not valid.' , 'warning' );
			return;
		}

		if( strlen( $Comment ) < 3 )
		{
			RCError_PushError( 'You must actually leave a comment.' , 'warning' );
			return;
		}

		if( !RCSpam_IsAnswerCorrect() )
		{
			RCError_PushError( 'You entered an incorrect response for the humanity check.' , 'warning' );
			return;
		}

		$CmtTable = new CTableComment();
		$CmtTable->InsertComment( $this->m_nID , $Comment , $Name , $Email , $this->m_strTitle , $this->m_nOwnerId );
		RCError_PushError( 'Comment submitted.' , 'message' );
		//Clear all post properties...
		RCWeb_ClearPostData();
	}

	protected function DisplayContent()
	{
		switch( $this->m_nMode )
		{
			case self::MODE_PAGE : $this->DisplayPage();
				break;
			case self::MODE_EDIT : $this->DisplayEditPage();
				break;
			case self::MODE_NEW : $this->DisplayNewPage();
				break;
			case self::MODE_LIST : $this->DisplayPageList();
				break;
		}

		if( self::MODE_LIST != $this->m_nMode && RCSession_IsPermissionAllowed( RCSESSION_MODIFYPAGE ) && self::MODE_NEW != $this->m_nMode )
		{
			$this->DisplayPageHistory();
		}
	}
	
	private function DisplayPageHistory()
	{
		printf( '<h3>Page History</h3>' );
		$OwnerInfo = $this->m_PageTable->GetOwner( $this->m_nID );
		$UserTable = new CTableUser();
		
		$CreatorInfo = $UserTable->GetUserInfo( $OwnerInfo['idCreator'] );
		
		printf( 'Created by %s (%s)' , $CreatorInfo['txtAlias'] , $CreatorInfo['txtUserName'] );
		
		$HistoryTable = new CTablePageHistory();
		$History = $HistoryTable->GetHistory( $this->m_nID );

		print("<ul>\n" );
		for( $i = 0; $i < count( $History ); $i++ )
		{
			$Event = $History[ $i ];
			$UserInfo = $UserTable->GetUserInfo( $Event['idUser'] );
			$Link = CreateHREF( PAGE_PAGE , 'p='.$this->m_strPageSlug.'&v='.$Event[ 'idVersion' ] );
			printf( "<li><a href=%s>%d (%s): \"%s\"</a> by %s (%s)</li>\n" , $Link , ( int ) $Event[ 'idVersion' ] , $Event[ 'dtPretty' ] , $Event[ 'txtTitle' ], $UserInfo['txtAlias'] , $UserInfo['txtUserName'] );
		}
		print("</ul>\n" );
	}

	private function DisplayPageList()
	{
		$Pages = $this->m_PageTable->GetPages();

		printf( '<ul>' );
		for( $i = 0; $i < count( $Pages ); $i++ )
		{
			printf( '<li><a href=%s>%s</a></li>' , CreateHREF( PAGE_PAGE , 'p='.$Pages[ $i ][ 'txtSlug' ] ) , $Pages[ $i ][ 'txtTitle' ] );
		}
		printf( '</ul>' );
		
		if( RCSession_IsPermissionAllowed( RCSESSION_MODIFYFEEDBACK ))
		{
			$CommentTable = new CTableComment();
			
			echo '<div id="comment_block">';
			print( '<h3>Comments Needing Moderation</h3>' );
			$Comments = $CommentTable->GetFormattedNotApprovedComments();
			if( 0 == count( $Comments) )
			{
				print 'None!';
			}
			else
			{
				$this->RenderComments( $Comments , true );
			}
			echo '</div>';
		
		}
	}

	private function DisplayEditForm()
	{
		?>
		<div style="width:100%;margin:0;padding:1em">
			<form action=<?php print CreateHREF( PAGE_PAGE , 'p='.$this->m_strPageSlug ) ?> method="post">
				<input type="hidden" name="stage" value="<?php echo self::MODE_NEW == $this->m_nMode ? '2' : '1' ?>"/>
				<input type="hidden" name="id" value="<?php echo $this->m_nID ?>"/>
				<p><b>Page Title: </b><input type="text" name="title" value="<?php echo $this->m_strTitle ?>" style="width:50%"/></p>
				<p><b>Page Slug: </b><input type="text" name="pageslug" value="<?php echo $this->m_strPageSlug ?>" style="width:50%"/></p>
				<p><b>Page Content:</b></br>
					<textarea style="height:200px;width:90%" name="content" cols="80" rows="20"><?php echo $this->m_strContent ?></textarea>
				</p>
				<center><input class="button" type="submit" value="Submit"/></center>
			</form>
		</div>
		<?php
	}

}
?>
