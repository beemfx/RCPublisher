<?php

function ShowBlogEntry() {
	$cf = array(
		 'user'     => 'rcblg',     // your MySQL username
		 'password' => 'Pub#435',     // ...and password
		 'host'     => 'p50mysql191.secureserver.net',    // MySQL Server (typically 'localhost')
	);

	@ $db = new mysqli($cf['host'], $cf['user'], $cf['password'], $cf['user']);

	if(mysqli_connect_errno()) {
		unset($db);
		print 'A problem occurred while connecting to the B2Evo Blog';
		return;
	}


	//Just want to get the most recent entry:
	$qry = 'select post_title, post_ID, date_format(post_datestart, "%M %e, %Y") as dt from evo_items__item where post_status="published" order by post_datestart desc limit 1';

	$res = $db->query($qry);

	if($res==true) {

		$row = $res->fetch_assoc();
		printf('<h2><a class="tlink" href="http://www.roughconcept.com/blog">Latest Blog: %s</a> <i><span style="font-size:80%%;white-space:nowrap">- %s</span></i></h2>', $row['post_title'], $row['dt']);

		//Okay, now lets get the prerendered version of the post:
		$res2 = $db->query('select itpr_content_prerendered as blog_text from evo_items__prerendering where itpr_format="htmlbody" and itpr_itm_ID='.$row['post_ID']);
		if($res2 == true)
		{
			$row = $res2->fetch_assoc();
			$strContent = $row['blog_text'];
			$res2->free();
		}
		else
		{
			$strContent = 'Error: Couldn\'t find prerendered text for '.$row['post_title'];
		}

		echo "\n<div class=\"b2evo\">\n";
		print $strContent;
		echo "\n</div>\n";

		$res->free();
	}
	else {
		print 'Could not get blog entry.<br/>';
		print $db->error.'<br/>';
	}


	return;
}
?>
