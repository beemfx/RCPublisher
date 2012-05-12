<?php

assert_options(ASSERT_BAIL, 1);

assert(!get_magic_quotes_gpc());

require_once( 'classes/table_base.php' );
require_once( 'classes/table_mail.php' );

require_once('config/config.php');    //Must be included first.
require_once('classes/file_manager.php'); //Must be before RCMarkup
require_once('classes/RCMarkup.php');
require_once('classes/rcsql.php');
require_once('classes/pages.php');
require_once('classes/rcsession.php');



RCSql_Connect();
RCSession_Begin();

$strContent = isset($_GET['c'])?$_GET['c']:$_GET['content'];

switch($strContent)
{
case 'home':
default:
	require('classes/page_home.php');
	$Page = new CPageHome();
	break;
case 'login':
	require('classes/page_login.php');
	$Page = new CLoginPage();
	break;
case 'contact':
	require('classes/page_contact.php');
	$Page = new CContactPage();
	break;
case 'email':
	require('classes/page_email.php');
	$Page = new CEmailPage();
	break;
case 'postnews':
	require('classes/page_postnews.php');
	$Page = new CPostNewsPage();
	break;
case 'uploadfile':
	require('classes/page_uploadfile.php');
	$Page = new CPageUploadFile();
	break;
case 'news':
	require('classes/page_news.php');
	$Page = new CNewsPage();
	break;
case 'page':
	require('classes/page_page.php');
	$Page = new CPagePage();
	break;
case 'settings':
	require('classes/page_settings.php');
	$Page = new CPageSettings();
	break;
}

$Page->Display();

RCSql_Disconnect();

?>
