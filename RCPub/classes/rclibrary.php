<?php

//Some globals
define( "RCRX_PAGESLUG" , '/^[A-Za-z0-9_]+$/' );
define( "RCRX_USERNAME" , '/^[A-Za-z0-9]{5,}$/' );
define( "RCRX_USERALIAS" , '/^[A-Za-z0-9_ ]{1,}$/' );
define( "RCRX_USERALIAS_REQ", 'Names may only contain letters, numbers, spaces, and -,_.' );
define( "RCRX_PASSWORD" , '/^[a-zA-Z0-9_*#]{5,}$/' );
define( "RCRX_PASSWORD_REQ" , 'Passwords must be 5 characters long, and may only consist of letters, numbers, _, *, and #.' );
define( 'MAX_COMMENT_LEN' , 1000 );
define( 'MAX_EMAIL_LEN', 5000 );

function RCWeb_GetGet( $VarName , $NotSetValue = null , $AssertIfNotExists = false )
{
	assert( 'string' == gettype( $VarName ) );
	assert( 'boolean' == gettype( $AssertIfNotExists ) );

	if( isset( $_GET[ $VarName ] ) )
		return $_GET[ $VarName ];
	assert( !$AssertIfNotExists || false );
	return $NotSetValue;
}

function RCWeb_GetPost( $VarName , $NotSetValue = null , $AssertIfNotExists = false )
{
	assert( 'string' == gettype( $VarName ) );
	assert( 'boolean' == gettype( $AssertIfNotExists ) );

	if( isset( $_POST[ $VarName ] ) )
	{
		return $_POST[ $VarName ];
	}
	assert( !$AssertIfNotExists || false );
	return $NotSetValue;
}

function RCWeb_SetPost( $VarName , $Value )
{
	assert( 'string' == gettype( $VarName ) );
	$_POST[ $VarName ] = $Value;
}

function RCWeb_GetSession( $VarName , $NotSetValue = null , $AssertIfNotExists = false )
{
	assert( 'string' == gettype( $VarName ) );
	assert( 'boolean' == gettype( $AssertIfNotExists ) );

	if( isset( $_SESSION[ $VarName ] ) )
		return $_SESSION[ $VarName ];
	assert( !$AssertIfNotExists || false );
	return $NotSetValue;
}

function RCWeb_ValidateEmail( $strE )
{
	$strEmailRegEx = "/^[^0-9][A-z0-9_]+([.][A-z0-9_]+)*[@][A-z0-9_]+([.][A-z0-9_]+)*[.][A-z]{2,4}$/";
	return preg_match( $strEmailRegEx , $strE ) != 0;
}

function RCWeb_ClearPostData()
{
	$_POST = array();
}

function RCSpam_DisplayQuestion()
{
	echo '<img src="captcha/captcha_image.php" alt="Security Image" border="0"/>';
}

function RCSpam_DisplayResponseArea()
{
	echo 'Type in the letters and numbers <input type="text" name="rcspam_secure"/>';
}

function RCSpam_IsAnswerCorrect()
{
	$Response = strtoupper( trim( strip_tags( RCWeb_GetPost( 'rcspam_secure' , '' , true ) ) ) );
	$Correct = RCWeb_GetSession( 'captcha' , '' , true );

	if( strlen( $Correct ) < 1 )
		return false;
	if( strlen( $Response ) < 1 )
		return false;

	if( $Response != $Correct )
	{
		return false;
	}
	return true;
}

?>