<?php

/**
 * This file implements the RC Publisher plugin. This plugin adds a widget that
 * displays the table of contents as defined in mini navigation and navigation
 * parameters.
 *
 * Copyright (c) 2011 Blaine Myers
 */
if (!defined('EVO_MAIN_INIT'))
	die('Please, do not access this page directly.');

/**
 * RC Publishder Widget Plugin
 *
 * This plugin displays
 */
class rcpub_plugin extends Plugin {

	/**
	 * Variables below MUST be overriden by plugin implementations,
	 * either in the subclass declaration or in the subclass constructor.
	 */
	var $name;
	var $code = 'beem_rcpub';
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
	function PluginInit(& $params) {
		$this->name = T_('RC Publisher Widget');
		$this->short_desc = T_('This skin tag displays menus from RC Publisher.');
		$this->long_desc = T_('Menus must be edited in the RC Publishder software.');

		//$this->dbtable = 'T_items__item';
		//$this->dbprefix = 'post_';
		//$this->dbIDname = 'post_ID';
	}

	/**
	 * Get definitions for widget specific editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_widget_param_definitions($params) {
		$r = array(
			 'navtype' => array(
				  'label' => T_('Nav Menu'),
				  'note' => T_('Which nav menu to be displayed.'),
				  'type' => 'select',
				  'options' => array('m' => 'Mini Menu', 'n' => 'Menu',),
				  'defaultvalue' => 'n',
			 ),
			 'sqlserv' => array(
				  'label' => T_('SQL Address'),
				  'note' => T_('The address of the SQL database for RC Publisher.'),
				  'type' => 'text',
				  'defaultvalue' => '',
			 ),
			  'sqluser' => array(
				  'label' => T_('SQL User'),
				  'note' => T_('User name.'),
				  'type' => 'text',
				  'defaultvalue' => '',
			 ),
			 'sqlpass' => array(
				  'label' => T_('SQL Password'),
				  'note' => T_('Password.'),
				  'type' => 'text',
				  'defaultvalue' => '',
			 ),
			 'sqldb' => array(
				  'label' => T_('SQL Database'),
				  'note' => T_('Databse (often same as user name).'),
				  'type' => 'text',
				  'defaultvalue' => '',
			 ),
			  'baseurl' => array(
				  'label' => T_('RC Publisher Base URL'),
				  'note' => T_('The base URL for the RC Publisher content.'),
				  'type' => 'text',
				  'defaultvalue' => 'http://',
			 ),
		);
		return $r;
	}

	function SkinTag($params) {
		/*
		  // Connect to the database:
		  @ $this->m_db = new mysqli(
		  'p50mysql281.secureserver.net',
		  'RCPublisher',
		  'Pub#435',
		  'RCPublisher');

		  if(mysqli_connect_errno())
		  {
		  unset($this->m_db);
		  print('A problem occured while connecting to the database. Try again later.');
		  return;
		  }
		 */
		$RCPubPlug = new CRCPubPlug(
				$params['navtype'],
				$params['sqlserv'], 
				$params['sqluser'],
				$params['sqlpass'],
				$params['sqldb'],
				$params['baseurl']);
		
		
		echo $params['block_start'];
		echo $RCPubPlug->GetText();
		echo $params['block_end'];
		return true;
	}

}

class CRCPubPlug
{
	private $m_db;
	private $m_strText;
	static private $m_strURL;
	
	public function CRCPubPlug($strMenu, $strServ, $strUser, $strPass, $strDB, $strBaseURL)
	{
		CRCPubPlug::$m_strURL = $strBaseURL;
		@$this->m_db = new mysqli($strServ, $strUser, $strPass, $strDB);

		  if(mysqli_connect_errno())
		  {
			  unset($this->m_db);
			  $this->m_strText = 'Database Error';
		  }
		  else
		  {
			  $this->m_strText = 'No problem!';
			  
			  $qry = sprintf('select %s as s from tblGlobalSettings where id=1', ($strMenu=='m')?'txtMiniNav':'txtNav');
			  $res = $this->m_db->query($qry);
			  
			  if(true == $res)
			  {
				  $row = $res->fetch_assoc();
				  $this->m_strText = $row['s'];
				  $res->free();
			  }
			  else
			  {
				  $this->m_strText = 'Invalid query.';
			  }
			  
			  $this->m_db->close(); //Close the database.
		  }
		  
		  $this->m_strText = $this->ProcessInternalLinks($this->m_strText);
		  
	}
	
	public function GetText()
	{
		return $this->m_strText;
	}
	
	static private function PIL_Replace($matches)
	{
		//The first thing to do is go through all the built in links, then try to do a page link.
		$strRef = '';
		switch($matches[1])
		{
			case 'home': $strRef = 'index.php?c=home';break;
			case 'blog': $strRef='"http://www.roughconcept.com/blog/index.php"';break;
			case 'toc': $strRef = 'index.php?c=toc';break;
			case 'contact': $strRef = 'index.php?c=contact';break;
			case 'login': $strRef = 'index.php?c=login';break;
			case 'news': $strRef = 'index.php?c=news&archive';break;
			default: $strRef = 'index.php?c=page&p='.$matches[1];break;
		}
		
		if($matches[1] !='blog')
		{
			$strRef = sprintf('"%s%s"', CRCPubPlug::$m_strURL, $strRef);
		}
			
		return sprintf('<a href=%s>%s</a>', $strRef, isset($matches[2])?$matches[2]:$matches[1]);
	}
	
	static protected function ProcessInternalLinks($strIn)
	{
		
		//ProcessInternalLinks is a regular expression replacement function that
		//attemps to find internal links, and replace them appropriately.
		//Internal links are in the form [[link link text]] where link text is optional.
		//Global links are attempted to be resolved first, then the link is assumed to be
		//a page link.
		
		return preg_replace_callback('/\[\[([A-Za-z0-9_]*)( [^\]\[]*)?\]\]/', "CRCPubPlug::PIL_Replace", $strIn);
	}
}

?>