<?php

/**
 * This file implements the RC Publisher plugin. This plugin adds a widget that
 * displays the table of contents as defined in mini navigation and navigation
 * parameters.
 *
 * Copyright (c) 2011 Blaine Myers
 */
if (!defined('EVO_MAIN_INIT'))die('Please, do not access this page directly.');

/**
 * RC Publishder Widget Plugin
 *
 * This plugin displays
 */
class rcwidget_plugin extends Plugin
{

	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */
	var $name;
	var $code = 'rcwidget';
	var $priority = 20;
	var $version = '1.0';
	var $author = 'Beem Software';
	var $group = 'widget';
	/**
	 * @var ItemQuery
	 */
	var $ItemQuery;

	/**
	 * Init
	 */
	function PluginInit(& $params)
	{
		$this->name = T_('RC Publisher Widget');
		$this->short_desc = T_('This skin tag displays menus from RC Publisher.');
		$this->long_desc = T_('Menus must be edited in the RC Publishder software.');
	}

	 function GetDefaultSettings(& $params)
	 {
		 $r = array
		 (
			'rcroot' => array
			(
				'label' => T_('RC Root Directory'),
				'note'  => T_('The full path to the settings file for the RC Software (eg /apache/html/rc/)'),
				'type'  => 'text',
				'defaultvalue' => '',
			),
			  
			'rcurl' => array
			(
				'label' => T_('RC Root URL'),
				'note'  => T_('The full path to the settings file for the RC Software (eg http://www.site.com/rc/)'),
				'type'  => 'text',
				'defaultvalue' => 'http://',
			),
		);
		return $r;
    }
	 
	/**
	 * Get definitions for widget specific editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_widget_param_definitions($params)
	{
		$r = array(
			 'navtype' => array(
				  'label' => T_('Nav Menu'),
				  'note' => T_('Which nav menu to be displayed.'),
				  'type' => 'select',
				  'options' => array('f' => 'Freestyle RC Markup', 'm' => 'Mini Menu', 'n' => 'Menu', 'ft' => 'Footer', 'hd' => 'Header' ),
				  'defaultvalue' => 'f',
			 ),
			 
			 'freetext' => array(
				  'label' => T_('RC Markup Text'),
				  'note' => T_('Free RC Markup.'),
				  'type' => 'textarea',
				  'defaultvalue' => '',
			 ),
		);
		return $r;
	}

	function SkinTag($params)
	{
		$rcPath = $this->Settings->get( 'rcroot' );
		 	 
		 //Make sure all this stuff is in the global scope.
		 global $g_rcPrefix;
		 global $g_rcDBHost;
		 global $g_rcDBUser;
		 global $g_rcDBPwd;
		 global $g_rcDBName;
		 global $g_rcFilepath;
		 
		 require_once($rcPath.'classes/pages.php');
		 require_once($rcPath.'config/config.php');
		 require_once($rcPath.'classes/rcsql.php');
		 require_once($rcPath.'classes/RCMarkup.php');
		 require_once($rcPath.'classes/table_base.php');
		 require_once($rcPath.'classes/file_manager.php');
		 require_once($rcPath.'classes/table_settings.php');
		 require_once($rcPath.'classes/rclibrary.php' );
		 
		 global $g_rcBaseUrl;
		 $g_rcBaseUrl = $this->Settings->get( 'rcurl' );
		 
		 RCSql_Connect();
		 RCSettings_Init();		 
		
		 switch( $params['navtype'] )
		 {
			 case 'n': 
				 $c = RCSettings_GetSetting( 'txtNavHTML' ); 
				 break;
			 case 'm': 
				 $c = RCSettings_GetSetting( 'txtMiniNavHTML' );
				 break;
			 case 'f': 
				 break;
			 case 'ft': 
				 $c = RCSettings_GetSetting( 'txtFooterHTML' );
				 break;
			 case 'hd': 
				 $c = RCSettings_GetSetting( 'txtHeaderHTML' );
				 break;
		 }
		 
		 if( 'f' == $params['navtype'])
		 {
			 $Formatter = new CRCMarkup( $params['freetext'] );
			 $c = $Formatter->GetHTML();
		 }
		 
		 RCSettings_Deinit();
		 RCSql_Disconnect();
		 
		echo $c;
		return true;
	}
	
	function SkinBeginHtmlHead()
	{
		$rcPath = $this->Settings->get( 'rcroot' );
			 
		//Make sure all this stuff is in the global scope.
		global $g_rcPrefix;
		global $g_rcDBHost;
		global $g_rcDBUser;
		global $g_rcDBPwd;
		global $g_rcDBName;
		global $g_rcFilepath;
		require_once($rcPath.'classes/pages.php');
		require_once($rcPath.'config/config.php');
		require_once($rcPath.'classes/rcsql.php');
		require_once($rcPath.'classes/RCMarkup.php');
		require_once($rcPath.'classes/table_base.php');
		require_once($rcPath.'classes/file_manager.php');
		require_once($rcPath.'classes/table_settings.php');
		 
		RCSql_Connect(); 
		$Settings = new CTableSettings();
		$c = $Settings->GetSetting( 'txtScriptHeader' );
		echo $c;
		RCSql_Disconnect();
	}
}

?>