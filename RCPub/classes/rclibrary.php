<?php

//Some globals
define( "RCRX_PAGESLUG" , '/^[A-Za-z0-9_]{1,32}$/' );
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
	$strEmailRegEx = "/^[^0-9][A-z0-9_]+([.][A-z0-9_]+)*[@][A-z0-9_]+([.][A-z0-9_]+)*[.][A-z]{2,20}$/";
	return preg_match( $strEmailRegEx , $strE ) != 0;
}

function RCWeb_ClearPostData()
{
	$_POST = array();
}

function RCSpam_DisplayQuestion()
{
	// echo '<img src="captcha/captcha_image.php" alt="Security Image" border="0"/>';
	// echo 'To verify you are real please type in the name of the author of this blog.';
}

function RCSpam_DisplayResponseArea()
{
	// echo 'Type in the letters and numbers <input type="text" name="rcspam_secure"/>';
	echo '<div class="g-recaptcha" data-sitekey="6LdZl3UUAAAAAFl0QYDomTMckYcP0dNnsjaEJnyC"></div>';
}

function RCSpam_GetPreScript()
{
	return "<script src='https://www.google.com/recaptcha/api.js'></script>";
}

function RCSpam_IsAnswerCorrect()
{
	$post_data = http_build_query(
		 array(
			  'secret' => '6LdZl3UUAAAAAKoR7bs-wJeW_gIpfFacw6AANhyN',
			  'response' => $_POST['g-recaptcha-response'],
			  'remoteip' => $_SERVER['REMOTE_ADDR']
		 )
	);
	$opts = array('http' =>
		 array(
			  'method'  => 'POST',
			  'header'  => 'Content-type: application/x-www-form-urlencoded',
			  'content' => $post_data
		 )
	);
	$context  = stream_context_create($opts);
	$response = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
	$result = json_decode($response);
	return $result->success;

	/*
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
	*/
}


$RCSettings_Table = null;

function RCSettings_Init()
{
	global $RCSettings_Table;
	assert( null == $RCSettings_Table );
	
	$RCSettings_Table = new CTableSettings();
}

function RCSettings_Deinit()
{
	global $RCSettings_Table;
	assert( null != $RCSettings_Table );
	
	$RCSettings_Table = null;
}

function RCSettings_GetSetting( $Setting )
{
	global $RCSettings_Table;
	assert( null != $RCSettings_Table );
	
	return $RCSettings_Table->GetSetting( $Setting );
}

function RCSettings_SetSetting( $Setting , $Value )
{
	global $RCSettings_Table;
	assert( null != $RCSettings_Table );
	
	$RCSettings_Table->SetSetting( $Setting , $Value );
}

?>