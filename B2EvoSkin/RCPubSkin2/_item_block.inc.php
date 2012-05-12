
<?php

if (!defined('EVO_MAIN_INIT'))
    die('Please, do not access this page directly.');


global $Item;

//This has something to do with locales. Good for multilingual or something.
$Item->locale_temp_switch();

//Create a DIV for the item block.
echo "\n<div class=\"rc_item_block\">\n";
echo "\n<div class=\"rc_item_title\">\n";

//We'll start with the title and date of the blog in header
echo "\n<h1>";
$Item->title();
$Item->issue_date(
	array('date_format' => 'F j, Y',
	    'before' => ' <i><span style="font-size:80%;white-space:nowrap">- ',
	    'after' => '</span></i>'));
echo "</h1>\n";
echo "\n</div>\n";

// Display CONTENT:
echo "\n<div class=\"rc_item_content\">\n";
$Item->content_teaser(array(
    'before' => '',
    'after' => '',
));
$Item->more_link(array(
    'force_more' => '',
    'before' => '',
    'after' => '',
    'link_text' => '',
));
$Item->content_extension(array(
    'before' => '',
    'after' => '',
    'force_more' => '',
));
echo "\n</div><!-- rc_item_content -->\n";

echo "\n<div class=\"rc_item_footer\">\n";

$Item->permanent_link(array(
    'before' => '',
    'after' => '',
    'text' => '#',
    'title' => '#',
    'class' => ''
));

$Item->categories(array(
    'before' => ' ',
    'after' => ' ',
    'include_main' => true,
    'include_other' => true,
    'include_external' => true,
    'before_main' => ', categories: ',
    'after_main' => '',
    'before_other' => ', ',
    'after_other' => '',
    'before_external' => '',
    'after_external' => '',
    'separator' => ', ',
    'link_categories' => true,
    'link_title' => '#',
    'format' => 'htmlbody',
));


$Item->edit_link(array(
    'before' => ' ',
    'after' => ' ',
    'text' => '#',
    'title' => '#',
    'class' => '',
    'save_context' => true
));


echo "<br />";

// Link to comments, trackbacks, etc.:
$Item->feedback_link(array(
    'type' => 'comments',
    'link_before' => '',
    'link_after' => ' ',
    'link_text_zero' => '#',
    'link_text_one' => '#',
    'link_text_more' => '#',
    'link_title' => '#',
    'use_popup' => false,
));

// Link to comments, trackbacks, etc.:
$Item->feedback_link(array(
    'type' => 'trackbacks',
    'link_before' => '',
    'link_after' => ' ',
    'link_text_zero' => '#',
    'link_text_one' => '#',
    'link_text_more' => '#',
    'link_title' => '#',
    'use_popup' => false,
));


skin_include('_item_feedback.inc.php', array(
    'before_section_title' => '',
    'after_section_title' => '',
));

echo "\n</div><!-- rc_item_footer -->\n";

locale_restore_previous(); // Restore previous locale (Blog locale)

echo "\n</div><!-- rc_item_block -->\n";
?>