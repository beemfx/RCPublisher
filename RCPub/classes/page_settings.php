<?php
/* * *****************************************************************************
 * File:   sample_page.php
 * Class:  CSampePage
 * Purpose: Page that gives a sample layout for developing other pages.
 *
 * Copyright (C) 2009 Blaine Myers
 * **************************************************************************** */
require_once('page_base.php');

$g_Settings = array
	(
	'txtWebsiteTitle' => array( 'desc' => 'Website Title' , 'type' => 'text' , ) ,
	'txtSkin' => array( 'desc' => 'Skin' , 'type' => 'skinchooser' , ) ,
	'txtScriptHeader' => array( 'desc' => 'Pre &lt;/head&gt; Scripting' , 'type' => 'textarea' , ) ,
	'txtHeader' => array( 'desc' => 'Page Header' , 'type' => 'textarea_w_rcformat' , ) ,
	'txtFooter' => array( 'desc' => 'Page Footer' , 'type' => 'textarea_w_rcformat' , ) ,
	'nHomeNewsStories' => array( 'desc' => 'Homage Page News Stores' , 'type' => 'selectnumber' , 'num_min' => 0 , 'num_max' => 12 ) ,
	'txtNav' => array( 'desc' => 'Navigation Bar' , 'type' => 'textarea_w_rcformat' , ) ,
	'txtMiniNav' => array( 'desc' => 'Mini-Navigation Bar' , 'type' => 'textarea_w_rcformat' , ) ,
	'txtSidebarHTML' => array( 'desc' => 'Home Page Sidebar' , 'type' => 'textarea' , ) ,
	'txtFeatureSlug' => array( 'desc' => 'Featured Page (slug)' , 'type' => 'text' , ) ,
	'txtBlogLink' => array( 'desc' => 'Blog Link (use {{slug}} for the slug identifier)' , 'type' => 'text' , ) ,
	//ImageMagick settings.
	'txtConvertPath' => array( 'desc' => 'Path to ImageMagick convert' , 'type' => 'text' , ) ,
	'nThumbnailWidth' => array( 'desc' => 'Thumbnail width' , 'type' => 'text' , ) ,
	'nThumbnailQuality' => array( 'desc' => 'Thumbnail quality (0-100)' , 'type' => 'selectnumber' , 'num_min' => 0 , 'num_max' => 100 ) ,
	'bAllowComments' => array( 'desc' => 'Allow comments on pages' , 'type' => 'checkbox' ) ,
);
class CPageSettings extends CPageBase
{

	const RQ_USERLEVEL = 5;

	public function CPageSettings()
	{
		parent::CPageBase( 'Settings' );
	}

	protected function IsPageAllowed()
	{
		return RCSession_IsPermissionAllowed( RCSESSION_EDITSETTINGS );
	}

	private function DisplayPre_HandleChangedSetting( $Setting , $Atts )
	{
		//RCError_PushError($Setting.' is '.RCWeb_GetPost( $Setting , '' ) );
		
		if( !$this->IsPageAllowed() )
			return;
		
		if( 'checkbox' == $Atts[ 'type' ] )
		{
			$this->ChangeGlobalSetting( $Setting , RCWeb_GetPost( $Setting , '0' ) );
		}
		else
		{
			$this->ChangeGlobalSetting( $Setting , RCWeb_GetPost( $Setting , '' ) );
			if( 'textarea_w_rcformat' == $Atts['type'] )
			{
				$Formatter = new CRCMarkup( RCWeb_GetPost( $Setting , '' ) );
				$this->ChangeGlobalSetting( $Setting.'HTML' , $Formatter->GetHTML() );
			}
		}
	}

	protected function DisplayPre()
	{
		if( RCWeb_GetPost( 'stage' ) == 'us' )
		{
			global $g_Settings;
			foreach( $g_Settings as $Setting => $Atts )
			{
				$this->DisplayPre_HandleChangedSetting( $Setting , $Atts );
			}

			$Pm = PluginManager_GetInstance();

			for( $i = 0; $i < $Pm->GetPluginCount(); $i++ )
			{
				$Plugin = $Pm->GetPluginByIndex( $i );
				$PlgSettings = $Plugin->GetSettings();
				if( null == $PlgSettings )
				{
					continue;
				}

				foreach( $PlgSettings as $Setting => $Atts )
				{
					$this->DisplayPre_HandleChangedSetting( $Setting , $Atts );
				}
			}
		}

		if( isset( $_GET[ 'action' ] ) && 'recache' == $_GET[ 'action' ] )
		{
			require_once('table_page.php');
			require_once('table_news.php');
			//Reset the cash.
			$Table = new CTablePage();
			$Table->ResetCache();
			$Table = new CTableNews();
			$Table->ResetCache();
		}

		if( isset( $_GET[ 'action' ] ) && 'reimagethumb' == $_GET[ 'action' ] )
		{
			require_once('file_manager.php');
			$Manager = new CFileManager();
			$Manager->ReCreateAllThumbs();
		}

		if( isset( $_GET[ 'action' ] ) && 'purgethumb' == $_GET[ 'action' ] )
		{
			require_once('file_manager.php');
			$Manager = new CFileManager();
			$Manager->DeleteAllThumbs();
		}
	}

	protected function GetContentHeader()
	{
		return 'Settings';
	}

	protected function DisplayContent()
	{
		//If submit was pressed, we update the settings.
		if( RCWeb_GetPost( 'stage' ) == 'us' )
		{
			RCError_PushError( 'Settings saved.' , 'message' );
		}

		if( isset( $_GET[ 'action' ] ) && 'recache' == $_GET[ 'action' ] )
		{
			RCError_PushError( 'Cache reset.' , 'message' );
		}

		if( isset( $_GET[ 'action' ] ) && 'reimagethumb' == $_GET[ 'action' ] )
		{
			RCError_PushError( 'Thumbnails generated.' , 'mesage' );
		}

		if( isset( $_GET[ 'action' ] ) && 'purgethumb' == $_GET[ 'action' ] )
		{
			RCError_PushError( 'Thumbnails purged.' , 'message' );
		}

		//No matter what we display the form.
		$this->DisplayForm();
	}

	private function DisplayForm_ShowSetting( $Setting , $Atts )
	{
		switch( $Atts[ 'type' ] )
		{
			case 'text':
				printf( '<p><b>%s</b>: <input type="text" name="%s" value="%s" style="width:50%%"/></p>' , $Atts[ 'desc' ] , $Setting , htmlspecialchars( $this->GetGlobalSetting( $Setting ) , ENT_QUOTES ) );
				break;
			case 'textarea_w_rcformat':
			case 'textarea':
				printf( '<p><b>%s</b></br><textarea style="height:5em;width:90%%" name="%s" cols="80" rows="20">%s</textarea></p>' , $Atts[ 'desc' ] , $Setting , $this->GetGlobalSetting( $Setting ) );
				break;
			case 'selectnumber':
				printf( '<p><b>%s: </b><select name="%s" size="1">%s</select></p>' , $Atts[ 'desc' ] , $Setting , $this->CreateNumberList( $Atts[ 'num_min' ] , $Atts[ 'num_max' ] , ( int ) $this->GetGlobalSetting( $Setting ) ) );
				break;
			case 'checkbox':
				printf( '<p><b>%s</b>: <input type="checkbox" name="%s" value="1" style="width:50%%" %s/></p>' , $Atts[ 'desc' ] , $Setting , 0 == $this->GetGlobalSetting( $Setting ) ? '' : 'checked'  );
				break;
			case 'skinchooser':
				printf( '<p><b>%s: </b><select name="%s" size="1">%s</select></p>' , $Atts[ 'desc' ] , $Setting , $this->CreateSkinChooser( $this->GetGlobalSetting( $Setting ) ) );
				break;
		}
	}

	private function DisplayForm()
	{
		global $g_Settings;
		?>

		<div style="margin:1em;padding:1em">
			<h2>Maintenance</h2>
			<p><center><a href=<?php print CreateHREF( PAGE_SETTINGS , 'action=recache' ) ?>>Reset Cache</a> | <a href=<?php print CreateHREF( PAGE_SETTINGS , 'action=reimagethumb' ) ?>>Generate Thumbnails</a> | <a href=<?php print CreateHREF( PAGE_SETTINGS , 'action=purgethumb' ) ?>>Delete Thumbnails</a></center></p>
		<form action=<?php print CreateHREF( PAGE_SETTINGS ) ?> method="post">
			<input type="hidden" name="stage" value="us"/>
			<?php
			print '<h2>RC Publisher Settings</h2><br/>';
			foreach( $g_Settings as $Setting => $Atts )
			{
				$this->DisplayForm_ShowSetting( $Setting , $Atts );
			}

			$Pm = PluginManager_GetInstance();

			for( $i = 0; $i < $Pm->GetPluginCount(); $i++ )
			{
				$Plugin = $Pm->GetPluginByIndex( $i );
				printf( '<h2>Settings for %s</h2><br />' , $Plugin->GetName() );
				$PlgSettings = $Plugin->GetSettings();
				if( null == $PlgSettings )
				{
					print( 'No settings for this plugin.' );
					continue;
				}
				foreach( $PlgSettings as $Setting => $Atts )
				{
					$this->DisplayForm_ShowSetting( $Setting , $Atts );
				}
			}
			?>
			<br />
			<center><input class="button" type="submit" value="Submit"/></center>
		</form>
		</div>
		<?php
	}

	private function CreateNumberList( $nMin , $nMax , $nSelected )
	{
		assert( 'integer' == gettype( $nMin ) && 'integer' == gettype( $nMax ) && 'integer' == gettype( $nSelected ) );
		assert( $nMin < $nMax );

		$sSelect = '';
		for( $i = $nMin; $i <= $nMax; $i++ )
		{
			$sSelect .= sprintf( "<option value=\"%d\" %s>%d</option>\n" , $i , $nSelected == $i ? 'selected' : '' , $i );
		}

		return $sSelect;
	}

	private function CreateSkinChooser( $Selected )
	{
		$Skins = scandir( 'skins/' );

		$Select = '';

		foreach( $Skins as $SkinFile )
		{
			if( '.' == $SkinFile || '..' == $SkinFile )
				continue;
			if( is_dir( 'skins/'.$SkinFile ) )
			{
				$Select .= sprintf( "<option value=\"%s\" %s>%s</option>\n" , $SkinFile , $Selected == $SkinFile ? 'selected' : '' , $SkinFile );
			}
		}

		return $Select;
	}

}
?>
