<?php
require('page_base.php');

class CNewsPage extends CPageBase
{
	public function CNewsPage()
	{
		parent::CPageBase('News', true, 0);
	}

	protected function DisplayContent()
	{
		//There are basically two news pages, this list page, and
		//the news article page.

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

		//Get the lowest year in the news archives.
		//First display all the years availeable int he archive.
		$res = $this->DoQuery('select distinct(date_format(dtPosted, \'%Y\')) as dtYear from tblNews order by dtYear desc');
		if($res == true)
		{
			print '<center><p>';
			for($i=0; $i<$res->num_rows; $i++)
			{
				$row = $res->fetch_assoc();
				if($strYear != $row['dtYear'])
				{
					printf('<a href=%s>%s</a>',
						CreateHREF(PAGE_NEWS, 'archive&year='.$row['dtYear']),
						$row['dtYear']);
				}
				else
				{
					print '<b>'.$row['dtYear'].'</b>';
				}
				if($i != $res->num_rows-1)
				{
					print ' | ';
				}
			}
			print '</p></center>';
			$res->free();
		}
	}

	private function DisplayArchive()
	{
		$strYear = isset($_GET['year'])?$_GET['year']:date('Y');
		
		$qry = 'select id,
					date_format(dtPosted, "%M") as dtMonth,
					date_format(dtPosted, "%b %d, %Y") as dt,
					txtTitle
				from tblNews where date_format(dtPosted, "%Y")="'.$strYear.'" order by dtPosted desc';

		$res = $this->DoQuery($qry);

		if(true==$res)
		{
			print '<h2>'.$strYear." News</h2>\n";
			for($i=0; $i<$res->num_rows; $i++)
			{
				$row = $res->fetch_assoc();

				if($strMonth != $row['dtMonth'])
				{
					print '<h4>'.$row['dtMonth']."</h4>\n";
					$strMonth = $row['dtMonth'];
				}

				printf('<p><a href=%s>%s</a></p>',
					CreateHREF(PAGE_NEWS, 'article&id='.$row['id']),
					$row['dt'].' - '.$row['txtTitle']);
			}
			$res->free();
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

		printf("<h2>Article</h2>\n");
		$qry = 'select id,
					date_format(dtPosted, "%M %e, %Y") as dt,
					txtTitle,
					txtBody
				from tblNews where id='.$nID;
		$res = $this->DoQuery($qry);

		if(true==$res)
		{
			$row = $res->fetch_assoc();
			printf("<h3>%s - <i><small>%s</small></i>%s</h3>\n",
					$row['txtTitle'],
					$row['dt'],
					$strEditLink);

			print('<p>');
			print($row['txtBody']);
			print('</p>');
			$res->free();
		}
	}

}
?>
