<?php
/*******************************************************************************
 * File:   pages.php
 * Purpose: Constants for each page.
 *
 * Copyright (C) 2009 Blaine Myers
 ******************************************************************************/


//$strIndex = 'index/';

define('PAGE_HOME',      'home');
define('PAGE_EMAIL',     'email');
define('PAGE_CONTACT',   'contact');
define('PAGE_LOGIN',     'login');
define('PAGE_POSTNEWS',  'postnews');
define('PAGE_UPLOADFILE','uploadfile');
define('PAGE_NEWS',      'news');
define('PAGE_PAGE',      'page');
define('PAGE_SETTINGS',  'settings');


//CreateHREF:
//Outputs a link to the console.
//$strContent should be one of the definitions in pages.php.
//$strVars is any additional variables that should be passed,
//or null if none should be passed. Variables should be separated
//by & as in with the html standard, but should not have an &
//prefixed eg: CreateHREF(PAGE_CONTENT, 'id=2&sort=5');
//
//This should be called whereever the href tag is to be located,
//as it creates teh href tag.

function CreateHREF_ALIAS($strContent, $strVars=null)
{
	
	if(PAGE_HOME == $strContent)
	{
		$strLink='index.html';
	}
	else if(PAGE_TOC == $strContent)
	{
		$find = array ('/&?page=(.*)/', '/&?sort=(.*)/');
		$replace = array ('_page$1', '_sort$1');
		$strLink = preg_replace($find, $replace, $strVars);
		$strLink = 'toc'.$strLink.'.html';
	}
	else if(PAGE_CONTENT == $strContent)
	{
		$strLink = 'item_'.preg_replace(array('/&?id=(.+)/'), array('$1'), $strVars).'.html';
	}
	else if(PAGE_ABOUT == $strContent)
	{
		$strLink = 'about'.preg_replace(array('/&?page=(tos|privacy)/'), array('_$1'), $strVars).'.html';
	}
	else if(PAGE_CONTACT == $strContent)
	{
		$strLink = 'contact.html';
	}
	else if(PAGE_LOGIN == $strContent)
	{
		if($strVars==null)
			$strLink = 'login.html';
		else
			$strLink = 'logout.html';
	}
	else if(PAGE_UPLOAD==$strContent)
	{
		$strLink = 'upload.html';
	}
	else if(PAGE_EDITC==$strContent)
	{
		$strLink = 'edit_'.preg_replace(array('/&?id=(.+)/'), array('$1'), $strVars).'.html';
	}
	else if(PAGE_PAGE==$strContent)
	{
		$strLink = 'page_'.$strVars.'.html';
	}
	else
	{
		$strLink = 'index.php?content='.$strContent;
		if(strlen($strVars)>0)
			$strLink.='&'.$strVars;
	}

	$strLink = sprintf('"%s"', $strLink);

	return $strLink;
}

function CreateHREF($strContent, $strVars=null)
{
	$strIndex = 'index.php?c='.$strContent;
	$strLink = '"'.$strIndex;
	if(strlen($strVars)>0)
		$strLink.='&'.$strVars;

	$strLink.='"';

	return $strLink;
}

?>