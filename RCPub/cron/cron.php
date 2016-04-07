<?php

assert_options( ASSERT_BAIL , 1 );

assert( !get_magic_quotes_gpc() );

chdir( '..' );

date_default_timezone_set( 'US/Central' );

if( !file_exists( 'config/config.php' ) )
{
	print( "RC Publisher is not installed, CRON cannot run.<br />\n" );
	return;
}

print( "Running cron job at ".date('l jS \of F Y h:i:s A')."...<br />\n" );

require_once('config/config.php');	 //Must be included first.
require( 'classes/rclibrary.php' );
require( 'classes/plugin_manager.php' );
require_once( 'classes/rcerror.php' );
require_once( 'classes/table_base.php' );
require_once( 'classes/table_mail.php' );
require_once( 'classes/table_settings.php' );
require_once('classes/rcsql.php');


RCSql_Connect();
RCSettings_Init();


print( "Forwarding emails (".date('l jS \of F Y h:i:s A').") ...<br />\n" );
$Mail = new CTableMail();
$UserTable = new CTableUser();

assert( NULL != $Mail && NULL != $UserTable );

$ExtMessages = $Mail->GetUnsentMessages();

if( NULL != $ExtMessages )
{
	foreach( $ExtMessages as $Msg )
	{
		$UserInfo = $UserTable->GetUserInfo( (int)$Msg['idUser_To'] );

		printf( 'Forwarding message from %s with subject "%s" to %s<br />' , $Msg['txtEmail'] , $Msg['txtSubject'] , $UserInfo['txtEmail'] );

		$msg = sprintf( "From: %s\nReply Email: %s\nSubject: %s\n\n%s" , $Msg['txtName'] , $Msg['txtEmail'] , $Msg['txtSubject'] , $Msg['txtMessage'] );	
		$Mail->PostMail_SendRealEmail( $UserInfo[ 'txtEmail' ] , $Msg['txtEmail'] , $Msg['txtName'] , 'RC Mail: '.$Msg['txtSubject'] , $Msg['txtMessage'] );

		// This was forwarded so make it as forwarded.
		$Mail->MarkAsMailed( (int)$Msg['idUser_To'] , (int)$Msg['id'] );
	}
}
else
{
	print( 'No messages to forward. <br />' );
}

RCSettings_Deinit();
RCSql_Disconnect();

?>