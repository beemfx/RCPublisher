<?php
/*******************************************************************************
 * File:   sample_page.php
 * Class:  CSampePage
 * Purpose: Page that gives a sample layout for developing other pages.
 *
 * Copyright (C) 2009 Blaine Myers
 ******************************************************************************/
require_once('page_base.php');

class CPageSettings extends CPageBase
{
	const RQ_USERLEVEL = 5;
	
	public function CPageSettings()
	{
		parent::CPageBase('Settings', self::RQ_USERLEVEL);
	}

	protected function DisplayContent()
	{
		echo '<h1>RC Publisher Settings</h1>';
		
		//If submit was pressed, we update the settings.
		if(isset($_POST['stage']) && $_POST['stage'] == 1)
		{
			$this->ChangeGlobalSetting('nHomeNewsStories', $_POST['news_stories']);
			$this->ChangeGlobalSetting('txtNav', $_POST['nav_bar']);
			$this->ChangeGlobalSetting('txtMiniNav', $_POST['mini_nav_bar']);
			$this->ChangeGlobalSetting('txtTwitterUser', $_POST['twitter_user']);
			$this->ChangeGlobalSetting('txtTwitterPwd', $_POST['twitter_pw']);
			$this->ChangeGlobalSetting('txtBlogURL' , $_POST['blog_url']);
			
			echo '<p style="background-color:#0c0">Saving settings...</p>';		
		}
		
		//No matter what we display the form.
		$this->DisplayForm();
	}
	
	private function DisplayForm()
	{
		?>
		<div style="width:100%;margin:0;padding:1em">
		<form action=<?php print CreateHREF(PAGE_SETTINGS)?> method="post">
		<input type="hidden" name="stage" value="1"/>
		<p><b>Home Page News Stories: </b><select name="news_stories" size="1"><?php $this->ShowNumberList($this->GetGlobalSetting('nHomeNewsStories'))?></select></p>
		<p><b>Navigation Bar:</b></br>
		<textarea style="height:5em;width:90%" name="nav_bar" cols="80" rows="20"><?php echo $this->GetGlobalSetting('txtNav') ?></textarea>
		</p>
		<p><b>Mini-Navigation Bar:</b></br>
		<textarea style="height:5em;width:90%" name="mini_nav_bar" cols="80" rows="20"><?php echo $this->GetGlobalSetting('txtMiniNav') ?></textarea>
		</p>
		<p><b>Twitter Name: </b><input type="text" name="twitter_user" value="<?php echo $this->GetGlobalSetting('txtTwitterUser')?>" style="width:50%"/></p>
		<p><b>Twitter Password: </b><input type="text" name="twitter_pw" value="<?php echo $this->GetGlobalSetting('txtTwitterPwd')?>" style="width:50%"/></p>
		<p><b>Blog URL: </b><input type="text" name="blog_url" value="<?php echo $this->GetGlobalSetting('txtBlogURL')?>" style="width:50%"/></p>
		<center><input class="button" type="submit" value="Submit"/></center>
		</form>
		</div>
		<?php
	}
	
	private function ShowNumberList($nSelected)
	{
		for($i=0; $i<=9; $i++)
		{
			printf("<option value=\"%d\" %s>%d</option>\n", $i, $nSelected==$i?'selected':'', $i);
		}
	}

}
?>
