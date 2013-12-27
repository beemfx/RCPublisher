<?php

function b2evoPlug_ShowBlogEntry()
{
	@ $db = new mysqli( RCSettings_GetSetting( 'txtB2Host' ) , RCSettings_GetSetting( 'txtB2User' ) , RCSettings_GetSetting( 'txtB2Pwd' ) , RCSettings_GetSetting( 'txtB2Db' ) );

	if( mysqli_connect_errno() )
	{
		unset( $db );
		print 'A problem occurred while connecting to the B2Evo Blog';
		return;
	}


	//Just want to get the most recent entry:
	$qry = 'select post_title, post_ID, date_format(post_datestart, "%M %e, %Y") as dt from evo_items__item where post_status="published" order by post_datestart desc limit 1';

	$res = $db->query( $qry );

	if( $res == true )
	{
		$row = $res->fetch_assoc();
		$sBlogURL = RCSettings_GetSetting( 'txtBlogLink' );
		$sBlogURL = preg_replace( '/{{slug}}/' , '' , $sBlogURL );
		printf( '<h2><a href="%s">Latest Blog: %s</a> <i><span style="font-size:80%%;white-space:nowrap">- %s</span></i></h2>' , $sBlogURL , $row[ 'post_title' ] , $row[ 'dt' ] );

		//Okay, now lets get the prerendered version of the post:
		$res2 = $db->query( 'select itpr_content_prerendered as blog_text from evo_items__prerendering where itpr_format="htmlbody" and itpr_itm_ID='.$row[ 'post_ID' ] );
		if( $res2 == true )
		{
			$row = $res2->fetch_assoc();
			$strContent = $row[ 'blog_text' ];
			$res2->free();
		}
		else
		{
			$strContent = 'Error: Couldn\'t find prerendered text for '.$row[ 'post_title' ];
		}

		print '<div class="b2EvoContent">';
		print $strContent;
		print '</div>';

		$res->free();
	}
	else
	{
		print 'Could not get blog entry.<br/>';
		print $db->error.'<br/>';
	}


	return;
}

?>
