<?php

/* Beem Format Plugin
 * (c) 2011 Beem Software by Blaine Myers (http://www.beemsoft.com)
 *
 * The Beem Format Plugin is a rendering plugin for b2evolution. The idea behind
 * it is to replace HTML markup in blog posts with an easier to use wiki type
 * markup language. It also creates better looking apostrophes and so forth.
 *
 * Instead of html tags we use a double bracket for special code for example
 * [[img srcfile|formatoptions|Description]]
 */
if (!defined('EVO_MAIN_INIT'))
    die('Please, do not access this page directly.');

//require('beemformatter.php');

class rcformat_plugin extends Plugin {

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
				'note'  => T_('The full path to the settings file for the RC Software (/?/html/rc/)'),
				'type'  => 'text',
				'defaultvalue' => '',
			),
		);
		return $r;
    }

    function RenderItemAsHtml(& $params)
	 {
		 $c = & $params['data'];
		
		 $rcPath = $this->Settings->get( 'rcroot' );
		 	 
		 //Make sure all this stuff is in the global scope.
		 global $g_rcPrefix;
		 global $g_rcDBHost;
		 global $g_rcDBUser;
		 global $g_rcDBPwd;
		 global $g_rcDBName;
		 global $g_rcFilepath;
		 
		 require($rcPath.'config/config.php');
		 require($rcPath.'classes/rcsql.php');
		 require($rcPath.'classes/RCMarkup.php');
		 require($rcPath.'classes/table_base.php');
		 require($rcPath.'classes/file_manager.php');
		 
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
