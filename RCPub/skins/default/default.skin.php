<?php

//Very basic:
class RCSkin implements ISkin
{

	function GetId()
	{
		return 'Default Skin';
	}

	function BeginHTML( $Page )
	{
		print( "<!DOCTYPE html>\n<html>\n<head>\n<title>" );
		print( $Page->GetTitle() );
		print( "</title>\n" );
		print( $Page->GetPreClosingHeadScript() );
		print( "</head>\n" );
		print( "<body>\n" );
	}

	function DrawPage( $Page )
	{
		print( $Page->GetHeader() );
		print( "<br />\n" );
		print( $Page->GetNav1() );
		print( "<br />\n" );
		print( $Page->GetNav2() );
		print( "<br />\n" );
		//print( $Page->GetBody() );
		$Page->Display_PageCallback();
		print( "<br />\n" );
		print( $Page->GetFooter() );
	}

	function EndHTML( $Page )
	{
		print( "</body>\n" );
		print( "</html>\n" );
	}

}

?>