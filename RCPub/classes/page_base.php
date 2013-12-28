<?php
/* * *****************************************************************************
 * File:   page_base.php
 * Class:  CPageBase
 * Purpose: Used to format the Rough Concept website, prints header, footer,
 * and associated menues. Should be used by all webpages within the Rough
 * Concept Publisher. All pages should inherit from this.
 *
 * Copyright (C) 2009 Blaine Myers
 * **************************************************************************** */
abstract class CPageBase
{

	//Abstract interface:
	protected abstract function DisplayContent();
	
	protected abstract function GetContentHeader();

	protected abstract function IsPageAllowed();

	public function GetTitle()
	{
		return $this->GetGlobalSetting( 'txtWebsiteTitle' ).': '.$this->m_strTitle;
	}

	public function GetPreClosingHeadScript()
	{
		$Scripts = "<script type=\"text/javascript\" src=\"js/rclibrary.js\"></script>\n";
		return $Scripts.$this->GetGlobalSetting( 'txtScriptHeader' );
	}

	public function GetHeader()
	{
		return $this->GetGlobalSetting( 'txtHeaderHTML' );
	}

	public function GetNav1()
	{
		return $this->GetGlobalSetting( 'txtNavHTML' );
	}

	public function GetNav2()
	{
		return $this->GetGlobalSetting( 'txtMiniNavHTML' );
	}

	public function GetErrorText()
	{
		return '<div id="rc_errors"></div>';
	}

	public function GetBody()
	{
		return '<h2>Page Body!</h2>This is the content.';
	}

	public function GetFooter()
	{
		return $this->GetGlobalSetting( 'txtFooterHTML' );
	}

	public function Display_PageCallback()
	{
		$ContentHeader = $this->GetContentHeader();
		if( null != $ContentHeader )
		{
			print( "<div id=\"content_header\">\n" );
			print '<h1>'.$ContentHeader.'</h1>';
			print( "</div>\n" );
		}
		print("\n<div id=\"rc_content\">\n" );
		if( $this->IsPageAllowed() )
		{
			$this->DisplayContent();
		}
		else
		{
			print("<p>You do not have the necessary permissions to view this page.</p>" );
		}
		//Always display an extra line at the end of the content div, that way
		//there won't be an empty line at the end depending on what the last
		//tag was.
		print('<br />' );

		print("\n</div> <!-- div#rc_content -->\n" );
	}

	//Public interface:
	public function Display( $Skin )
	{
		$this->DisplayPre();
		$Skin->BeginHTML( $this );
		$Skin->DrawPage( $this );
		$this->DisplayPost();
		$this->DisplayUserOptions();
		$this->DisplayErrors();
		$Skin->EndHTML( $this );
	}

	//Protected attributes.
	protected $m_strTitle; //Title of the page.

	protected function CPageBase( $strTitle )
	{
		$this->m_strTitle = $strTitle;
	}

	public function GetGlobalSetting( $strSettingName )
	{
		return RCSettings_GetSetting( $strSettingName );
	}

	//When changing a global setting the newvalue must be formatted correctly
	//if the new value is just a number it can be passed in directly, but if
	//it is a string then the string must have quotes around the characters
	//for exampe ChangeGlobalSetting('strOwner', '"Jack"').
	protected function ChangeGlobalSetting( $strSettingName , $strNewValue )
	{
		return RCSettings_SetSetting( $strSettingName , $strNewValue );
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
		return $Mail->GetNumUnreadMessage( ( int ) RCSession_GetUserProp( 'user_id' ) );
	}

	protected function DoQuery( $qry )
	{
		$db = RCSql_GetDb();

		$res = $db->query( $qry );
		if( !$res )
		{
			print($qry."<br/>\n" );
			printf( "MySQL Querry Error: %s.<br/>\n" , $db->error );
		}
		return $res;
	}

	protected function DoSingleRowQuery( $strTable , $nID )
	{
		$res = $this->DoQuery( 'select * from '.$strTable.' where id='.$nID );
		if( true == $res )
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

	protected function DoSingleRowQueryEx( $strTable , $strField , $nID )
	{
		$res = $this->DoQuery( 'select * from '.$strTable.' where '.$strField.'='.$nID );
		if( true == $res )
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

	protected function DisplayErrors()
	{
		?>
		<script language="javascript" type="text/javascript">
			var Element = document.getElementById('rc_errors');
			Element.innerHTML = '<?php print RCError_GetErrorText(); ?>';
		</script>
		<?php
	}

	protected function DisplayUserOptions()
	{
		//We only do this if the user level is high enough.
		if( RCSession_IsUserLoggedIn() )
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
					font:normal normal normal 12pt/120% Times;
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
				<b>[<?php print RCSession_GetUserProp( 'user' ) ?>]</b>
				<a href=<?php print CreateHREF( PAGE_EMAIL ) ?>>Inbox (<?php print $this->GetNumMessages() ?>)</a>
				<a href=<?php print CreateHREF( PAGE_POSTNEWS ) ?>>Post News</a>
				<a href=<?php print CreateHREF( PAGE_UPLOADFILE ) ?>>File Manager</a>
				<a href=<?php print CreateHREF( PAGE_PAGE , 'p=' ) ?>>New Page</a>
				<a href=<?php print CreateHREF( PAGE_SETTINGS ) ?>>Settings</a>
				<a href=<?php print CreateHREF( PAGE_USER ) ?>>User</a>
				<a href=<?php print CreateHREF( PAGE_LOGIN , 'logout' ) ?>>Logout</a>
			</div>
			<script language="javascript" type="text/javascript">
				document.getElementById('rc_wrapper').style.marginTop = '40px';
			</script>
			<?php
		}
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
