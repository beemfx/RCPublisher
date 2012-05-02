<?php
//There are basically two options, the setup stage, and the actuall
//install stage. The setup stage occurs first, and allows the user to
//specify certain information. The install stage occurs after that, where
//all the tables are setup, and the configuration file is created.

//install.php should be deleted after it is used, also after it is installed
//the entire directory can be closed off from the rest of the web.

require('../config/config.php'); //This is just temporary so I can quickly test this.

StartHTML();

if(1==$_POST['stage']) {
	DoInstall();
}
else {
	DoSetup();
}

EndHTML();


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

function DoInstall()
{
	//Should strip slashes from all posts here.
	$TABLES['page'] = array
	( 
		'name' => 'tblPage',
		 
		'struct' => 
			'`id` int(11) NOT NULL auto_increment,
		  `txtSlug` char(32) NOT NULL,
		  `txtTitle` char(64) NOT NULL,
		  `txtContent` text NOT NULL,
		  `txtHTMLCache` text NOT NULL,
		  PRIMARY KEY  (`id`),
		  UNIQUE KEY `txtSlug` (`txtSlug`)',	  
	);
	
	$TABLES['comment'] = array
	( 
		'name' => 'tblComment',
		 
		'struct' => 
			'`id` int(11) NOT NULL auto_increment,
		  `idContent` int(11) NOT NULL,
		  `idUser` int(11) default NULL,
		  `txtName` char(20) NOT NULL,
		  `txtEmail` char(20) default NULL,
		  `txtComment` text NOT NULL,
		  `txtCommentFormat` text NOT NULL,
		  `dtPosted` datetime NOT NULL,
		  `bApproved` tinyint(1) NOT NULL,
		  PRIMARY KEY  (`id`),
		  KEY `idContent` (`idContent`,`dtPosted`)',	  
	);
	
	$TABLES['globalsettings'] = array
	( 
		'name' => 'tblGlobalSettings',
		 
		'struct' => 
				'`id` int(11) NOT NULL auto_increment,
			  `nNextUL` int(11) NOT NULL COMMENT \'This is the number for the next file to be uploaded.\',
			  `nHomeNewsStories` int(11) NOT NULL COMMENT \'The number of news stories to be shown on the home page.\',
			  `nContentPerPage` int(11) NOT NULL COMMENT \'The amount of content displayed per page in the table of contents.\',
			  `txtTwitterUser` text NOT NULL,
			  `txtTwitterPwd` text NOT NULL,
			  `txtNav` text NOT NULL,
			  `txtMiniNav` text NOT NULL,
			  PRIMARY KEY  (`id`)',	  
	);
	
	$TABLES['message'] = array
	( 
		'name' => 'tblMessage',
		 
		'struct' => 
				'`id` int(11) NOT NULL auto_increment,
			  `idUser_To` int(11) NOT NULL,
			  `idUser_From` int(11) default NULL,
			  `txtName` char(25) default NULL,
			  `txtEmail` char(40) NOT NULL,
			  `txtSubject` char(100) NOT NULL,
			  `txtMessage` text NOT NULL,
			  `bRead` tinyint(1) NOT NULL,
			  `dtSent` datetime NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `idUser_To` (`idUser_To`,`dtSent`)',	  
	);
	
	$TABLES['news'] = array
	( 
		'name' => 'tblNews',
		 
		'struct' => 
				'`id` int(11) NOT NULL auto_increment,
			  `idUser` int(11) NOT NULL,
			  `dtPosted` datetime NOT NULL,
			  `txtTitle` char(120) NOT NULL,
			  `txtBody` text NOT NULL,
			  `txtBodyFormat` text NOT NULL,
			  `bVisible` tinyint(1) NOT NULL default \'1\',
			  PRIMARY KEY  (`id`),
			  KEY `dtPosted` (`dtPosted`)',	  
	);
	
	$TABLES['user'] = array
	( 
		'name' => 'tblUser',
		 
		'struct' => 
			'`id` int(11) NOT NULL auto_increment,
		  `txtUserName` char(32) NOT NULL,
		  `txtPassword` char(41) NOT NULL,
		  `txtAlias` varchar(32) NOT NULL,
		  `txtEmail` char(32) NOT NULL,
		  `nAccessLevel` int(11) NOT NULL,
		  `txtLastIP` char(16) NOT NULL,
		  PRIMARY KEY  (`id`,`txtUserName`)',	  
	);
	



	//Install, first attempt to login to the database, and create the tables:
	// Connect to the database:
	echo '<p>Attempting to connect to database...</p>';
	@ $db = new mysqli($_POST['db_host'],$_POST['db_user'],$_POST['db_pass'],$_POST['db_dbname']);

	if(mysqli_connect_errno())
	{
		echo '<p>Could not connect to the database, possible invalid data.</p>';
		return false;
	}
	echo '<p>Connected to database.</p>';

	//Create the tables:
	$res = true;
	foreach($TABLES as $TABLE)
	{
		$res = $res && CreateTable($db, $_POST['rc_prefix'], $_POST['db_engine'], $TABLE);
	}

	if(!$res)
	{
		echo '<p>Error creating tables (see above).</p>';
		$db->close();
		return false;
	}


	//Saving configuration:
	echo '<p>Saving configuration file...</p>';
	$fout = fopen('config.php', 'w');
	if(!$fout)
	{
		echo '<p>Could not create settings file for writing.</p>';
		$db->close();
		return false;
	}

	fprintf($fout, "<?php\n");
	fprintf($fout, "\t\$g_rcPrefix = \"%s\";\n\n", $_POST['rc_prefix']);
	fprintf($fout, "\t\$g_rcFilepath = \"%s\";\n\n", $_POST['rc_filepath']);
	fprintf($fout, "\t\$g_rcDBHost = \"%s\";\n", $_POST['db_host']);
	fprintf($fout, "\t\$g_rcDBUser = \"%s\";\n", $_POST['db_user']);
	fprintf($fout, "\t\$g_rcDBPwd = \"%s\";\n", $_POST['db_pass']);
	fprintf($fout, "\t\$g_rcDBName = \"%s\";\n", $_POST['db_dbname']);
	
	fprintf($fout, "?>\n");
	fclose($fout);
	
	echo '<p>Closing connection to database.</p>';
	$db->close();
	echo '<p>Successfully installed. It is now recommended that you delete the
			install direcotry.</p>';
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
//In the setup the following options are specified:
//DB Username.
//DB Password.
//DB Database.
//DB Address.
//DB Table prefix.
//Home stories, the number of stories displayed on the home page (or
//whatever page the stories are displayed on).
	global $g_rcPrefix;
	global $g_rcFilepath;
	global $g_rcDBHost;
	global $g_rcDBPwd;
	global $g_rcDBUser;
	global $g_rcDBUser;
	?>
<h1>RC Publisher Installer</h1>
<form method="post" action="index.php">
	<input type="hidden" name="stage" value="1" />
	<h3>Database Setup</h3>
	<table>
		<tr>
			<th>Username:</th><td><input type="text" name="db_user" value="<?php echo $g_rcDBUser;?>"/></td>
		</tr>
		<tr>
			<th>Password:</th><td><input type="text" name="db_pass" value="<?php echo $g_rcDBPwd;?>" /></td>
		</tr>
		<tr>
			<th>Host address:</th><td><input type="text" name="db_host" value="<?php echo $g_rcDBHost;?>"/></td>
		</tr>
		<tr>
			<th>Database:</th><td><input type="text" name="db_dbname" value="<?php echo $g_rcDBUser;?>"/></td>
		</tr>
		<tr>
			<th>Engine:</th><td><select name="db_engine"><option>innodb</option><option>myisam</option></select>
		</tr>
	</table>
	<h3>RC Publisher Setup</h3>
	<table>
		<tr>
			<th>Files Location (relative to base URL not this software):</th><td><input type="text" name="rc_filepath" value="<?php echo strlen($g_rcFilepath) > 0 ? $g_rcFilepath : "rcfiles";?>"/></td>
		</tr>
		<tr>
			<th>Table prefix:</th><td><input type="text" name="rc_prefix" value="rc2_"/></td>
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
