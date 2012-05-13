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

class CPagePage extends CPageBase
{
	const MODE_UNK  = 0;
	const MODE_PAGE = 1;
	const MODE_EDIT = 2;
	const MODE_NEW  = 3;
	const MODE_LIST = 4;
	
	const EDIT_RQ_LEVEL = 5;
	const CRNW_RQ_LEVEL = 5;
	
	private $m_strContent;
	private $m_strPageSlug;
	private $m_nID;
	private $m_nMode = self::MODE_UNK;
	
	private $m_PageTable;
	

	public function CPagePage()
	{
		parent::CPageBase('Misc Page', 0);
	}
	
	protected function ProcessInput()
	{
		//If the stage was set then we are most likely dealing with a situation
		//where either an update or a new post occured.
		$this->m_strContent   = $_POST['content'];
		$this->m_strPageSlug  = $_POST['pageslug'];
		$this->m_strTitle     = $_POST['title'];
		$this->m_nID          = (int)$_POST['id'];
		$this->m_nMode        = (1 == $_post['stage'])?self::MODE_EDIT:self::MODE_NEW;

		//We want to make sure the slug is okay, it shoudld contain only letters numbers and underscores.
		if(!preg_match('/^[A-Za-z0-9_]*$/', $this->m_strPageSlug))
		{
			//If the slug isn't okay, we want to go back to the edit page.
			$this->ShowWarning($this->m_strPageSlug.' is an invalid slug. The slug may only contain alpha-numerica characters and underscores.');	
		}
		else
		{
			if(1 == $_POST['stage'] && $this->GetUserLevel()>=self::EDIT_RQ_LEVEL)
			{
				//We are updating an entry.
				$this->m_PageTable->UpdatePage( $this->m_nID, $this->m_strPageSlug, $this->m_strTitle, $this->m_strContent);
			}
			else if(2 == $_POST['stage'] && $this->GetUserLevel()>=self::CRNW_RQ_LEVEL)
			{
				$this->m_PageTable->CreatePage( $this->m_strPageSlug, $this->m_strTitle, $this->m_strContent);
			}

			//Now that we've updated the table, we want the page to appear, so we set the mode to
			//page, and process the content.
			$this->m_nMode = self::MODE_PAGE;
			$Page = $this->m_PageTable->GetPage($this->m_strPageSlug);
			$this->m_strContent = $Page['formatted'];
		}
	}

	protected function DisplayPre()
	{		
		$this->m_strTitle = '';
		$this->m_PageTable = new CTablePage();
		//The page slug should be passed in the p parameter.
		
		$this->m_strPageSlug = $_GET['p'];
		
		if(isset($_POST['stage']))
		{	
			$this->ProcessInput();
		}
		else
		{
			if(!isset($_GET['p']))
			{
				$this->m_nMode = self::MODE_LIST;
				return;
			}
			//By default set the mode to page:
			$this->m_nMode = self::MODE_PAGE;
			//We need to know the mode for the page.
			if(isset($_GET['mode']))
			{
				if(!strcmp('edit',$_GET['mode']))
					$this->m_nMode = self::MODE_EDIT;
				else if(!strcmp('new', $_GET['mode']))
					$this->m_nMode = self::MODE_NEW;
			}


			//We now attempt to get the page.
			$Page = $this->m_PageTable->GetPage($this->m_strPageSlug);

			//Now if the mode was page, and the page didn't exist, and the user level
			//is high enough, we can do a new page instead.
			if(self::MODE_PAGE == $this->m_nMode && null == $Page && self::CRNW_RQ_LEVEL <= $this->GetUserLevel())
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
				($this->GetUserLevel() < self::EDIT_RQ_LEVEL && self::MODE_EDIT == $this->m_nMode)
				||
				($this->GetUserLevel() < self::CRNW_RQ_LEVEL && self::MODE_NEW  == $this->m_nMode)	  
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
				$this->m_strContent = $Page['body'];
				$this->m_strTitle   = $Page['title'];
				$this->m_nID        = (int)$Page['id'];
			}
			else if(self::MODE_NEW == $this->m_nMode)
			{

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
		if($this->GetUserLevel()>=self::EDIT_RQ_LEVEL)
		{
			$strEditLink = sprintf(
				' [<a href=%s>Edit</a>]',
				CreateHREF(PAGE_PAGE, 'mode=edit&p='.$this->m_strPageSlug));
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

	protected function DisplayContent()
	{
		switch($this->m_nMode)
		{
			case self::MODE_PAGE : $this->DisplayPage();     break;
			case self::MODE_EDIT : $this->DisplayEditPage(); break;
			case self::MODE_NEW  : $this->DisplayNewPage();  break;
			case self::MODE_LIST : $this->DisplayPageList(); break;
		}
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
		<form action=<?php print CreateHREF(PAGE_PAGE)?> method="post">
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
