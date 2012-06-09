<?php

/* Basically RCError is a que of error messages that can be pumped out at any
 * time that way error messagse may be collected before the page is displayed
 * and then showed at some point.
 * 
 * These error messages are not meant to be software error messages, but user
 * error messages such as putting in the wrong password etc. Actually software
 * errors should be handled by asserts.
 */

$g_sError = '';

function RCError_PostError($sMsg, $nSeverity = 0)
{
	global $g_sError;
	
	$g_sError .= sprintf('<p style="color:%s">%s</p>', 0 == $nSeverity ? 'red' : 'yellow', $sMsg);
}

function RCError_ShowErrors()
{
	global $g_sError;
	echo $g_sError;
	//Clear the error messages.
	$g_sError = '';
}


?>
