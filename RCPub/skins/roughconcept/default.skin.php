<?php

//Very basic:
class RCSkin implements ISkin
{
	function GetId()
	{
		return 'Rough Concept Skin';
	}
	
	function BeginHTML( $Page )
	{
		echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
		//echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
		//echo '<!DOCTYPE HTML>';
		?>
		<html>
		<head>
		<link rel="stylesheet" type="text/css" href="skins/roughconcept/rcskin.css"/>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<title><?php print( $Page->GetTitle() );?></title>
      <?php print( $Page->GetPreClosingHeadScript() );?>
		</head>
		<body>
		<?php
	}
	
	function DrawPage( $Page )
	{
		print("<div id=\"wrapper\">\n");
		print("<div id=\"header\">\n");
		print( $Page->GetHeader() );
		print("</div>\n"); //header
		?>
		<div id="menu_main">
			<!-- Load the background image, so that there isn't that delay when
			it loads -->
			<img src="images/menu_button_d.png"
				  alt="bg_down" title="bg_down"
				  style="display:none" />
		<p>
		<?php print( $Page->GetNav1() ); ?>
		</div>
		<div id="menu_sub">
		<?php print( $Page->GetNav2() ); ?>
		</div>
		<?php
		$Page->Display_PageCallback();
		print("<div id=\"footer\">\n");
		print('<br/><p>'.$Page->GetFooter().'</p><br/>' );
		print("</div>\n"); //footer
		print("</div>\n"); //wrapper
	}
	
	function EndHTML( $Page )
	{
		print( "</body>\n" );
		print( "</html>\n" );
	}
}

?>