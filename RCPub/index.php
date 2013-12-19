<?php

assert_options( ASSERT_BAIL , 1 );

assert( !get_magic_quotes_gpc() );

if( !file_exists( 'config/config.php' ) )
{
	if( file_exists( 'install/config.php' ) )
	{
		print( 'The installation utiltites has been run, please copy config.php from the install directory to the config directory.' );
	}
	else
	{
		print( 'RC Publisher is not installed. Please visit <a href="install/index.php">the install page</a>.' );
	}
	return;
}

require( 'classes/rclibrary.php' );
require_once( 'classes/rcerror.php' );
require_once( 'classes/table_base.php' );
require_once( 'classes/table_mail.php' );
require_once( 'classes/table_settings.php' );

require_once('config/config.php');	 //Must be included first.
require_once('classes/file_manager.php'); //Must be before RCMarkup
require_once('classes/RCMarkup.php');
require_once('classes/rcsql.php');
require_once('classes/pages.php');
require_once('classes/rcsession.php');



RCSql_Connect();
RCSession_Begin();

$strContent = isset( $_GET[ 'c' ] ) ? $_GET[ 'c' ] : (isset( $_GET[ 'content' ] ) ? $_GET[ 'content' ] : '');

switch( $strContent )
{
	case 'page':
		require('classes/page_page.php');
		$Page = new CPagePage();
		break;
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
	case 'settings':
		require('classes/page_settings.php');
		$Page = new CPageSettings();
		break;
	case 'user':
		require('classes/page_user.php');
		$Page = new CPageUser();
		break;
}

$SkinType = $Page->GetGlobalSetting( 'txtSkin' );
require( 'classes/skin_base.php' );
$SkinFile = 'skins/'.$SkinType.'/default.skin.php';
if( !file_exists( $SkinFile ) )
{
	$SkinFile = 'skins/default/default.skin.php';
}
require( $SkinFile );

$Skin = new RCSkin();
assert( $Skin instanceof ISkin );

$Page->Display( $Skin );

RCSql_Disconnect();
?>
