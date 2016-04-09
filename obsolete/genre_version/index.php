<?php

require('classes/pages.php');

$strContent = isset($_GET['c'])?$_GET['c']:$_GET['content'];

switch($strContent)
{
case 'home':
default:
	require('classes/page_home.php');
	$Page = new CPageHome();
	break;
case 'toc':
	require('classes/toc_page.php');
	$Page = new CTOCPage();
	break;
case 'content':
	require('classes/content_page.php');
	$Page = new CContentPage();
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
	require('classes/post_news_page.php');
	$Page = new CPostNewsPage();
	break;
case 'editg':
	require('classes/edit_genre_page.php');
	$Page = new CEditGenrePage();
	break;
case 'editc':
	require('classes/edit_content_page.php');
	$Page = new CEditContentPage();
	break;
case 'upload':
	require('classes/upload_page.php');
	$Page = new CUploadPage();
	break;
case 'uploadfile':
	require('classes/page_uploadfile.php');
	$Page = new CPageUploadFile();
	break;
case 'news':
	require('classes/news_page.php');
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

?>
