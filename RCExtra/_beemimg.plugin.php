<?php
/**
 * This file implements the Beem Image Plugin
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package plugins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


class beemimg_plugin extends Plugin
{
	var $code = 'beem_img1983';
	var $name = 'Beem Image';
	var $priority = 10;
	var $apply_rendering = 'opt-out';
	var $group = 'rendering';
	var $help_url = 'http://www.beemsoft.com';
	var $short_desc;
	var $long_desc;
	var $version = '1.00';
	var $number_of_installs = 1;

	private $m_strSearch;

	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Formats images for display in posts.');
		$this->long_desc = T_('<p>Usage: [img src=\'image url\' link=\'Useful link url\' format=\'(width) (float position)\' alt=\'alt text\' | Caption]]</p>');
	
	
		$this->m_strSearch = '/\\[\\[img[^\\[\\]]*\\]\\]/';
	}


	/**
	 * Get the settings that the plugin can use.
	 *
	 * Those settings are transfered into a Settings member object of the plugin
	 * and can be edited in the backoffice (Settings / Plugins).
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @see PluginSettings
	 * @see Plugin::PluginSettingsValidateSet()
	 * @return array
	 */
	function GetDefaultSettings( & $params )
	{
		$r = array(
			'beemimg_block' => array(
					'label' => 'Beem Image Block',
					'type' => 'html_textarea',
					'cols' => 60,
					'rows' => 10,
					'defaultvalue' => '<div class="image_block" style="width:$WIDTH$;float:$POSITION$"><a href="$LINK$"><img src="$SRC$" style="width:100%" alt="$ALT$"/></a><br />$CAPTION$</div>',
					'note' => 'This is how the image should be displayed Params: $WIDTH$, $POSITION$, $SRC$, $LINK$, $CAPTION$, $ALT$.',
				),
				'default_width' => array(
					'label' => 'Default Width',
					'type' => 'text',
					'cols' => 10,
					'rows' => 1,
					'defaultvalue' => T_('50%'),
					'note' => T_('The width of the image if none is specified (%, px).'),
				),
				'default_position' => array(
					'label' => 'Default Position',
					'type' => 'select',
					'options' => array('left' => 'left', 'right' => 'right', 'none' => 'none'),
					'defaultvalue' => T_('right'),
					'note' => T_('The default floating position of the image if none is specified.'),
				),
			);

		return $r;
	}


  /**
   * Comments out the adsense tags so that they don't get worked on by other renderers like Auto-P
   *
	 * @param mixed $params
	 */
	/*
	function FilterItemContents( & $params )
	{
		$content = & $params['content'];

		$content = preg_replace( $this->m_strSearch, '<!-- BEEM_IMG($0) -->', $content );

		return true;
	}
	*/

	/**
	 * Changes the commented out tags into something that is visible to the editor
	 *
	 * @param mixed $params
	 */
	/*
	function UnfilterItemContents( & $params )
	{
		$content = & $params['content'];

		$content = preg_replace( '/<!-- BEEM_IMG\(([^\(\)]*)\) -->/', '$1', $content );

		return true;
	}
	*/

	/**
	 * Event handler: Called when rendering item/post contents as HTML. (CACHED)
	 *
	 * The rendered content will be *cached* and the cached content will be reused on subsequent displays.
	 * Use {@link DisplayItemAsHtml()} instead if you want to do rendering at display time.
	 *
 	 * Note: You have to change $params['data'] (which gets passed by reference).
	 *
	 * @param array Associative array of parameters
	 *   - 'data': the data (by reference). You probably want to modify this.
	 *   - 'format': see {@link format_to_output()}. Only 'htmlbody' and 'entityencoded' will arrive here.
	 *   - 'Item': the {@link Item} object which gets rendered.
	 * @return boolean Have we changed something?
	 */
	function RenderItemAsHtml( & $params )
	{
		$content = & $params['data'];

		$content = preg_replace_callback($this->m_strSearch, array( $this, 'DisplayItem_callback' ), $content );

		return true;
	}


	/**
	 * Perform rendering (at display time, i-e: NOT cached)
	 *
	 * @todo does this actually get fed out in the xml feeds?
	 *
	 * @see Plugin::DisplayItemAsHtml()
	 */
	function DisplayItemAsHtml( & $params )
	{
		return false;

		/*
		$content = & $params['data'];

		$content = preg_replace_callback($this->m_strSearch, array( $this, 'DisplayItem_callback' ), $content );

		return true;
		*/
	}

	private function GetParam($strName, $strIn)
	{
		if(preg_match('/'.$strName.'\\s*=\\s*["\']([^"\']*)["\']/', $strIn, $match_out))
		{
			return $match_out[1];
		}
		else
		{
			return null;
		}
	}

	private function GetFormat($strFormat, & $strPosition, & $strWidth)
	{
		//Set the default values first, they'll be replaced if specified.
		$strPosition = $this->Settings->get('default_position');
		$strWidth = $this->Settings->get('default_width');

		//Essentially we just break apart the string and determine what the 
		//format parts are.

		
		$keys = preg_split('/[\\s,\\|]+/', $strFormat);
		
		$nKeys = count($keys);
		
		foreach($keys as $key)
		{
			if(preg_match('/[0-9]+(px)|(%)/', $key))
			{
				$strWidth = $key;
			}
			else if(preg_match('/(left)|(right)|(none)/', $key))
			{
				$strPosition = $key;
			}
			else if(preg_match('/[0-9]+/', $key))
			{
				//If we found just a number, assume it is the width in pixels.
				$strWidth = $key.'px';
			}

		}
	}

	function DisplayItem_callback( $matches )
	{
		//First extract the information:
		$strSrc = $this->GetParam('src', $matches[0]);
		$strLink = $this->GetParam('link', $matches[0]);
		$strFormat = $this->GetParam('format', $matches[0]);
		$strAlt = $this->GetParam('alt', $matches[0]);
		$strCaption = '';

		//If no link was specified, just link to the image.
		if(strlen($strLink) < 1)
		{
			$strLink = $strSrc;
		}

		$this->GetFormat($strFormat, $strPosition, $strWidth);
		
		//Get the caption:
		if(preg_match('/\\|\\s?([^\\]\\[]*)\\]\\]/', $matches[0], $match_out))
		{
			$strCaption = $match_out[1];
		}

		//echo 'Caption: '.$strCaption;

		$strBlock = $this->Settings->get('beemimg_block');

		$pattern  = array( '/\\$SRC\\$/', '/\\$LINK\\$/', '/\\$CAPTION\\$/', '/\\$WIDTH\\$/', '/\\$POSITION\\$/', '/\\$ALT\\$/');
		$replace = array(  $strSrc,       $strLink,       $strCaption,       $strWidth,       $strPosition,       $strAlt);
		
		return preg_replace($pattern, $replace, $strBlock);
	}

	/**
	 * Filter out adsense tags from XML content.
	 *
	 * @see Plugin::RenderItemAsXml()
	 *
	 * @todo: Just do a basic img tag.
	 */
	function DisplayItemAsXml( & $params )
	{
		$content = & $params['data'];

		$content = preg_replace( $this->m_strSearch, 'Image', $content );

		return true;
	}

	/**
	 * Display a toolbar in admin
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	/*
	function AdminDisplayToolbar( & $params )
	{
		if( $params['edit_layout'] == 'simple' )
		{	// This is too complex for simple mode, don't display it:
			return false;
		}

		echo '<div class="edit_toolbar">';
		echo '<input type="button" id="adsense_default" title="'.T_('Insert AdSense block').'" class="quicktags" onclick="textarea_wrap_selection( b2evoCanvas, \'[adsense:]\', \'\', 1 );" value="'.T_('AdSense').'" />';
		echo '</div>';

		return true;
	}
	*/
}



?>
