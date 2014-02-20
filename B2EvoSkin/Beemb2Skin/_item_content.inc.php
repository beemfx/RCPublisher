<?php
/**
 * This is the template that displays the contents for a post
 * (images, teaser, more link, body, etc...)
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template (or other templates)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $disp_detail;

// Default params:
$params = array_merge( array(
	 'content_mode'        => 'auto',	// Can be 'excerpt', 'normal' or 'full'. 'auto' will auto select depending on backoffice SEO settings for $disp-detail
	 'intro_mode'          => 'auto',	// same as above. This will typically be forced to "normal" when displaying an intro section so that intro posts always display as normal there
	 'force_more'          => false,		// This will be set to true id 'content_mode' resolves to 'full'.
	 'content_start_excerpt' => '<div class="content_excerpt">',
	 'content_end_excerpt' => '</div>',
	 'content_start_full'  => '<div class="content_full">',
	 'content_end_full'    => '</div>',
	 'before_images'       => '<div class="bImages">',
	 'before_image'        => '<div class="image_block">',
	 'before_image_legend' => '<div class="image_legend">',
	 'after_image_legend'  => '</div>',
	 'after_image'         => '</div>',
	 'after_images'        => '</div>',
	 'image_size'          => 'fit-400x320',
	 'excerpt_image_size'  => 'fit-80x80',
	 'before_url_link'     => '<p class="post_link">'.T_('Link:').' ',
	 'after_url_link'      => '</p>',
	 'url_link_text_template' => '$url$',
	 'before_more_link'    => '<p class="bMore">',
	 'after_more_link'     => '</p>',
	 'more_link_text'      => '#',
	 'excerpt_before_text' => '<div class="excerpt">',
	 'excerpt_after_text'  => '</div>',
	 'excerpt_before_more' => ' <span class="excerpt_more">',
	 'excerpt_after_more'  => '</span>',
	 'excerpt_more_text'   => T_('more').' &raquo;',
	 // fp> todo: rename 'files' to 'attach' (as in attachments)
	 'limit_files'         => 1000,
	 'file_list_start'     => '<div class="attchments"><h3>'.T_('Attachments').':</h3><ul>',
	 'file_list_end'       => '</ul></div>',
	 'file_start'          => '<li>',
	 'file_end'            => '</li>',
	 'before_file_size'    => ' <span class="file_size">',
	 'after_file_size'     => '</span>',
	 ), $params );


// Determine content mode to use..
if( $Item->is_intro() ) {
	$content_mode = $params['intro_mode'];
}
else {
	$content_mode = $params['content_mode'];
}
if( $content_mode == 'auto' ) {
// echo $disp_detail;
	switch( $disp_detail ) {
		case 'posts-cat':
			$content_mode = $Blog->get_setting('chapter_content');
			break;

		case 'posts-tag':
			$content_mode = $Blog->get_setting('tag_content');
			break;

		case 'posts-date':
			$content_mode = $Blog->get_setting('archive_content');
			break;

		case 'posts-filtered':
			$content_mode = $Blog->get_setting('filtered_content');
			break;

		case 'posts-default':  // home page 1
		case 'posts-next':		 // next page 2, 3, etc
		default:
			$content_mode = $Blog->get_setting('main_content');
	}
}

// echo $content_mode;

switch( $content_mode ) {
	case 'excerpt':
	// Reduced display:
		echo $params['content_start_excerpt'];

		if( !empty($params['excerpt_image_size']) ) {
		// Display images that are linked to this post:
			$Item->images( array(
				 'before' =>              $params['before_images'],
				 'before_image' =>        $params['before_image'],
				 'before_image_legend' => $params['before_image_legend'],
				 'after_image_legend' =>  $params['after_image_legend'],
				 'after_image' =>         $params['after_image_legend'],
				 'after' =>               $params['after_images'],
				 'image_size' =>					 $params['excerpt_image_size'],
				 'image_link_to' =>       'single',
				 ) );
		}

		$Item->excerpt( array(
			 'before'              => $params['excerpt_before_text'],
			 'after'               => $params['excerpt_after_text'],
			 'excerpt_before_more' => $params['excerpt_before_more'],
			 'excerpt_after_more'  => $params['excerpt_after_more'],
			 'excerpt_more_text'   => $params['excerpt_more_text'],
			 ) );

		echo $params['content_end_excerpt'];
		break;

	case 'full':
		$params['force_more'] = true;
		/* continue down */
	case 'normal':
	default:
	// Full dislpay:
		echo $params['content_start_full'];

		// Increment view count of first post on page:
		$Item->count_view( array(
			 'allow_multiple_counts_per_page' => false,
			 ) );

		if( !empty($params['image_size']) ) {
		// Display images that are linked to this post:
			$Item->images( array(
				 'before' =>              $params['before_images'],
				 'before_image' =>        $params['before_image'],
				 'before_image_legend' => $params['before_image_legend'],
				 'after_image_legend' =>  $params['after_image_legend'],
				 'after_image' =>         $params['after_image_legend'],
				 'after' =>               $params['after_images'],
				 'image_size' =>          $params['image_size'],
				 ) );
		}

		echo('<div class="b2EvoContent">');

		// URL link, if the post has one:
		$Item->url_link( array(
			 'before'        => $params['before_url_link'],
			 'after'         => $params['after_url_link'],
			 'text_template' => $params['url_link_text_template'],
			 'url_template'  => '$url$',
			 'target'        => '',
			 'podcast'       => '#',        // auto display mp3 player if post type is podcast (=> false, to disable)
			 ) );

		// Display CONTENT:
		$Item->content_teaser( array(
			 'before'      => '',
			 'after'       => '',
			 ) );
		$Item->more_link( array(
			 'force_more'  => $params['force_more'],
			 'before'      => $params['before_more_link'],
			 'after'       => $params['after_more_link'],
			 'link_text'   => $params['more_link_text'],
			 ) );
		$Item->content_extension( array(
			 'before'      => '',
			 'after'       => '',
			 'force_more'  => $params['force_more'],
			 ) );

		// Links to post pages (for multipage posts):
		$Item->page_links( '<p class="right">'.T_('Pages:').' ', '</p>', ' &middot; ' );

		// Display Item footer text (text can be edited in Blog Settings):
		$Item->footer( array(
			 'mode'        => '#',				// Will detect 'single' from $disp automatically
			 'block_start' => '<div class="item_footer">',
			 'block_end'   => '</div>',
			 ) );

		echo('</div>');


		if( !empty($params['limit_files']) ) {	// Display attachments/files that are linked to this post:
			$Item->files( array(
				 'before' =>              $params['file_list_start'],
				 'before_file' =>         $params['file_start'],
				 'before_file_size' =>    $params['before_file_size'],
				 'after_file_size' =>     $params['after_file_size'],
				 'after_file' =>          $params['file_end'],
				 'after' =>               $params['file_list_end'],
				 'limit_files' =>         $params['limit_files'],
				 ) );
		}

		echo $params['content_end_full'];

}
/*
 * $Log: _item_content.inc.php,v $
 */
?>