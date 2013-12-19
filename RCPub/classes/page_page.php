<?php
/*******************************************************************************
 * File:   page_page.php
 * Class:  CPagePage
 * Purpose: Miscellanious pages. This class is used to create edit and display miscellanious pages for the
 * website.
 * 
 * TODO: Should be able to delete pages, and display a list of all pages.
 *
 * Copyright (C) 2010 Blaine Myers
 ******************************************************************************/
require_once('page_base.php');
require_once('table_page.php');
require_once('table_comment.php');

class CPagePage extends CPageBase
{
	const MODE_UNK  = 0;
	const MODE_PAGE = 1;
	const MODE_EDIT = 2;
	const MODE_NEW  = 3;
	const MODE_LIST = 4;
	
	const VERSION_DEFAULT=0;
	
	private $m_strContent;
	private $m_strPageSlug;
	private $m_nID;
	private $m_nMode = self::MODE_UNK;
	private $m_Version = self::VERSION_DEFAULT;
	
	private $m_PageTable;
	

	public function CPagePage()
	{
            parent::CPageBase('Misc Page');
	}
        
        protected function IsPageAllowed()
        {
            return true;
        }
	
	protected function ProcessInput()
	{
		//If the stage was set then we are most likely dealing with a situation
		//where either an update or a new post occured.
		$this->m_strContent   = RCWeb_GetPost('content');
		$this->m_strPageSlug  = RCWeb_GetPost('pageslug');
		$this->m_strTitle     = RCWeb_GetPost('title');
		$this->m_nID          = (int)  RCWeb_GetPost('id');
		$this->m_nMode        = (1 == RCWeb_GetPost('stage'))?self::MODE_EDIT:self::MODE_NEW;

		//We want to make sure the slug is okay, it shoudld contain only letters numbers and underscores.
		if(!preg_match(RCRX_PAGESLUG, $this->m_strPageSlug))
		{
			//If the slug isn't okay, we want to go back to the edit page.
			RCError_PushError($this->m_strPageSlug.' is an invalid slug. The slug may only contain alpha-numerica characters and underscores.' , 'warning' );	
		}
		else
		{
			if(1 == RCWeb_GetPost('stage') && RCSession_IsPermissionAllowed( RCSESSION_MODIFYPAGE ) )
			{
				//We are updating an entry.
				$this->m_PageTable->UpdatePage( $this->m_nID, $this->m_strPageSlug, $this->m_strTitle, $this->m_strContent);
			}
			else if(2 == RCWeb_GetPost('stage') && RCSession_IsPermissionAllowed( RCSESSION_CREATEPAGE ))
			{
				if( $this->m_PageTable->IsSlugTaken( $this->m_strPageSlug ) )
				{
					RCError_PushError($this->m_strPageSlug.' is already taken, please use a different slug.');
					return;
				}
				else 
				{
					$this->m_PageTable->CreatePage( $this->m_strPageSlug, $this->m_strTitle, $this->m_strContent);
				}
			}

			//Now that we've updated the table, we want the page to appear, so we set the mode to
			//page, and process the content.
			$strRedirect = 'Location: '.CreateHREF(PAGE_PAGE, 'p='.$this->m_strPageSlug, true);
			header( $strRedirect);
			exit;
		}
	}

	protected function DisplayPre()
	{		
		$this->m_strTitle = '';
		$this->m_PageTable = new CTablePage();
		//The page slug should be passed in the p parameter.
		
		$this->m_strPageSlug = RCWeb_GetGet('p');
		$this->m_Version = RCWeb_GetGet( 'v' , self::VERSION_DEFAULT );
		
		if(0 != RCWeb_GetPost('stage' , 0 ))
		{	
			$this->ProcessInput();
		}
		else
		{
			if(!isset( $_GET ['p'] ) )
			{
				$this->m_nMode = self::MODE_LIST;
				return;
			}
			//By default set the mode to page:
			$this->m_nMode = self::MODE_PAGE;
			//We need to know the mode for the page.
			$Mode = RCWeb_GetGet('mode');
			if( null != $Mode )
			{
				if(!strcmp('edit',$Mode))
				{
					$this->m_nMode = self::MODE_EDIT;
				}
				else if(!strcmp('new', $Mode))
				{
					$this->m_nMode = self::MODE_NEW;
				}
			}


			//We now attempt to get the page.
			$Page = $this->m_PageTable->GetPage($this->m_strPageSlug);

			if( self::VERSION_DEFAULT != $this->m_Version )
			{
				$HistoryTable = new CTablePageHistory();
				$Event = $HistoryTable->GetPage((int)$Page['id'], $this->m_Version);
				$RCMarkup = new CRCMarkup($Event['txtBody']);
				$Page['formatted'] = $RCMarkup->GetHTML();
				$Page['title'] = $Event['txtTitle'];
			}

			//Now if the mode was page, and the page didn't exist, and the user level
			//is high enough, we can do a new page instead.
			if(self::MODE_PAGE == $this->m_nMode && null == $Page && RCSession_IsPermissionAllowed( RCSESSION_CREATEPAGE ) )
			{
				$this->m_nMode = self::MODE_NEW;
				$this->m_nID = 0;
			}
			else if($Page==null)
			{
				$this->m_strTitle = 'Unknown Page';
				$this->m_strContent = "Couldn't find the specified page.";
				$this->m_nID = 0;
				return;
			}
			
			//Bail out if the operation is not allowed.
			if
			(
				(!RCSession_IsPermissionAllowed( RCSESSION_MODIFYPAGE ) && self::MODE_EDIT == $this->m_nMode)
				||
				(!RCSession_IsPermissionAllowed( RCSESSION_CREATEPAGE ) && self::MODE_NEW  == $this->m_nMode)	  
			)
			{
				$this->m_nMode = self::MODE_PAGE;
				$this->m_strTitle = 'Unknown Page';
				$this->m_strContent = "Couldn't find the specified page.";
				$this->m_nID = 0;
				return;
			}

			//Now if we are displaying a page, we process all the macros.
			if(self::MODE_PAGE == $this->m_nMode)
			{
				$this->m_strContent = $Page['formatted'];
				$this->m_strTitle   = $Page['title'];
				$this->m_nID        = (int)$Page['id'];
			}
			else if(self::MODE_EDIT == $this->m_nMode)
			{
				$EditData = $this->m_PageTable->GetContentForEdit((int)$Page['id'] , $this->m_Version );
				
				$this->m_strContent = $EditData['txtBody'];
				$this->m_strTitle   = $EditData['txtTitle'];
				$this->m_nID        = (int)$Page['id'] ;
			}
			else if(self::MODE_NEW == $this->m_nMode)
			{

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
	
	protected function DisplayPage()
	{
		$this->DisplayContentBlock();
                if( 0 != $this->m_nID )
                {
                    $this->DisplayCommentBlock();
                }
	}
	
	protected function DisplayContentBlock()
	{
		//$Comment = new CTableComment();
		//$Comment->InsertComment( $this->m_nID , 'My totally new comment!' , 'Ryan' , 'beemfx@gmail.com' );	
		if(RCSession_IsPermissionAllowed( RCSESSION_MODIFYPAGE ) && 0 != $this->m_nID )
		{	
			$strVersion = self::VERSION_DEFAULT == $this->m_Version ? '' : '&v='.$this->m_Version;
			$strEditLink = sprintf(
				' [<a href=%s>Edit</a>]',
				CreateHREF(PAGE_PAGE, 'mode=edit&p='.$this->m_strPageSlug.$strVersion));
		}
		else
		{
			$strEditLink='';
		}
		
		printf('<h1>%s%s</h1>', $this->m_strTitle, $strEditLink);
		
		echo '<div style="margin:0;padding:1em">'."\n";
		echo $this->m_strContent;
		echo '</div>';
	}
	
	protected function DisplayCommentBlock()
	{
		$Comment = new CTableComment();
		$Comments = $Comment->GetFormattedCommentsForPage( $this->m_nID );
			
		echo '<div id="comment_block">';
		echo '<h3>Comments</h3>';
		
		for( $i=0; $i < count($Comments); $i++)
		{
			$c = $Comments[$i];
			
			echo '<div class="comment">';
			printf( '<h4>Comment from: %s [%s]</h4>' , $c['txtName'] , 0<=$c['idUser'] ? 'Member' : 'Visitor' );
			echo '<p class="text">'.$c['txtCommentFormat'].'</p>';
			echo '<p class="date">'.$c['dt'].'</p>';
			echo '</div>';
		}
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
		$this->DisplayCommentBlock_LeaveFeedback_CommentForm();
		echo '</div>';
	}
	
	protected function DisplayCommentBlock_LeaveFeedback_CommentForm()
	{
		?>
		<form method="post" action=<?php print CreateHREF(PAGE_PAGE,'p='.$this->m_strPageSlug)?>>
		<input type="hidden" name="comment_stage" value="1"/>
		<input type="hidden" name="comment_page_id" value="<?php echo $this->m_nID?>"/>
		<span class="leave_comment_header">Name:</span>
		<?php
			if( RCSession_GetUserProp('user_id') >= 0 )
			{
				echo RCSession_GetUserProp( 'user_alias' );
			}
			else
			{
				echo '<input type="text" name="comment_name" value="'.RCWeb_GetPost('comment_name','').'"/>';
			}
		?>
		<br/>
		<span class="leave_comment_header">Email:</span>
		<?php
			if( RCSession_GetUserProp('user_id') >= 0 )
			{
				echo RCSession_GetUserProp( 'user_email' );
			}
			else
			{
				echo '<input type="text" name="comment_email" value="'.RCWeb_GetPost('comment_email','').'"/>';
			}
		?>
		<br/>
		(Your email address will not appear on this site.)<br/>
		<span class="leave_comment_header">Comment:</span>
		<textarea name="comment_comment" style="height:200px;width:100%"><?php print RCWeb_GetPost('comment_comment','')?></textarea>
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
		assert( 0 !=$this->m_nID );
		if( $this->m_nID != (int)RCWeb_GetPost( 'comment_page_id' ) )return;
		
		$Name    = RCWeb_GetPost( 'comment_name' );
		$Email   = RCWeb_GetPost( 'comment_email' );
		$Comment = RCWeb_GetPost( 'comment_comment' );
		
		if( RCSession_GetUserProp('user_id') >= 0 )
		{
			$Name  = RCSession_GetUserProp( 'user_alias' );
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
		
		if( strlen( $Name ) >= 50 )
		{
			$Name = substr( $Name , 0 , 50 );
		}
		
		if( strlen( $Email ) >= 50 )
		{
			$Email = substr( $Email , 0 , 50 );
		}
		
		$CmtTable = new CTableComment();
		$CmtTable->InsertComment( $this->m_nID , $Comment , $Name , $Email );
		//Clear all post properties...
		RCWeb_ClearPostData();
	}

	protected function DisplayContent()
	{	
		switch($this->m_nMode)
		{
			case self::MODE_PAGE : $this->DisplayPage();     break;
			case self::MODE_EDIT : $this->DisplayEditPage(); break;
			case self::MODE_NEW  : $this->DisplayNewPage();  break;
			case self::MODE_LIST : $this->DisplayPageList(); break;
		}
		
		if(self::MODE_LIST != $this->m_nMode && RCSession_IsPermissionAllowed( RCSESSION_MODIFYPAGE ))
		{
			$this->DisplayPageHistory();
		}
	}
	
	private function DisplayPageHistory()
	{
		printf( '<h3>Page History</h3>');
		$HistoryTable = new CTablePageHistory();
		$History = $HistoryTable->GetHistory( $this->m_nID );
		
		print("<ul>\n");
		for($i = 0; $i < count($History); $i++)
		{
			$Event = $History[$i];
			$Link = CreateHREF(PAGE_PAGE, 'p='.$this->m_strPageSlug.'&v='.$Event['idVersion']);
			printf("<li><a href=%s>%d (%s): %s</a></li>\n", $Link, (int)$Event['idVersion'], $Event['dtPretty'], $Event['txtTitle']);
		}
		print("</ul>\n");
	}
	
	private function DisplayPageList()
	{
		$Pages = $this->m_PageTable->GetPages();
		
		printf('<h1>All Pages</h1>');
		printf( '<ul>' );
		for($i = 0; $i < count($Pages); $i++)
		{
			printf('<li><a href=%s>%s</a></li>', CreateHREF(PAGE_PAGE, 'p='.$Pages[$i]['txtSlug']), $Pages[$i]['txtTitle']);
		}
		printf( '</ul>' );
	}
	
	private function DisplayEditForm()
	{
		?>
		<div style="width:100%;margin:0;padding:1em">
		<form action=<?php print CreateHREF(PAGE_PAGE, 'p='.$this->m_strPageSlug)?> method="post">
		<input type="hidden" name="stage" value="<?php echo self::MODE_NEW==$this->m_nMode?'2':'1'?>"/>
		<input type="hidden" name="id" value="<?php echo $this->m_nID?>"/>
		<p><b>Page Title: </b><input type="text" name="title" value="<?php echo $this->m_strTitle?>" style="width:50%"/></p>
		<p><b>Page Slug: </b><input type="text" name="pageslug" value="<?php echo $this->m_strPageSlug?>" style="width:50%"/></p>
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
