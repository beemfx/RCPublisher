<?php

require_once('table_user.php');

$g_rcCookieName = 'rc2pageh';

function RCSession_Begin()
{	
	global $g_rcCookieName;
	session_start();
	//Always reset the user level.
	$_SESSION['user_level'] = 0;
	//Always reset the user id;
	$_SESSION['user_id'] = -1;
	
	//Generate a login key if one wasn't set already.
	if(!isset($_SESSION['login_key']))
	{
		$code = 'abcdefghijklmnopqrstuvwxyz12345678';
		$key='';
		srand(time());
		for($i=0; $i<64; $i++)
		{
			$key = $key.$code[rand()%(strlen($code))];
		}
		$_SESSION['login_key'] = $key;
	}
	
	//Check if there is a login cookie, and if not already logged in
	//automatically login if the ip address is the same.
	if(isset($_COOKIE[$g_rcCookieName]) && $_COOKIE[$g_rcCookieName] != 0)
	{
		$T = new CTableUser();
		
		$Info = $T->GetUserInfo((int)$_COOKIE[$g_rcCookieName]);

		if($Info['txtLastIP']==$_SERVER['REMOTE_ADDR'])
		{
			$_SESSION['user']       = $Info['txtUserName'];
			$_SESSION['user_alias'] = $Info['txtAlias'];
			$_SESSION['user_id']    = $Info['id'];
			$_SESSION['user_level'] = $Info['nAccessLevel'];
			$_SESSION['user_email'] = $Info['txtEmail'];
		}
	}
}

function RCSession_Connect($strUser, $strFullHashPwd, $strSalt, $bRemember)
{	
	global $g_rcCookieName;
	$T = new CTableUser();
	
	$nID = $T->GetUserId($strUser, $strFullHashPwd, $strSalt);
	
	if(false === $nID)
	{
		//echo 'Failed to login.';
		return false;
	}
	
	$Info = $T->GetUserInfo($nID);
	
	$_SESSION['user_id']    = $Info['id'];
	$_SESSION['user']       = $Info['txtUserName'];
	$_SESSION['user_alias'] = $Info['txtAlias'];
	$_SESSION['user_level'] = $Info['nAccessLevel'];
	$_SESSION['user_email'] = $Info['txtEmail'];
	
	setcookie($g_rcCookieName, (int)$_SESSION['user_id'], time()+3600*24*365);
	
	//Update the the IP to the current IP since we are logging in from there.
	$T->UpdateIP($nID, $_SERVER['REMOTE_ADDR']);
	return true;
}

function RCSession_GetUserProp($property)
{
	global $g_rcCookieName;
	assert(isset($_SESSION[$property]));
	return $_SESSION[$property];
}

function RCSession_Disconnect()
{
	global $g_rcCookieName;
	session_destroy();
	setcookie($g_rcCookieName, '', -1);
	
	$_SESSION['user_id']    = 0;
	$_SESSION['user']       = '';
	$_SESSION['user_alias'] = '';
	$_SESSION['user_level'] = 0;
	$_SESSION['user_email'] = '';
}

?>
