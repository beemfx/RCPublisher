<?php
//There are basically two options, the setup stage, and the actuall
//install stage. The setup stage occurs first, and allows the user to
//specify certain information. The install stage occurs after that, where
//all the tables are setup, and the configuration file is created.
//install.php should be deleted after it is used, also after it is installed
//the entire directory can be closed off from the rest of the web.

require( '../classes/rclibrary.php' );

StartHTML();

$Stage = RCWeb_GetPost( 'stage' , 0 );

if( 1 == $Stage )
{
	DoInstall();
}
else
{
	DoSetup();
}

EndHTML();

//Functions:

function CreateTable( $db , $sPrefix , $sEngine , $ITEM )
{
	$sTableName = $sPrefix.$ITEM[ 'name' ];

	echo '<p>Creating table '.$sTableName.'...</p>';

	DoQuery( $db , 'drop table if exists '.$sTableName );

	//Create article table:
	$qry = sprintf( 'CREATE TABLE %s ( %s ) engine = %s' , $sTableName , $ITEM[ 'struct' ] , $sEngine );


	$res = DoQuery( $db , $qry );

	return $res;
}

function DoInstall()
{
	//Should strip slashes from all posts here.
	$TABLES[ 'page' ] = array
		(
		'name' => 'tblPage' ,
		'struct' =>
		'`id` int(11) NOT NULL auto_increment,
		  `txtSlug` char(32) NOT NULL,
		  `txtTitle` char(64) NOT NULL,
		  `txtBodyHTMLCache` text NOT NULL,
		  `idVersion_Current` int(11) NOT NULL,
		  `idCreator` int(11) NOT NULL,
		  `idOwner` int(11) NOT NULL,
		  PRIMARY KEY  (`id`),
		  UNIQUE KEY `txtSlug` (`txtSlug`)' ,
	);

	$TABLES[ 'page_history' ] = array
		(
		'name' => 'tblPageHistory' ,
		'struct' =>
		'`id` int(11) NOT NULL auto_increment,
        `idPage` int(11) NOT NULL,
        `idVersion` int(11) NOT NULL,
		  `txtTitle` char(64) NOT NULL,
		  `txtBody` text NOT NULL,
		  `dt` datetime NOT NULL,
		  `idUser` int(11) NOT NULL,
		  PRIMARY KEY  (`id`),
        KEY  (`idVersion`)'
	);

	$TABLES[ 'comment' ] = array
		(
		'name' => 'tblComment' ,
		'struct' =>
		'`id` int(11) NOT NULL auto_increment,
		  `idContent` int(11) NOT NULL,
		  `idUser` int(11) default NULL,
		  `txtName` char(50) NOT NULL,
		  `txtEmail` char(50) default NULL,
		  `txtComment` text NOT NULL,
		  `txtCommentFormat` text NOT NULL,
		  `dtPosted` datetime NOT NULL,
		  `bApproved` tinyint(1) NOT NULL,
		  PRIMARY KEY  (`id`),
		  KEY `idContent` (`idContent`,`dtPosted`)' ,
	);

	$TABLES[ 'globalsettings' ] = array
		(
		'name' => 'tblGlobalSettings' ,
		'struct' =>
		'`id` int(11) NOT NULL auto_increment,
			  `txtName` char(20) NOT NULL,
			  `txtSetting` text NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `txtName` (`txtName`)' ,
	);

	$TABLES[ 'message' ] = array
		(
		'name' => 'tblMessage' ,
		'struct' =>
		'`id` int(11) NOT NULL auto_increment,
			  `idUser_To` int(11) NOT NULL,
			  `idUser_From` int(11) default NULL,
			  `txtName` char(25) default NULL,
			  `txtEmail` char(40) NOT NULL,
			  `txtSubject` char(100) NOT NULL,
			  `txtMessage` text NOT NULL,
			  `bRead` tinyint(1) NOT NULL,
                          `bDeleted` tinyint(1) NOT NULL,
			  `dtSent` datetime NOT NULL,
			  PRIMARY KEY  (`id`),
			  KEY `idUser_To` (`idUser_To`,`dtSent`)' ,
	);

	$TABLES[ 'news' ] = array
		(
		'name' => 'tblNews' ,
		'struct' =>
		'`id` int(11) NOT NULL auto_increment,
			  `idUser` int(11) NOT NULL,
			  `dtPosted` datetime NOT NULL,
			  `txtTitle` char(120) NOT NULL,
			  `txtBody` text NOT NULL,
			  `txtBodyHTMLCache` text NOT NULL,
			  `bVisible` tinyint(1) NOT NULL default \'1\',
			  PRIMARY KEY  (`id`),
			  KEY `dtPosted` (`dtPosted`)' ,
	);

	$TABLES[ 'user' ] = array
		(
		'name' => 'tblUser' ,
		'struct' =>
		'`id` int(11) NOT NULL auto_increment,
		  `txtUserName` char(32) NOT NULL,
		  `txtPassword` char(41) NOT NULL,
		  `txtAlias` varchar(32) NOT NULL,
		  `txtEmail` char(32) NOT NULL,
		  `nAccessLevel` int(11) NOT NULL,
        `nPerms` int(11) NOT NULL,
		  `txtLastIP` char(16) NOT NULL,
		  `txtLastIP2` char(16) NOT NULL,
		  `nLastUpdateIP` int(11) NOT NULL,
		  PRIMARY KEY  (`id`,`txtUserName`)' ,
	);

	$TABLES[ 'files' ] = array
		(
		'name' => 'tblFiles' ,
		'struct' =>
		'`id` int(11) NOT NULL auto_increment,
		  `txtSlug` varchar(20) NOT NULL,
		  `txtName` varchar(20) NOT NULL,
		  `txtExt` varchar(10) NOT NULL,
		  `txtType` varchar(20) NOT NULL,
		  `dt` datetime NOT NULL,
		  `txtLocalPath` varchar(128) NOT NULL,
		  `txtDesc` text NOT NULL,
		  PRIMARY KEY  (`id`),
		  UNIQUE KEY `txtSlug` (`txtSlug`)' ,
	);




	//Install, first attempt to login to the database, and create the tables:
	// Connect to the database:
	echo '<p>Attempting to connect to database...</p>';
	@ $db = new mysqli( RCWeb_GetPost( 'db_host' ) , RCWeb_GetPost( 'db_user' ) , RCWeb_GetPost( 'db_pass' ) , RCWeb_GetPost( 'db_dbname' ) );

	if( mysqli_connect_errno() )
	{
		echo '<p>Could not connect to the database, possible invalid data.</p>';
		return false;
	}
	echo '<p>Connected to database.</p>';

	//Create the tables:
	$res = true;
	foreach( $TABLES as $TABLE )
	{
		$res = $res && CreateTable( $db , RCWeb_GetPost( 'rc_prefix' ) , RCWeb_GetPost( 'db_engine' ) , $TABLE );
	}

	if( !$res )
	{
		echo '<p>Error creating tables (see above).</p>';
		$db->close();
		return false;
	}

	//Set the inital settings.
	$InitialSettings = array
		(
		'nHomeNewsStories' => addslashes( '5' ) ,
		'txtNav' => addslashes( '[[home Home]][[login Log In]][[newpage New Page]]' ) ,
		'txtMiniNav' => addslashes( '[[contact Contact]]' ) ,
		'txtSkin' => addslashes( 'default' ) ,
		'nThumbnailQuality' => '100',
		'nThumbnailWidth' => '300',
	);

	foreach( $InitialSettings as $Setting => $Value )
	{
		$qry = sprintf( 'insert into '.RCWeb_GetPost( 'rc_prefix' ).'tblGlobalSettings (txtName, txtSetting) values ("%s", "%s")' , $Setting , $Value );
		DoQuery( $db , $qry );
	}

	//Create the default user.
	$FULL_PERMS = 0x0FFFFFFF;
	$DefaultUser = array
		(
		'txtUserName' => '"'.addslashes( 'admin' ).'"' ,
		'txtPassword' => 'MD5("admin")' ,
		'txtAlias' => '"'.addslashes( 'Administrator Account' ).'"' ,
		'txtEmail' => '"'.addslashes( RCWeb_GetPost( 'rc_adminemail' ) ).'"' ,
		'nAccessLevel' => '"'.addslashes( '10' ).'"' ,
		'nPerms' => '"'.addslashes( $FULL_PERMS ).'"' ,
		'txtLastIP' => '"'.addslashes( '' ).'"' ,
		'txtLastIP2' => '"'.addslashes( '' ).'"' ,
		'nLastUpdateIP' =>'"'.addslashes('0').'"',
	);

	$qry = 'insert into '.RCWeb_GetPost( 'rc_prefix' ).'tblUser ('.implode( ',' , array_keys( $DefaultUser ) ).') values ('.implode( ',' , array_values( $DefaultUser ) ).')';
	DoQuery( $db , $qry );

	//Saving configuration:
	echo '<p>Saving configuration file...</p>';
	$fout = fopen( 'config.php' , 'w' );
	if( !$fout )
	{
		echo '<p>Could not create settings file for writing.</p>';
		$db->close();
		return false;
	}

	fprintf( $fout , "<?php\n" );
	fprintf( $fout , "\t\$g_rcPrefix = \"%s\";\n\n" , RCWeb_GetPost( 'rc_prefix' ) );
	fprintf( $fout , "\t\$g_rcFilepath = \"%s\";\n\n" , RCWeb_GetPost( 'rc_filepath' ) );
	fprintf( $fout , "\t\$g_rcWWWPath = \"%s\";\n\n" , RCWeb_GetPost( 'rc_wwwpath' ) );
	fprintf( $fout , "\t\$g_rcDBHost = \"%s\";\n" , RCWeb_GetPost( 'db_host' ) );
	fprintf( $fout , "\t\$g_rcDBUser = \"%s\";\n" , RCWeb_GetPost( 'db_user' ) );
	fprintf( $fout , "\t\$g_rcDBPwd = \"%s\";\n" , RCWeb_GetPost( 'db_pass' ) );
	fprintf( $fout , "\t\$g_rcDBName = \"%s\";\n" , RCWeb_GetPost( 'db_dbname' ) );

	fprintf( $fout , "?>\n" );
	fclose( $fout );

	echo '<p>Closing connection to database.</p>';
	$db->close();
	echo '<p>Successfully installed. Please copy the config.php file to the config directory, and it is now recommended that you delete the
			install direcotry. You may log in with username: admin password: admin</p>';
}

function DoQuery( $db , $qry )
{
	$res = $db->query( $qry );
	if( !$res )
	{
		echo '<p>'.$qry.'</p>';
		printf( "<p>MySQL Querry Error: %s.</p>>\n" , $db->error );
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
	global $g_rcWWWPath;
	global $g_rcDBHost;
	global $g_rcDBPwd;
	global $g_rcDBUser;
	global $g_rcDBUser;
	
	$RequestUri = preg_replace( '/install\//' , '', $_SERVER['REQUEST_URI'] );
	$DefaultWWW = sprintf( 'http://%s%s' , $_SERVER['SERVER_NAME'], $RequestUri );
	
	?>
	<h1>RC Publisher Installer</h1>
	<form method="post" action="index.php">
		<input type="hidden" name="stage" value="1" />
		<h3>Database Setup</h3>
		<table>
			<tr>
				<th>Username:</th><td><input type="text" name="db_user" value="<?php echo $g_rcDBUser; ?>"/></td>
			</tr>
			<tr>
				<th>Password:</th><td><input type="text" name="db_pass" value="<?php echo $g_rcDBPwd; ?>" /></td>
			</tr>
			<tr>
				<th>Host address:</th><td><input type="text" name="db_host" value="<?php echo $g_rcDBHost; ?>"/></td>
			</tr>
			<tr>
				<th>Database:</th><td><input type="text" name="db_dbname" value="<?php echo $g_rcDBUser; ?>"/></td>
			</tr>
			<tr>
				<th>Engine:</th><td><select name="db_engine"><option>innodb</option><option>myisam</option></select>
			</tr>
		</table>
		<h3>RC Publisher Setup</h3>
		<table>
			<tr>
				<th>Files Location (relative to base URL not this software):</th><td style="width:50%"><input style="width:100%" type="text" name="rc_filepath" value="<?php echo strlen( $g_rcFilepath ) > 0 ? $g_rcFilepath : "rcfiles"; ?>"/></td>
			</tr>
			<tr>
				<th>Full URL To Root (i.e. http://www.domain.com/path/):</th><td><input style="width:100%" type="text" name="rc_wwwpath" value="<?php echo strlen( $g_rcWWWPath ) > 0 ? $g_rcWWWPath : $DefaultWWW; ?>"/></td>
			</tr>
			<tr>
				<th>Table prefix:</th><td><input type="text" name="rc_prefix" value="rc2_"/></td>
			</tr>
			<tr>
				<th>Admin Email:</th><td><input type="text" name="rc_adminemail" value=""/></td>
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
				body{width:900px;margin:0 auto;border:2px solid red;padding:1em;}
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
