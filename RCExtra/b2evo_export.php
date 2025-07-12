<?php

// I wrote this to export everything from b2evo and export it to something that would
// work with WordPress. This would need to be placed in the root directory of the
// installation to function.

assert_options( ASSERT_BAIL , 1 );
ini_set('display_errors', 'On');
error_reporting(E_ALL);

assert( !get_magic_quotes_gpc() );

if( !file_exists( 'config/config.php' ) )
{
	return;
}

require( 'classes/rclibrary.php' );
require( 'classes/plugin_manager.php' );
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
RCSettings_Init();
RCSession_Begin();
PluginManager_Init();
PluginManager_GetInstance()->RegisterPlugin('b2evoplug');

function RcExport_ExportPost( $fp , $PostId )
{
	printf( 'Exporting post %d...<br/>' , $PostId );
	
	@ $db = RCSql_GetDb();

	if( mysqli_connect_errno() )
	{
		unset( $db );
		print 'A problem occurred while connecting to the B2Evo Blog';
		return;
	}


	//Just want to get the most recent entry:
	$DateFormat = 'date_format(post_datestart,"%m/%d/%Y %H:%i:%s") as dt';
	$CommentDateFormat = 'date_format(comment_date,"%m/%d/%Y %H:%i:%s") as dt';
	$qry = sprintf('select post_title, post_urltitle, post_status, post_content, %s from evo_items__item where post_ID=%d', $DateFormat , $PostId );
	// $qry = printf("select post_title, post_ID, date_format(post_datestart, \"%%m/%%d/%%Y %%H:%%i:%%s\") as dt from evo_items__item where post_status=\"published\" order by post_datestart desc limit 1");

	$res = $db->query( $qry );

	if( $res == true )
	{
		$row = $res->fetch_assoc();
		
		printf( 'Exporting %s<br/>' , $row['post_title'] );
		fwrite( $fp , "AUTHOR: Author NameHere\n" );
		fwrite( $fp , "TITLE: ".$row['post_title']."\n" );
		fwrite( $fp , "DATE: ".$row['dt']."\n" );
		fwrite( $fp , "BASENAME: ".$row['post_urltitle']."\n" );
		fwrite( $fp , "STATUS: ".$row['post_status']."\n" );
		fwrite( $fp , "BODY:\n" );
		fwrite( $fp , $row['post_content'] );
		fwrite( $fp , "\n" );
		fwrite( $fp , "-----\n" );
		
		echo 'Fetching comments... <br/>';
		$CommentQry = "select *, ".$CommentDateFormat." from evo_comments where comment_item_ID=".$PostId;
		$CommentRes = $db->query( $CommentQry );
		if( $CommentRes == true )
		{
			for( $CommentIdx = 0; $CommentIdx < $CommentRes->num_rows; $CommentIdx++ )
			{
				$CommentRow = $CommentRes->fetch_assoc();
				fwrite( $fp , "COMMENT:\n" );
				fwrite( $fp , "AUTHOR: ".$CommentRow['comment_author']."\n" );
				fwrite( $fp , "EMAIL: ".$CommentRow['comment_author_email']."\n" );
				fwrite( $fp , "URL: ".$CommentRow['comment_author_url']."\n" );
				fwrite( $fp , "IP: ".$CommentRow['comment_author_IP']."\n" );
				fwrite( $fp , "DATE: ".$CommentRow['dt']."\n" );
				fwrite( $fp , $CommentRow['comment_content'] );
				fwrite( $fp , "\n" );
				fwrite( $fp , "-----\n" );
			}
			$CommentRes->free();
		}
		else
		{
			print 'Could not get comments entry.<br/>';
			print $db->error.'<br/>';
		}
		
		fwrite( $fp , "--------\n" );
		
		$res->free();
	}
	else
	{
		print 'Could not get blog entry.<br/>';
		print $db->error.'<br/>';
	}

	printf( 'Complete.<br/>' );

	return;
}

function RcExport_Process()
{
	echo 'Exporting data...<br/>';
	
	$fp = fopen( 'temp/exported.txt' , 'w' ) or die('Failed to open file.');
	
	echo 'File opened.<br/>';
	
	@ $db = RCSql_GetDb();
	
	$AllIds = $db->query( 'select post_ID from evo_items__item order by post_ID' );
	
	if( $AllIds == true )
	{
		for( $IdIdx=0; $IdIdx<$AllIds->num_rows; $IdIdx++ )
		{
			$IdRow = $AllIds->fetch_assoc();
			
			echo "Getting row ".$IdRow['post_ID']."... <br />";
			RcExport_ExportPost( $fp , $IdRow['post_ID'] );
		}
		$AllIds->free();
	}
	
	// RcExport_ExportPost( $fp , 44 );
	// RcExport_ExportPost( $fp , 47 );
	// RcExport_ExportPost( $fp , 206 );
	// fwrite( $fp , "AUTHOR: Jack Everett\n" ) or die('Couldn\'t write');
	
	echo 'Closing file<br/>';
	fclose( $fp );
}

RcExport_Process();

PluginManager_Deinit();
RCSettings_Deinit();
RCSql_Disconnect();
?>
