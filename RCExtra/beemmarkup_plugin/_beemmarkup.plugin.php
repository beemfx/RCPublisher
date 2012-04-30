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

require('beemformatter.php');

class beemmarkup_plugin extends Plugin {

    var $code = 'beem_markup';
    var $name = 'Beem Markup Language';
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
    function PluginInit(& $params) {
        $this->short_desc = T_('Implements special markup for the text.');
        $this->long_desc = T_('Reformats things such as apostrophes, quotes, etc, paragraph tags, newlines. Probably shouldn\'t be used with other reformatting software such as Auto P.');
    }

    function GetDefaultSettings(& $params) {
        $r = array(
            'beemmarkup_img_block' => array(
                'label' => 'Image Block',
                'type' => 'html_textarea',
                'cols' => 60,
                'rows' => 10,
                'defaultvalue' => '<div style = "color:white;padding:.25em;margin:.25em;border:0;background-color:black;__STYLE__">
                    <a href="__LINK__">
                    <img stye="border:0;margin:0;padding:0;" width="100%" src = "__SRC__" alt = "__ALT__"/>
                    </a>
                    <br />__DESC__</div>'),

           'beemmarkup_img_path' => array(
                'label' => 'Default Image Path (used when a full path name is not specified)',
                'type' => 'html_textarea',
                'cols' => 60,
                'rows' => 10,
                'defaultvalue' => 'http://www.???.com/blogs/media/blogs/blogname/'),
        );

        return $r;
    }

    function RenderItemAsHtml(& $params) {
        $c = & $params['data'];
        $f = new CBeemFormatter(& $c, 
                $this->Settings->get('beemmarkup_img_path'),
                $this->Settings->get('beemmarkup_img_block'));
        $f->FormatIt();
        $c = $f->GetText();
        return true;
    }

    function DisplayItemAsXml(& $params) {
        return $this->RenderItemAsHtml($params);
    }

}

?>
