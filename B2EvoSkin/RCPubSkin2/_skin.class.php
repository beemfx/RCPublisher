<?php

/**
 * This file implements a class derived of the generic Skin class in order to provide custom code for
 * the skin in this folder.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @package skins
 * @subpackage custom
 *
 * @version $Id: _skin.class.php,v 1.3 2009/05/24 21:14:38 fplanque Exp $
 */
if (!defined('EVO_MAIN_INIT'))
    die('Please, do not access this page directly.');

/**
 * Specific code for this skin.
 *
 * ATTENTION: if you make a new skin you have to change the class name below accordingly
 */
class RCPubSkin2_Skin extends Skin {

    /**
     * Get default name for the skin.
     * Note: the admin can customize it.
     */
	function get_default_name()
	{
		return 'Rough Concept v 2.0';
	}

    /**
     * Get default type for the skin.
     */
   function get_default_type()
	{
		return 'normal';
   }
}

?>
