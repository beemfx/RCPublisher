<?php
/*******************************************************************************
 * File:   page_base.php
 * Class:  CPageBase
 * Purpose: Used to format the Rough Concept website, prints header, footer,
 * and associated menues. Should be used by all webpages within the Rough
 * Concept Publisher. All pages should inherit from this.
 *
 * Copyright (C) 2009 Blaine Myers
 ******************************************************************************/

abstract class CPageBase
{
	//Abstract interface:
	protected abstract function DisplayContent();

	public function GetTitle(){ return $this->GetGlobalSetting( 'txtWebsiteTitle' ).': '.$this->m_strTitle; }
	
	public function GetPreClosingHeadScript(){ return $this->GetGlobalSetting( 'txtScriptHeader' ); }
	
	public function GetHeader()
	{ 
		$Formatter = new CRCMarkup( $this->GetGlobalSetting('txtHeader'));
		return $Formatter->GetHTML(); 
	}
	
	public function GetNav1()
	{ 
		$Formatter  = new CRCMarkup($this->GetGlobalSetting('txtNav'));
		return $Formatter->GetHTML();
	}
	
	public function GetNav2()
	{
		$Formatter  = new CRCMarkup($this->GetGlobalSetting('txtMiniNav'));
		return $Formatter->GetHTML();
	}
	
	public function GetBody(){ return '<h2>Page Body!</h2>This is the content.'; }
	public function GetFooter()
	{
		$Formatter = new CRCMarkup( $this->GetGlobalSetting('txtFooter'));	
		return $Formatter->GetHTML();
	}
	
	public function Display_PageCallback()
	{
		print("<div id=\"content\">\n");
		if($this->GetUserLevel() >= $this->m_nUserLevel)
		{
			$this->DisplayContent();
		}
		else
		{
			print("<p>You are not authorized to view this page. ");
			print("Please <a href=".CreateHREF(PAGE_LOGIN).">log in</a> to view.</p>");
		}
		//Always display an extra line at the end of the content div, that way
		//there won't be an empty line at the end depending on what the last
		//tag was.
		print('<br />');
		
		print("</div>\n");
	}
	
	//Public interface:
	public function Display( $Skin )
	{
		if( false )
		{
			$this->DisplayPre();
			$Skin->BeginHTML( $this );
			$Skin->DrawPage( $this );
			$this->DisplayPost();
			$this->DisplayUserOptions();
			$Skin->EndHTML( $this );
			return;
		}
		

		$this->StartHTML();
		print("<div id=\"wrapper\">\n");

		print("<div id=\"header\">\n");
		$this->DisplayHeader();
		print("</div>\n");
		
		//The navigation gets displayed last because when logging in and out
		//it affects the options on the navigation.
		$this->DisplayNavigation();
		$this->Display_PageCallback();
		print("<div id=\"footer\">\n");
		$this->DisplayFooter();
		print("</div>\n");

		$this->DisplayPost();

		print("</div>\n");
		$this->DisplayUserOptions();
		$this->EndHTML();
	}

	//Protected attributes.
	protected $m_strTitle; //Title of the page.
	protected $m_nUserLevel; //This is the user level required to view the page.


	protected function CPageBase($strTitle, $nUserLevel=0)
	{
		assert('integer' == gettype($nUserLevel));
		
		$this->m_strTitle = $strTitle;
		$this->m_nUserLevel = $nUserLevel;
	}

	protected function GetGlobalSetting($strSettingName)
	{
		$Settings = new CTableSettings();	
		return $Settings->GetSetting($strSettingName);
	}

	//When changing a global setting the newvalue must be formatted correctly
	//if the new value is just a number it can be passed in directly, but if
	//it is a string then the string must have quotes around the characters
	//for exampe ChangeGlobalSetting('strOwner', '"Jack"').
	protected function ChangeGlobalSetting($strSettingName, $strNewValue)
	{
		$Settings = new CTableSettings();	
		return $Settings->SetSetting($strSettingName, $strNewValue);
	}

	public static function ShowWarning($str)
	{
		printf("<p style=\"color:red\">%s</p>\n", $str);
	}

	protected function GetNumMessages()
	{
		/*
		$qry = 'select id from tblMessage where idUser_To='.$nUserID.' and bRead=false';
		$res = $this->DoQuery($qry);
		$nRows = $res==true?$res->num_rows:0;
		$res->free();
		*/
		$Mail = new CTableMail();
		return $Mail->GetNumUnreadMessage((int)RCSession_GetUserProp('user_id'));
	}

	protected function DoQuery($qry)
	{
		$db = RCSql_GetDb();
		
		$res = $db->query($qry);
		if(!$res)
		{
			print($qry."<br/>\n");
			printf("MySQL Querry Error: %s.<br/>\n", $db->error);
		}
		return $res;
	}

	protected function DoSingleRowQuery($strTable, $nID)
	{
		$res = $this->DoQuery('select * from '.$strTable.' where id='.$nID);
		if(true==$res)
		{
			$row = $res->fetch_assoc();
			$res->free();
		}
		else
		{
			$row = null;
		}
		return $row;
	}

	protected function DoSingleRowQueryEx($strTable, $strField, $nID)
	{
		$res = $this->DoQuery('select * from '.$strTable.' where '.$strField.'='.$nID);
		if(true==$res)
		{
			$row = $res->fetch_assoc();
			$res->free();
		}
		else
		{
			$row = null;
		}
		return $row;
	}
		
	protected function GetUserLevel()
	{
		//We should probably do some kind of IP verification or something here.	
		return (int)  RCSession_GetUserProp('user_level');
	}
	
	protected function DisplayUserOptions()
	{
		//We only do this if the user level is high enough.
		if($this->GetUserLevel() > 0)
		{
			?>
			<style>div#UO
			{
				display:block;
				position:fixed;
				top:0;
				left: 0;
				width:100%;
				height:28px;
				border-bottom:2px solid #707070;
				background:#eee;
			}

			div#UO *
			{
				vertical-align:middle;
			}

			div#UO a
			{
				padding:0 .2em;
				border:2px solid #eee;
				color:#000;
				text-decoration: none;
			}

			div#UO a:hover
			{
				border:2px outset #707070;
			}
			</style>
			<div id="UO">
			<b>RC Publisher:</b>
			<b>[<?php print RCSession_GetUserProp('user')?>]</b>
			<a href=<?php print CreateHREF(PAGE_EMAIL)?>>Inbox (<?php print $this->GetNumMessages()?>)</a>
			<a href=<?php print CreateHREF(PAGE_POSTNEWS)?>>Post News</a>
			<a href=<?php print CreateHREF(PAGE_UPLOADFILE)?>>File Manager</a>
			<a href=<?php print CreateHREF(PAGE_PAGE,'p=')?>>New Page</a>
			<a href=<?php print CreateHREF(PAGE_SETTINGS)?>>Settings</a>
			<a href=<?php print CreateHREF(PAGE_USER)?>>User</a>
			<a href=<?php print CreateHREF(PAGE_LOGIN, 'logout')?>>Logout</a>
			</div>
			<script language="javascript" type="text/javascript">
					document.getElementById('wrapper').style.marginTop = '40px';
			</script>
			<?php
		}
	}

	private function DisplayNavigation()
	{
		$Markup  = new CRCMarkup($this->GetGlobalSetting('txtNav'));
		$sNav    = $Markup->GetHTML();
		$Markup  = new CRCMarkup($this->GetGlobalSetting('txtMiniNav'));
		$sSubNav = $Markup->GetHTML();
		
		?>
		<div id="menu_main">
			<!-- Load the background image, so that there isn't that delay when
			it loads -->
			<img src="images/menu_button_d.png"
				  alt="bg_down" title="bg_down"
				  style="display:none" />
		<p>
		<?php echo $sNav;?>
		</div>
		<div id="menu_sub">
		<?php echo $sSubNav;?>
		</div>
		<?php
	}

	//Private Methods:
	private function StartHTML()
	{
		echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
		//echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
		//echo '<!DOCTYPE HTML>';
		?>
		<html>
		<head>
		<link rel="stylesheet" type="text/css" href="rc_1.css"/>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<title><?php printf('%s: %s', $this->GetGlobalSetting('txtWebsiteTitle'), $this->m_strTitle);?></title>
                <?php printf( '%s' , $this->GetGlobalSetting( 'txtScriptHeader'));?>
		</head>
		<body>
		<?php
	}

	private function EndHTML()
	{
		?>
		</body>
		</html>
		<?php
	}

	private function DisplayHeader()
	{
		$Formatter = new CRCMarkup( $this->GetGlobalSetting('txtHeader'));
		echo $Formatter->GetHTML();
	}

	private function DisplayFooter()
	{
		$Formatter = new CRCMarkup( $this->GetGlobalSetting('txtFooter'));	
		echo '<br/><p>'.$Formatter->GetHTML().'</p><br/>';
	}

	//Display post will be called after all content is displayed, but before
	//the </body> and </html> tags.
	protected function DisplayPost()
	{

	}

	//DisplayPre is called after the session is verifyed but before any
	//content is actually displayed. It really shouldn't be used for
	//showing anything, but to process input, such as logging in and out
	//It doesn't need to be overriden.
	protected function DisplayPre()
	{

	}
}
?>
