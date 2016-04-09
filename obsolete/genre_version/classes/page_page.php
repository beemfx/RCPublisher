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
require('RCMarkup.php');

class CPagePage extends CPageBase
{
	const MODE_UNK  = 0;
	const MODE_PAGE = 1;
	const MODE_EDIT = 2;
	const MODE_NEW  = 3;
	
	const EDIT_RQ_LEVEL = 5;
	const CRNW_RQ_LEVEL = 5;
	
	private $m_strContent;
	private $m_strPageSlug;
	private $m_nID;
	private $m_nMode = self::MODE_UNK;
	

	public function CPagePage()
	{
		parent::CPageBase('Misc Page', true, 0);
	}
	
	protected function ProcessInput()
	{
		//If the stage was set then we are most likely dealing with a situation
		//where either an update or a new post occured.
		$this->m_strContent   = $_POST['content'];
		$this->m_strPageSlug = $_POST['pageslug'];
		$this->m_strTitle         = $_POST['title'];
		$this->m_nID              = (int)$_POST['id'];
		$this->m_nMode         = (1 == $_post['stage'])?self::MODE_EDIT:self::MODE_NEW;

		if(get_magic_quotes_gpc())
		{
			$this->m_strContent    = striplashes($this->m_strContent);
			$this->m_strPageSlug  = stripslashes($this->m_strPageSlug);
		}

		//We want to make sure the slug is okay, it shoudld contain only letters numbers and underscores.
		if(!preg_match('/^[A-Za-z0-9_]*$/', $this->m_strPageSlug))
		{
			//If the slug isn't okay, we want to go back to the edit page.
			$this->ShowWarning($this->m_strPageSlug.' is an invalid slug. The slug may only contain alpha-numerica characters and underscores.');
			
		}
		else
		{

			//echo 'The slug is '.$this->m_strPageSlug;
			//echo 'The content is: '.$this->m_strContent;
			if(1 == $_POST['stage'])
			{
				//We are updating an entry.
				$qry = sprintf('update tblPage set txtPageContent="%s", txtPageSlug="%s", txtPageTitle="%s" where id="%d"', 
						addslashes($this->m_strContent), 
						  $this->m_strPageSlug, 
						  addslashes($this->m_strTitle), 
						  $this->m_nID);

				$this->DoQuery($qry);
			}
			else if(2 == $_POST['stage'])
			{
				//We are inserting a new page.
				$qry = sprintf('insert into tblPage (txtPageContent, txtPageSlug, txtPageTitle) values ("%s", "%s", "%s")',
						  addslashes($this->m_strContent),
						  $this->m_strPageSlug,
						  addslashes($this->m_strTitle));
				
				$this->DoQuery($qry);
			}

			//Now that we've updated the table, we want the page to appear, so we set the mode to
			//page, and process the content.
			$this->m_nMode = self::MODE_PAGE;
			$Markup = new CRCMarkup($this->m_strContent, $this->m_db);
			$this->m_strContent = $Markup->GetHTML();
			
			//$this->m_strContent = $this->ProcessContent($this->m_strContent);
		}
	}

	protected function DisplayPre()
	{		
		//The page slug should be passed in the p parameter.
		$this->m_strPageSlug = $_GET['p'];
		
		if(isset($_POST['stage']))
		{	
			$this->ProcessInput();
		}
		else
		{
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
			{
				$row = $this->DoSingleRowQueryEx('tblPage', 'txtPageSlug', '"'.$this->m_strPageSlug.'"');
			}

			//Now if the mode was page, and the page didn't exist, and the user level
			//is high enough, we can do a new page instead.
			if(self::MODE_PAGE == $this->m_nMode && null == $row && self::CRNW_RQ_LEVEL <= $_SESSION['user_level'])
			{
				$this->m_nMode = self::MODE_NEW;
				$this->m_nID = 0;
			}
			else if($row==null)
			{
				$this->m_strTitle = 'Unknown Page';
				$this->m_strContent = "Couldn't find the specified page.";
				$this->m_nID = 0;
				return;
			}

			//Now if we are displaying a page, we process all the macros.
			if(self::MODE_PAGE == $this->m_nMode)
			{
				$Markup = new CRCMarkup($row['txtPageContent'], $this->m_db);
				$this->m_strContent = $Markup->GetHTML();
				$this->m_strTitle = $row['txtPageTitle'];
				$this->m_nID = (int)$row['id'];
			}
			else if(self::MODE_EDIT == $this->m_nMode)
			{
				$this->m_strContent = $row['txtPageContent'];
				$this->m_strTitle = $row['txtPageTitle'];
				$this->m_nID = (int)$row['id'];
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
		$this->m_strContent = '';
		$this->m_strTitle = '';
		echo 'Creating new page...';
		$this->DisplayEditForm();
	}
	
	protected function DisplayPage()
	{
		if($_SESSION['user_level']>=self::EDIT_RQ_LEVEL)
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
		}
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
