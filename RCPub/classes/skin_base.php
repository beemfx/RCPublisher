<?php

interface ISkin
{
	function GetId();
	function BeginHTML( $Page );
	function DrawPage( $Page );
	function EndHTML( $Page );
}

?>