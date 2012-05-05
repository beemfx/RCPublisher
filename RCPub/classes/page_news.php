<?php
require('page_base.php');
require('table_news.php');

class CNewsPage extends CPageBase
{
	private $m_NewsTable;
	
	public function CNewsPage()
	{
		parent::CPageBase('News', true, 0);
		
		$this->m_NewsTable = null;
	}

	protected function DisplayContent()
	{
		//There are basically two news pages, this list page, and
		//the news article page.
		$this->m_NewsTable = new CTableNews();

		print('<div class="news">');
		$this->DisplayYearList();

		if(isset($_GET['archive']))
		{
			$this->DisplayArchive();
		}
		else if(isset($_GET['article']))
		{
			$this->DisplayArticle($_GET['id']);
		}

		print('</div>');
	}

	private function DisplayYearList()
	{
		print '<h1>News Archives</h1>'."\n";
		if(isset($_GET['archive']))
		{
			$strYear = isset($_GET['year'])?$_GET['year']:date('Y');
		}
		
		$years = $this->m_NewsTable->GetYears();
		

		print '<center><p>';
		for($i=0; $i<count($years); $i++)
		{
			if($strYear != $years[$i])
			{
				printf('<a href=%s>%s</a>',
					CreateHREF(PAGE_NEWS, 'archive&year='.$years[$i]),
					$years[$i]);
			}
			else
			{
				print '<b>'.$years[$i].'</b>';
			}
			if($i != count($years)-1)
			{
				print ' | ';
			}
		}
		print '</p></center>';
	}

	private function DisplayArchive()
	{
		$strYear = isset($_GET['year'])?$_GET['year']:date('Y');
		
		$archive = $this->m_NewsTable->GetArchiveByYear($strYear);
		print '<h2>'.$strYear." News</h2>\n";
		for($i=0; $i<count($archive); $i++)
		{
			$row = $archive[$i];
			
			if($strMonth != $row['dtMonth'])
			{
				print '<h4>'.$row['dtMonth']."</h4>\n";
				$strMonth = $row['dtMonth'];
			}

			printf('<p><a href=%s>%s</a></p>',
				CreateHREF(PAGE_NEWS, 'article&id='.$row['id']),
				$row['dt'].' - '.$row['txtTitle']);
		}
	}

	private function DisplayArticle($nID)
	{
		if($_SESSION['user_level']>0)
		{
			$strEditLink = sprintf(
				' [<a href=%s>Edit</a>]',
				CreateHREF(PAGE_POSTNEWS, 'mode=edit&id='.$nID));
		}
		else
		{
			$strEditLink='';
		}
			
		$story = $this->m_NewsTable->GetStory((int)$nID);


		if(null != $story)
		{
			printf("<h3>%s - <i><small>%s</small></i>%s</h3>\n",
					$story['txtTitle'],
					$story['dt'],
					$strEditLink);

			print('<p>');
			print($story['formatted']);
			print('</p>');
		}
	}

}
?>
