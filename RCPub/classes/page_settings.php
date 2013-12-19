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
	'txtHeader' => array( 'desc' => 'Page Header' , 'type' => 'textarea' , ) ,
	'txtFooter' => array( 'desc' => 'Page Footer' , 'type' => 'textarea' , ) ,
	'nHomeNewsStories' => array( 'desc' => 'Homage Page News Stores' , 'type' => 'selectnumber' , 'num_min' => 0 , 'num_max' => 12 ) ,
	'txtNav' => array( 'desc' => 'Navigation Bar' , 'type' => 'textarea' , ) ,
	'txtMiniNav' => array( 'desc' => 'Mini-Navigation Bar' , 'type' => 'textarea' , ) ,
	'txtSidebarHTML' => array( 'desc' => 'Home Page Sidebar' , 'type' => 'textarea' , ) ,
	'txtFeatureSlug' => array( 'desc' => 'Featured Page (slug)' , 'type' => 'text' , ) ,
	'txtBlogLink' => array( 'desc' => 'Blog Link (use {{slug}} for the slug identifier)' , 'type' => 'text' , ) ,
	//b2evo Plugin Settings (should actually be a plugin in the future).
	'txtB2Host' => array( 'desc' => 'b2evolution Host' , 'type' => 'text' , ) ,
	'txtB2User' => array( 'desc' => 'b2evolution User' , 'type' => 'text' , ) ,
	'txtB2Pwd' => array( 'desc' => 'b2evolution Password' , 'type' => 'text' , ) ,
	'txtB2Db' => array( 'desc' => 'b2evolution Database' , 'type' => 'text' , ) ,
	//ImageMagick settings.
	'txtConvertPath' => array( 'desc' => 'Path to ImageMagick convert' , 'type' => 'text' , ) ,
	'nThumbnailWidth' => array( 'desc' => 'Thumbnail width' , 'type' => 'text' , ) ,
	'nThumbnailQuality' => array( 'desc' => 'Thumbnail quality (0-100)' , 'type' => 'selectnumber' , 'num_min' => 0 , 'num_max' => 100 ) ,
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

	protected function DisplayPre()
	{
		if( RCWeb_GetPost( 'stage' ) == 'us' )
		{
			global $g_Settings;
			foreach( $g_Settings as $Setting => $Atts )
			{
				$this->ChangeGlobalSetting( $Setting , RCWeb_GetPost( $Setting , '' ) );
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

	protected function DisplayContent()
	{
		echo '<h1>RC Publisher Settings</h1>';

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

	private function DisplayForm()
	{
		global $g_Settings;
		?>
		<div style="width:100%;margin:0;padding:1em">
			<p><center><a href=<?php print CreateHREF( PAGE_SETTINGS , 'action=recache' ) ?>>Reset Cache</a> | <a href=<?php print CreateHREF( PAGE_SETTINGS , 'action=reimagethumb' ) ?>>Generate Thumbnails</a> | <a href=<?php print CreateHREF( PAGE_SETTINGS , 'action=purgethumb' ) ?>>Delete Thumbnails</a></center></p>
		<form action=<?php print CreateHREF( PAGE_SETTINGS ) ?> method="post">
			<input type="hidden" name="stage" value="us"/>
			<?php
			foreach( $g_Settings as $Setting => $Atts )
			{
				switch( $Atts[ 'type' ] )
				{
					case 'text':
						printf( '<p><b>%s</b>: <input type="text" name="%s" value="%s" style="width:50%%"/></p>' , $Atts[ 'desc' ] , $Setting , htmlspecialchars( $this->GetGlobalSetting( $Setting ) , ENT_QUOTES ) );
						break;
					case 'textarea':
						printf( '<p><b>%s</b></br><textarea style="height:5em;width:90%%" name="%s" cols="80" rows="20">%s</textarea></p>' , $Atts[ 'desc' ] , $Setting , $this->GetGlobalSetting( $Setting ) );
						break;
					case 'selectnumber':
						printf( '<p><b>%s: </b><select name="%s" size="1">%s</select></p>' , $Atts[ 'desc' ] , $Setting , $this->CreateNumberList( $Atts[ 'num_min' ] , $Atts[ 'num_max' ] , ( int ) $this->GetGlobalSetting( $Setting ) ) );
						break;
					case 'skinchooser':
						printf( '<p><b>%s: </b><select name="%s" size="1">%s</select></p>' , $Atts[ 'desc' ] , $Setting , $this->CreateSkinChooser( $this->GetGlobalSetting( $Setting ) ) );
						break;
				}
			}
			?>
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
