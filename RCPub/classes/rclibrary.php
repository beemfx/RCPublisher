<?php

function RCWeb_GetGet( $VarName , $NotSetValue = null, $AssertIfNotExists = false )
{
	assert( 'string' == gettype($VarName) );
	assert( 'boolean' == gettype($AssertIfNotExists) );
	
	if( isset( $_GET[$VarName] ) )return $_GET[$VarName];
	assert(!$AssertIfNotExists || false);
	return $NotSetValue;
}

function RCWeb_GetPost( $VarName , $NotSetValue = null, $AssertIfNotExists = false )
{
	assert( 'string' == gettype($VarName) );
	assert( 'boolean' == gettype($AssertIfNotExists) );
	
	if( isset( $_POST[$VarName] ) )return $_POST[$VarName];
	assert(!$AssertIfNotExists || false);
	return $NotSetValue;
}

function RCWeb_GetSession( $VarName , $NotSetValue = null, $AssertIfNotExists = false )
{
	assert( 'string' == gettype($VarName) );
	assert( 'boolean' == gettype($AssertIfNotExists) );
	
	if( isset( $_SESSION[$VarName] ) )return $_SESSION[$VarName];
	assert(!$AssertIfNotExists || false);
	return $NotSetValue;
}

function RCWeb_ValidateEmail($strE)
{
	$strEmailRegEx = "/^[^0-9][A-z0-9_]+([.][A-z0-9_]+)*[@][A-z0-9_]+([.][A-z0-9_]+)*[.][A-z]{2,4}$/";
	return preg_match($strEmailRegEx, $strE)!=0;
}

?>