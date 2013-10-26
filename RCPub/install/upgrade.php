<?php
//There are basically two options, the setup stage, and the actuall
//install stage. The setup stage occurs first, and allows the user to
//specify certain information. The install stage occurs after that, where
//all the tables are setup, and the configuration file is created.

//install.php should be deleted after it is used, also after it is installed
//the entire directory can be closed off from the rest of the web.
ini_set('error_reporting', E_ALL);

require_once('../config/config.php');
require_once('../classes/rcsql.php');

RCSql_Connect();
StartHTML();

if(isset($_POST['stage']) && 1==$_POST['stage']) {
	DoUpgrade_CreateNewTables();
}
else {
	DoSetup();
}

EndHTML();

RCSql_Disconnect();


//Functions:

function CreateTable($db, $sPrefix, $sEngine, $ITEM)
{
	$sTableName = $sPrefix.$ITEM['name'];
	
	echo '<p>Creating table '.$sTableName.'...</p>';

	DoQuery($db, 'drop table if exists '.$sTableName);

	//Create article table:
	$qry = sprintf('CREATE TABLE %s ( %s ) engine = %s', $sTableName, $ITEM['struct'], $sEngine);

	
	$res=DoQuery($db, $qry);
	
	return $res;
}

function DoUpgrade_CreateNewTables()
{
	global $g_rcPrefix;	
	//Should strip slashes from all posts here.
	$TABLES['page'] = array
	( 
		'name' => 'tblPageHistory',
		 
		'struct' => 
        '`id` int(11) NOT NULL auto_increment,
        `idPage` int(11) NOT NULL,
        `idVersion` int(11) NOT NULL,
		  `txtTitle` char(64) NOT NULL,
		  `txtBody` text NOT NULL,
		  `dt` datetime NOT NULL,
		  PRIMARY KEY  (`id`),
        KEY  (`idVersion`)'
			  
	);
	
	//Install, first attempt to login to the database, and create the tables:
	// Connect to the database:
	echo '<p>Attempting to connect to database...</p>';
	@ $db = RCSql_GetDb();

	if( null == $db )
	{
		echo '<p>Could not connect to the database, possible invalid data.</p>';
		return false;
	}
	echo '<p>Connected to database.</p>';

	//Create the tables:
	$res = true;
	foreach($TABLES as $TABLE)
	{
		$res = $res && CreateTable($db, $g_rcPrefix, $_POST['db_engine'], $TABLE);
	}

	if(!$res)
	{
		echo '<p>Error creating tables (see above).</p>';
		return false;
	}
	
	//Add idVersion_Current to this page.
	DoQuery( $db , 'ALTER TABLE '.$g_rcPrefix.'tblPage ADD idVersion_Current int(11)' );
	DoQuery( $db , 'UPDATE '.$g_rcPrefix.'tblPage SET idVersion_Current=1');
	
	//Todo, take the current pages and create histories for them,
	//Then drop the pages and make appropriate pages.

	echo '<p>Successfully upgraded.</p>';
}

function DoQuery($db, $qry)
{
	$res = $db->query($qry);
	if(!$res)
	{
		echo '<p>'.$qry.'</p>';
		printf("<p>MySQL Querry Error: %s.</p>>\n", $db->error);
	}
	return $res;
}

function DoSetup()
{
?>
<h1>RC Publisher Upgrade</h1>
<form method="post" action="upgrade.php">
	<input type="hidden" name="stage" value="1" />
	<table>
		<tr>
			<th>Engine:</th><td><select name="db_engine"><option>innodb</option><option>myisam</option></select>
		</tr>
	</table>
	<center><input type="submit" value="Install"/></center>
</form>
<?php
}

function StartHTML()
{
?>
<html>
<head>
<title>RC Publisher Installer</title>
<style type="text/css">
	body{width:400px;margin:0 auto;border:2px solid red;padding:1em;}
	th{text-align:right}
</style>
</head>
<body>
<?php
}

function EndHTML()
{
?>
</body>
</html>
<?php
}

?>
