<?php

assert_options(ASSERT_BAIL, 1);

assert(!get_magic_quotes_gpc());

require_once( 'classes/rcsql.php');
RCSql_Connect();

require('classes/pages.php');

$strContent = isset($_GET['c'])?$_GET['c']:$_GET['content'];

switch($strContent)
{
case 'home':
default:
	require('classes/page_home.php');
	$Page = new CPageHome();
	break;
case 'login':
	require('classes/login_page.php');
	$Page = new CLoginPage();
	break;
case 'clear':
	require('classes/clear_page.php');
	$Page = new CClearPage();
	break;
case 'contact':
	require('classes/contact_page.php');
	$Page = new CContactPage();
	break;
case 'email':
	require('classes/email_page.php');
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
