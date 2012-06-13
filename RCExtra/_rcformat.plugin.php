<?php

/* RC Format Plugin
 * (c) 2012 Beem Software by Blaine Myers (http://www.beemsoft.com)
 *
 * The Format Plugin is a rendering plugin for b2evolution. The idea behind
 * it is to replace HTML markup in blog posts with RC Markup.
 *
 * Instead of html tags we use a double bracket for special code for example
 * [[img srcfile|formatoptions|Description]]
 */
if (!defined('EVO_MAIN_INIT'))die('Please, do not access this page directly.');

class rcformat_plugin extends Plugin
{
    var $code = 'rcformat';
    var $name = 'RC Formatter';
    var $priority = 10;
    var $apply_rendering = 'opt-out';
    var $group = 'rendering';
    var $help_url = 'http://www.beemsoft.com';
    var $short_desc;
    var $long_desc;
    var $version = '1.00';
    var $number_of_installs = 1;

    /**
     * Init
     */
    function PluginInit(& $params)
	 {
        $this->short_desc = T_('Format blogs using RC Markup.');
        $this->long_desc  = T_('Formats plain text using RC Format controls. The same used for RC Publisher pages and news stories. (See documentation.)');
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
		);
		return $r;
    }
	 
    function RenderItemAsHtml(& $params)
	 {
		//Hopefully this is only being called once per page, it should only
		 //be getting called when updating and saving the blog.
		 $c = & $params['data'];
		
		 $rcPath = $this->Settings->get( 'rcroot' );
		 	 
		 //Make sure all this stuff is in the global scope.
		 global $g_rcPrefix;
		 global $g_rcDBHost;
		 global $g_rcDBUser;
		 global $g_rcDBPwd;
		 global $g_rcDBName;
		 global $g_rcFilepath;
		 
		 require_once($rcPath.'config/config.php');
		 require_once($rcPath.'classes/rcsql.php');
		 require_once($rcPath.'classes/table_base.php');
		 require_once($rcPath.'classes/table_settings.php');
		 require_once($rcPath.'classes/RCMarkup.php');
		 require_once($rcPath.'classes/file_manager.php');
		 
		 RCSql_Connect();
		 
		 $Formatter = new CRCMarkup( $c );
		 
		 $c = $Formatter->GetHTML();
		 
		 RCSql_Disconnect();
		 
		 return true;
    }

    function DisplayItemAsXml(& $params)
	 {
        return $this->RenderItemAsHtml($params);
    }
}

?>
