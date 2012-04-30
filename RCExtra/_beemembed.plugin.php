<?php
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


class beemembed_plugin extends Plugin {
	var $code = 'beem_embed1983';
	var $name = 'Beem Embed';
	var $priority = 5;
	var $apply_rendering = 'opt-in';
	var $group = 'rendering';
	var $help_url = 'http://www.beemsoft.com';
	var $short_desc;
	var $long_desc;
	var $version = '1.00';
	var $number_of_installs = 1;


	/**
	 * Init
	 */
	function PluginInit( & $params ) {
		$this->short_desc = T_('Embeds one blog into another.');
		$this->long_desc = T_('<p>Usage: [[embed blog-slug]]</p>');

	}

	function GetDefaultSettings( & $params ) {
		return array();
	}

	function RenderItemAsHtml( & $params ) {
		$content = & $params['data'];

		$content = preg_replace_callback('/\[\[embed\s*([^\]\[]*)\]\]/', array( $this, 'EmbedCallback' ), $content );

	}

	function EmbedCallback( $matches ) {
		$slug = $matches[1];
		return $this->GetPostContent($slug);
	}



	function GetPostContent($slug) {
		global $MainList;
		global $BlogCache, $Blog;
		global $timestamp_min, $timestamp_max;

		$ItemList = & new ItemList2( $Blog, $timestamp_min, $timestamp_max, 1, 'ItemCache');

		$filters = array();
		$filters['post_title'] = $slug;

		$ItemList->set_filters( $filters, false ); // we don't want to memorize these params

		// Run the query:
		$ItemList->query();

		if( ! $ItemList->result_num_rows ) {	// Nothing to display:
			return 'EMBED WARNING('.$slug.' post was not found.)';
		}


		//We should have only recovered one item, so we don't need to loop
		//through the entire list.
		$Item = & $ItemList->get_item();
		$content = $Item->get_content_teaser(1, false, 'htmlbody');

		return $content;
	}
	
}

?>
