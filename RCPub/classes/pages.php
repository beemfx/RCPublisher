<?php

/* * *****************************************************************************
 * File:   pages.php
 * Purpose: Constants for each page.
 *
 * Copyright (C) 2009 Blaine Myers
 * **************************************************************************** */

define( 'PAGE_HOME' , 'home' );
define( 'PAGE_EMAIL' , 'email' );
define( 'PAGE_CONTACT' , 'contact' );
define( 'PAGE_LOGIN' , 'login' );
define( 'PAGE_POSTNEWS' , 'postnews' );
define( 'PAGE_UPLOADFILE' , 'uploadfile' );
define( 'PAGE_NEWS' , 'news' );
define( 'PAGE_PAGE' , 'page' );
define( 'PAGE_SETTINGS' , 'settings' );
define( 'PAGE_USER' , 'user' );

$g_rcBaseUrl = dirname($_SERVER['REQUEST_URI']).'/';

//CreateHREF:
//Creates a link for the page.
//$strContent should be one of the definitions in pages.php.
//$strVars is any additional variables that should be passed,
//or null if none should be passed. Variables should be separated
//by & as in with the html standard, but should not have an &
//prefixed eg: CreateHREF(PAGE_CONTENT, 'id=2&sort=5');
//
//This should be called whereever the href tag is to be located,
//as it creates the href tag.

function CreateHREF( $strContent , $strVars = null , $bNoQuotes = false )
{
	global $g_rcBaseUrl;
	
	$strIndex = $g_rcBaseUrl.'index.php?c='.$strContent;

	$strLink = $strIndex;

	if( strlen( $strVars ) > 0 )
		$strLink.='&'.$strVars;

	if( !$bNoQuotes )
		$strLink = '"'.$strLink.'"';

	return $strLink;
}

?>