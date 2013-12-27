<?php
/* * *****************************************************************************
 * File:   page_home.php
 * Class:  CPageHome
 * Purpose: The landing page (to be dropped).
 *
 * Copyright (C) 2009 Blaine Myers
 * **************************************************************************** */
require_once('page_base.php');
require_once('table_page.php');
require('table_news.php');
class CPageHome extends CPageBase
{

	public function CPageHome()
	{
		parent::CPageBase( 'Home' );
	}

	protected function IsPageAllowed()
	{
		return true;
	}
	
	protected function GetContentHeader()
	{
		return null;
	}

	protected function DisplayContent()
	{
		?>
		<!-- Left column. -->
		<div class="rc_column" style="width:70%;">
			<?php $this->ShowNews(); ?>
			<?php $this->ShowBlog(); ?>
		</div>
		<!-- Right column -->
		<div class="rc_column" style="width:28%">

			<?php $this->ShowSidebar(); ?>
			<?php $this->ShowFeature(); ?>
			<?php //$this->ShowAdsense(); ?>		
		</div>
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
		$nNumStories = $this->GetGlobalSetting( 'nHomeNewsStories' );
		
		print('<div class="news">' );
		printf( '<h2><a href=%s>News</a></h2>' , CreateHREF( PAGE_NEWS , 'archive' ) );

		$NewsTable->DisplayRecentArticles( $nNumStories );

		print('</div>' );

		print '<a class="big_link" href='.CreateHREF( PAGE_NEWS , 'archive' ).'>more news</a>';
	}

	private function ShowSidebar()
	{
		echo $this->GetGlobalSetting( 'txtSidebarHTML' );
	}

	private function ShowBlog()
	{
		require('plugins/b2evoplug/plugin.php');
		b2evoPlug_ShowBlogEntry();
	}

	private function ShowFeature()
	{
		$FeatureSlug = $this->GetGlobalSetting( 'txtFeatureSlug' );
		$PageTable = new CTablePage();
		if( $PageTable->IsSlugTaken( $FeatureSlug ) )
		{
			$Page = $PageTable->GetPage( $FeatureSlug );
			printf( "<h2>%s</h2>\n" , $Page[ 'title' ] );
			print($Page[ 'formatted' ] );
		}
	}

	protected function DisplayPost()
	{
		
	}

}
?>
