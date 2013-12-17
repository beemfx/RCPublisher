<?php

/* Basically RCError is a que of error messages that can be pumped out at any
 * time that way error messagse may be collected before the page is displayed
 * and then showed at some point.
 * 
 * These error messages are not meant to be software error messages, but user
 * error messages such as putting in the wrong password etc. Actually software
 * errors should be handled by asserts.
 */

$RCError_NumErrors = 0;
$RCError_ErrorStack = array();

function RCError_PushError($Message , $Type = 'warning' )
{
	assert( 'string' == gettype($Type) );
	assert( 'string' == gettype($Message) );
	
	global $RCError_NumErrors;
	global $RCError_ErrorStack;
	
	$RCError_ErrorStack[$RCError_NumErrors] = array( 'message' => $Message , 'type' => $Type );
	$RCError_NumErrors++;
	
}

function RCError_GetErrorText()
{
	global $RCError_NumErrors;
	global $RCError_ErrorStack;
	
	$Out = '';
	
	for( $i = 0; $i < $RCError_NumErrors; $i++ )
	{
		$Color = 'red';
		switch( $RCError_ErrorStack[$i]['type'] )
		{
			case 'warning': $Color = 'yellow'; break;
			case 'error': $Color = 'red'; break;
			case 'message': $Color = 'green'; break;
			default: $Color = 'green'; break;
		}
		
		$Out .= '<p style="color:'.$Color.'">'.$RCError_ErrorStack[$i]['message'].'</p>';
	}
	
	return $Out;
}

?>
