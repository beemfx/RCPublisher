<?php
/*******************************************************************************
 * File:   clearpage.php
 * Class:  CClearPage
 * Purpose: Temporary page used for clearing the databes.
 *
 * Copyright (C) 2009 Blaine Myers
 ******************************************************************************/
require_once('page_base.php');
require('mysqlex.php');
require_once('php_ex.php');
require('table_news.php');

class CPageHome extends CPageBase {
	public function CPageHome() {
		parent::CPageBase('Home', true, 0);
	}

	protected function DisplayContent()
	{
		?>
		<!-- Left column. -->
		<div class="home_seg" style="width:70%;">
                        <?php	$this->ShowNews(); ?>
			<?php	$this->ShowBlog(); ?>
		</div>
		<!-- Right column -->
		<div class="home_seg" style="width:28%">
               
                        <?php	$this->ShowTwitter(); ?>
			<?php	$this->ShowFeature(); ?>
			<?php //$this->ShowAdsense(); ?>
			
		</div>
		<!--
		<div class="home_seg" style="width:69%;">
			
		</div>
		<div class="home_seg" style="width:29%">
			
		</div>
		-->
		<?php
	}

	private function ShowAdsense()
	{
		?>
		<script type="text/javascript"><!--
			google_ad_client = "pub-0877444337090424";
			/* Double Sidebar */
			google_ad_slot = "8323480437";
			google_ad_width = 120;
			google_ad_height = 240;
			//-->
			</script>
			<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js"></script>
		<?php
	}

	private function ShowNews() 
	{
		$NewsTable = new CTableNews();
		$nNumStories = $this->GetGlobalSetting('nHomeNewsStories');
		$Stories = $NewsTable->ObtainRecentNews($nNumStories);
		print('<div class="news">');
		printf('<h2><a class="tlink" href=%s>News</a></h2>', CreateHREF(PAGE_NEWS, 'archive'));

		for($i = 0; $i < count($Stories); $i++)
		{
			$story = $Stories[$i];
			printf("<h3>%s - <i><small>%s</small></i></h3>\n",
					 $story['title'],
					 $story['date']);

			print('<p>');
			print($story['formatted']);
			print('</p>');
		}
		
		if(0 == $nNumStories)
			echo 'No news.';
		
		print('</div>');

		print '<a class="big_link" href='.CreateHREF(PAGE_NEWS, 'archive').'>more news</a>';
	}

	private function ShowTwitter() 
	{			
		echo '<h2><a class="tlink" href="http://twitter.com/'.$this->GetGlobalSetting('txtTwitterUser').'">On Twitter</a></h2>';
		echo '<ul id="twitter_update_list"></ul>';
		echo '<a class="big_link" href="http://twitter.com/'.$this->GetGlobalSetting('txtTwitterUser').'" id="twitter-link" style="text-align:right;">follow me on Twitter</a>';
	}

	private function ShowBlog() {
		require('B2EvoShowBlog.php');
		ShowBlogEntry();
		print '<a class="big_link" href="http://www.roughconcept.com/blog/">more blog posts</a>';
	}

	private function ShowFeature()
	{
		?>
		A feature goes here.
		<?php
	}

	protected function DisplayPost() 
	{
	echo '<script type="text/javascript" src="http://twitter.com/javascripts/blogger.js"></script>';
	echo '<script type="text/javascript" src="http://twitter.com/statuses/user_timeline/'.$this->GetGlobalSetting('txtTwitterUser').'.json?callback=twitterCallback2&amp;count=5"></script>';
	}
}
?>
