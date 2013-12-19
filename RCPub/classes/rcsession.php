<?php

require_once('table_user.php');

define( 'RCSESSION_COOKIENAME' , 'rc2pageh' );

function RCSession_Begin()
{
	session_start();
	//Always reset the user level.
	$_SESSION[ 'user_level' ] = 0;
	RCSession_SetPermissions( 0 );
	//Always reset the user id;
	$_SESSION[ 'user_id' ] = -1;

	//Generate a login key if one wasn't set already.
	if( !isset( $_SESSION[ 'login_key' ] ) )
	{
		$code = 'abcdefghijklmnopqrstuvwxyz12345678';
		$key = '';
		srand( time() );
		for( $i = 0; $i < 64; $i++ )
		{
			$key = $key.$code[ rand() % (strlen( $code )) ];
		}
		$_SESSION[ 'login_key' ] = $key;
	}

	//Check if there is a login cookie, and if not already logged in
	//automatically login if the ip address is the same.
	if( isset( $_COOKIE[ RCSESSION_COOKIENAME ] ) && $_COOKIE[ RCSESSION_COOKIENAME ] != 0 )
	{
		$T = new CTableUser();

		$Info = $T->GetUserInfo( ( int ) $_COOKIE[ RCSESSION_COOKIENAME ] );

		if( $Info[ 'txtLastIP' ] == $_SERVER[ 'REMOTE_ADDR' ] )
		{
			$_SESSION[ 'user' ] = $Info[ 'txtUserName' ];
			$_SESSION[ 'user_alias' ] = $Info[ 'txtAlias' ];
			$_SESSION[ 'user_id' ] = $Info[ 'id' ];
			$_SESSION[ 'user_level' ] = $Info[ 'nAccessLevel' ];
			$_SESSION[ 'user_email' ] = $Info[ 'txtEmail' ];
			RCSession_SetPermissions( ( int ) $Info[ 'nPerms' ] );
		}
	}
}

function RCSession_Connect( $strUser , $strFullHashPwd , $strSalt , $bRemember )
{
	$T = new CTableUser();

	$nID = $T->GetUserId( $strUser , $strFullHashPwd , $strSalt );

	if( false === $nID )
	{
		//echo 'Failed to login.';
		return false;
	}

	$Info = $T->GetUserInfo( $nID );

	$_SESSION[ 'user_id' ] = $Info[ 'id' ];
	$_SESSION[ 'user' ] = $Info[ 'txtUserName' ];
	$_SESSION[ 'user_alias' ] = $Info[ 'txtAlias' ];
	$_SESSION[ 'user_level' ] = $Info[ 'nAccessLevel' ];
	$_SESSION[ 'user_email' ] = $Info[ 'txtEmail' ];
	RCSession_SetPermissions( ( int ) $Info[ 'nPerms' ] );

	setcookie( RCSESSION_COOKIENAME , ( int ) $_SESSION[ 'user_id' ] , time() + 3600 * 24 * 365 );

	//Update the the IP to the current IP since we are logging in from there.
	$T->UpdateIP( $nID , $_SERVER[ 'REMOTE_ADDR' ] );
	return true;
}

function RCSession_GetUserProp( $property )
{
	assert( isset( $_SESSION[ $property ] ) );
	if( 'user_id' == $property )
	{
		return (int)$_SESSION[$property];
	}
	return $_SESSION[ $property ];
}

function RCSession_SetUserProp( $Prop , $NewValue )
{
	assert( isset( $_SESSION[ $Prop ] ) );
	$_SESSION[ $Prop ] = $NewValue;
}

function RCSession_Disconnect()
{
	session_destroy();
	setcookie( RCSESSION_COOKIENAME , '' , -1 );

	$_SESSION[ 'user_id' ] = 0;
	$_SESSION[ 'user' ] = '';
	$_SESSION[ 'user_alias' ] = '';
	$_SESSION[ 'user_level' ] = 0;
	$_SESSION[ 'user_email' ] = '';
}

define( 'RCSESSION_CREATEPAGE' , (1 << 0 ) );
define( 'RCSESSION_MODIFYPAGE' , (1 << 1 ) );
define( 'RCSESSION_DELETEPAGE' , (1 << 2 ) );
define( 'RCSESSION_CREATENEWS' , (1 << 3 ) );
define( 'RCSESSION_MODIFYNEWS' , (1 << 4 ) );
define( 'RCSESSION_DELETENEWS' , (1 << 5 ) );
define( 'RCSESSION_CREATEFILE' , (1 << 6 ) );
define( 'RCSESSION_MODIFYFILE' , (1 << 7 ) );
define( 'RCSESSION_DELETEFILE' , (1 << 8 ) );
define( 'RCSESSION_CREATEUSER' , (1 << 9 ) );
define( 'RCSESSION_MODIFYUSER' , (1 << 10 ) );
define( 'RCSESSION_DELETEUSER' , (1 << 11 ) );
define( 'RCSESSION_EDITSETTINGS' , (1 << 12 ) );
define( 'RCSESSION_CONTACTUSER' , (1 << 13 ) );
define( 'RCSESSION_CREATEFEEDBACK' , (1 << 14 ) );
define( 'RCSESSION_MODIFYFEEDBACK' , (1 << 15 ) );
define( 'RCSESSION_DELETEFEEDBACK' , (1 << 16 ) );

function RCSession_IsPermissionAllowed( $Perm )
{
	assert( 'integer' == gettype( $Perm ) );

	return 0 != ($Perm & $_SESSION[ 'user_permissions' ]);
}

function RCSession_IsUserLoggedIn()
{
	return RCSession_GetUserProp( 'user_level' ) > 0;
}

function RCSession_SetPermissions( $UserPerms )
{
	assert( 'integer' == gettype( $UserPerms ) );

	//Reset permissions.
	$_SESSION[ 'user_permissions' ] = 0x00000000;

	//These permissions come from the user profile.
	$_SESSION[ 'user_permissions' ] |= $UserPerms; //RCSession_GetUserProp( 'user_level' ) > 0 ? 0xFFFFFFFF : 0;
	//The following permissions are always allowed:
	$_SESSION[ 'user_permissions' ] |= RCSESSION_CONTACTUSER;
	$_SESSION[ 'user_permissions' ] |= RCSESSION_CREATEFEEDBACK;
}

?>
