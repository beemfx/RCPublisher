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

	protected function PageStartup()
	{
		session_start();

		//Check if there is a login cookie, and if not already logged in
		//automatically login if the ip address is the same.
		if(isset($_COOKIE['rclogs']) && $_SESSION['user_level']==0)
		{
			$row=$this->DoSingleRowQuery('tblUser', $_COOKIE['rclogs']);
			if($row != null)
			{
				if($row['txtLastIP']==$_SERVER['REMOTE_ADDR'])
				{
					$_SESSION['user'] = $row['txtUserName'];
					$_SESSION['user_alias'] = $row['txtAlias'];
					$_SESSION['user_id'] = $row['id'];
					$_SESSION['user_level'] = $row['nAccessLevel'];
				}
			}
		}
		$this->DisplayPre();
	}
	
	//Public interface:
	public function Display()
	{
		$this->PageStartup();

		$this->StartHTML();
		print("<div id=\"wrapper\">\n");

		print("<div id=\"header\">\n");
		$this->DisplayHeader();
		print("</div>\n");
		
		//The navigation gets displayed last because when logging in and out
		//it affects the options on the navigation.
		$this->DisplayNavigation();
		print("<div id=\"content\">\n");
		if($_SESSION['user_level']>=$this->m_nUserLevel)
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
		$this->m_strTitle = $strTitle;
		$this->m_nUserLevel = $nUserLevel;
	}

	protected function GetGlobalSetting($strSettingName){
		global $g_rcPrefix;
		$strQuery = 'select txtSetting as s from '.$g_rcPrefix.'tblGlobalSettings where txtName="'.$strSettingName.'"';
		$res = $this->DoQuery($strQuery);

		if(true == $res)
		{
			//There should only be one row, and the setting is in that row.
			$row = $res->fetch_assoc();
			$out = $row['s'];
			$res->free();
		}
		else
		{
			$out = null;
		}

		return $out;
	}

	//When changing a global setting the newvalue must be formatted correctly
	//if the new value is just a number it can be passed in directly, but if
	//it is a string then the string must have quotes around the characters
	//for exampe ChangeGlobalSetting('strOwner', '"Jack"').
	protected function ChangeGlobalSetting($strSettingName, $strNewValue)
	{
		global $g_rcPrefix;
		$qry = 'update '.$g_rcPrefix.'tblGlobalSettings set txtSetting = "'.addslashes($strNewValue).'" where txtName="'.addslashes($strSettingName).'"';
		$this->DoQuery($qry);
	}

	protected function ShowWarning($str)
	{
		printf("<p style=\"color:red\">%s</p>\n", $str);
	}

	protected function GetNumMessages($nUserID)
	{
		/*
		$qry = 'select id from tblMessage where idUser_To='.$nUserID.' and bRead=false';
		$res = $this->DoQuery($qry);
		$nRows = $res==true?$res->num_rows:0;
		$res->free();
		*/
		return 0;//$nRows;
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
		return (int)$_SESSION['user_level'];
	}
	
	protected function DisplayUserOptions()
	{
		//We only do this if the user level is high enough.
		if($this->GetUserLevel() > 0)
		{
			echo '<div id="UO">';
			echo '<b>RC Publisher:</b> ';
			?>
			<b>[<?php print $_SESSION['user']?>]</b>
			<a href=<?php print CreateHREF(PAGE_EMAIL)?>>Inbox (<?php print $this->GetNumMessages($_SESSION['user_id'])?>)</a>
			<a href=<?php print CreateHREF(PAGE_POSTNEWS)?>>Post News</a>
			<a href=<?php print CreateHREF(PAGE_UPLOADFILE)?>>File Manager</a>
			<a href=<?php print CreateHREF(PAGE_SETTINGS)?>>Settings</a>
			<a href=<?php print CreateHREF(PAGE_LOGIN, 'logout')?>>Logout</a>
			<?php
			echo '</div>';
			
			//We also want to do some javascript to change the wrapper margin,
			//so that we can see the top of the page.
			?>
			<script language="javascript" type="text/javascript">
					document.getElementById('wrapper').style.marginTop = '40px';
			</script>
			<?php
		}
	}

	private function DisplayNavigation()
	{
		$Markup = new CRCMarkup($this->GetGlobalSetting('txtNav'));
		$sNav    = $Markup->GetHTML();
		$Markup = new CRCMarkup($this->GetGlobalSetting('txtMiniNav'));
		$sSubNav =  $Markup->GetHTML();
		
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

		<!-- Should only be displayed if an administrator is logged on. -->
		</div>
		<?php
	}

	//Private Methods:
	private function StartHTML()
	{
		echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
		"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
		//echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
		?>
		<html>
		<head>
		<link rel="stylesheet" type="text/css" href="rc_1.css"/>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<title><?php printf('Rough Concept by Jack Everett: %s', $this->m_strTitle);?></title>
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
		?>
		<img src="images/title.png" width="100%" alt="Rough Concept: The Offical Website of Jack Everett"/>
		<?php
	}

	private function DisplayFooter()
	{
		?>
		<br/>
		<p>
		Content  &copy; <?php print(date('Y'));?> Jack Everett. All rights
		reserved. Rough Concept is a trademark of Jack Everett in the US and other
		countries. All other trademarks are the property of their respective
		owners.
		</p>
		<p style="text-align:center;border-top:none;border-bottom:none;">
		<a href=<?php print CreateHREF(PAGE_PAGE, 'p=about')?>>About &amp; Copyright,
			Privacy Policy, and Terms of Service</a>.
		</p>
		<br/>
		<?php
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
