<?php
//Very basic:
class RCSkin implements ISkin
{

	function GetId()
	{
		return 'Beem Software Skin';
	}

	function BeginHTML( $Page )
	{
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
		//echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
		//echo '<!DOCTYPE HTML>';
		?>
		<html>
			<head>
				<link rel="stylesheet" type="text/css" href="skins/beemmedia/style.css"/>
				<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
				<title><?php print( $Page->GetTitle() ); ?></title>
				<?php print( $Page->GetPreClosingHeadScript() ); ?>
			</head>
			<body>
				<?php
			}

			function DrawPage( $Page )
			{
				print("<div id=\"rc_wrapper\">\n" );
				print("<div id=\"rc_header\">\n" );
				print( $Page->GetHeader() );
				print("</div>\n" ); //rc_header
				?>
				<div id="rc_menu_main">
						<?php print( $Page->GetNav1() ); ?>
				</div>
				<div id="rc_menu_sub">
					<?php print( $Page->GetNav2() ); ?>
				</div>
				<?php
				print( $Page->GetErrorText() );
				$Page->Display_PageCallback();
				print("\n<div id=\"rc_footer\">\n" );
				print('<br/><p>'.$Page->GetFooter().'</p><br/>' );
				print("\n</div> <!-- div#rc_footer -->\n" );
				print("\n</div> <!-- div#rc_wrapper -->\n" );
			}

			function EndHTML( $Page )
			{
				print( "\n</body>\n" );
				print( "\n</html>\n" );
			}

		}
		?>