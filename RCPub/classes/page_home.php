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
		$NewsTable = new CTableNews($this->m_db);
		$nNumStories = $this->GetGlobalSetting('nHomeNewsStories');
		echo 'There should be '.$nNumStories.' stories.';
		$nNumStories = $NewsTable->ObtainRecentNews($nNumStories);
		echo 'There are '.$nNumStories.' stories.';
		print('<div class="news">');
		printf('<h2><a class="tlink" href=%s>News</a></h2>', CreateHREF(PAGE_NEWS, 'archive'));

		for($i = 0; $i < $nNumStories; $i++)
		{
			$story = $NewsTable->GetRecentNewsStory($i);
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

	private function ShowTwitter() {
		?>
		<h2><a class="tlink" href="http://twitter.com/RoughConcept">On Twitter</a></h2>
		<ul id="twitter_update_list"></ul>
		<a class="big_link" href="http://twitter.com/RoughConcept" id="twitter-link" style="text-align:right;">follow me on Twitter</a>
		<?php
	}

	private function ShowBlog() {
		require('B2EvoShowBlog.php');
		ShowBlogEntry();
		print '<a class="big_link" href="http://www.roughconcept.com/blog/">more blog posts</a>';
	}

	private function ShowFeature() {
	//Will bring back the featured content once some real content has been uploaded.
	//Only want the first volume of a series, or any content that doesn't have a volume.
		$res = $this->DoQuery('select id, '.FULL_TITLE_QUERY.' as txtFullTitle, txtDesc, txtFile from tblContent where (fVolume is null or fVolume = 1) and bVisible=1');
		if($res==true) {
			$day = getdate();
			srand($day['yday']);
			$res->data_seek(rand(0, $res->num_rows));
			$row = $res->fetch_assoc();
			print("<h2><a class=\"tlink\" href=".CreateHREF(PAGE_TOC).">Daily Feature</a></h2>\n");
			print("<h4><a target=\"_blank\" style=\"color:black;font-weight:bold\" href="./*CreateHREF(PAGE_CONTENT, "id=".$row['id'])*/$row['txtFile'].">".$row['txtFullTitle']."</a></h4>\n");
			print("<p>".neat_trim($row['txtDesc'], 550)."</p>\n");

			$res->free();
		}

		print '<a class="big_link" href='.CreateHREF(PAGE_CONTENT, 'id='.$row['id']).'>notes and comments</a>';
		print '<a class="big_link" target=\"_blank\" href='./*CreateHREF(PAGE_CONTENT, 'id='.$row['id'])*/$row['txtFile'].'>read it</a>';
	}

	protected function DisplayPost() {
		?>
<script type="text/javascript" src="http://twitter.com/javascripts/blogger.js"></script>
<script type="text/javascript" src="http://twitter.com/statuses/user_timeline/RoughConcept.json?callback=twitterCallback2&amp;count=5"></script>
	<?php
	}
}
?>
