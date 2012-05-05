<?php
/* RCSql is a basic interface for creating a database for RC Publisher.
 * Basically every page requires the database, so we just connect to it
 * one time and disconnect one time, and then any class that needs the
 * database can call RCSql_GetDb. The database always exists. It exists
 * before any page is loaded.
 * 
 * Copryight (c) 2012, Beem Software
 */
require_once( 'config/config.php' );

$g_rcSQL = null;

function RCSql_Connect()
{
	global $g_rcSQL;
	
	global $g_rcDBHost;
	global $g_rcDBUser;
	global $g_rcDBPwd;
	global $g_rcDBName;
		
	assert(null == $g_rcSQL);
				// Connect to the database:
	@ $g_rcSQL = new mysqli(
		$g_rcDBHost,
		$g_rcDBUser,
		$g_rcDBPwd,
		$g_rcDBName);

	if(mysqli_connect_errno())
	{
		print('A problem occured while connecting to the database. Try again later.');
		$g_rcSQL = null;
		return;
	}
}

function RCSql_Disconnect()
{
	global $g_rcSQL;
	assert('mysqli' == get_class($g_rcSQL));
	
	$g_rcSQL->close();

	$g_rcSQL = null;
}

function RCSql_GetDb()
{
	global $g_rcSQL;
	assert('mysqli' == get_class($g_rcSQL));
	return $g_rcSQL;
}

?>
