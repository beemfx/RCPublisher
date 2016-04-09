<?php
/*******************************************************************************
 * File:   ListPage.php
 * Class:  CListPage
 * Purpose: Used to format the Rough Concept website, prints header, footer,
 * and associated menues. Should be used by all webpages within the Rough
 * Concept Publisher.
 *
 * Copyright (C) 2009 Blaine Myers
 ******************************************************************************/

//The following sort ids are used:
//1: Sorted by Sort ascending.
//2: Sorted by Sort descending.
//3: Sorted by DateWritten descending.
//4: Sorted by DateWritten ascending.
//5: Author ascending.
//6: Author descending.
//7: Genre ascending.
//8: Genre descending.

require_once('page_base.php');
require_once('mysqlex.php');

class CTOCPage extends CPageBase
{
	public function CTOCPage()
	{
		parent::CPageBase('Table of Contents');
	}
	
	protected function DisplayContent()
	{

		if(!isset($_GET['page']))
		{
			$_GET['page']=1;
		}
		if(!isset($_GET['sort']))
		{
			$_GET['sort']=1;
		}

		printf("<h1>Contents [Page %d]</h1>\n", $_GET['page']);
		print('<br/>');
		print('<p style="margin:0 6em 1em;border:0;padding:0">');
		print('Most of the content is in PDF format. ');
		print('If you are having trouble viewing the content, right click on the title and select "Save Link As..." from your web browers. ');
		print('You will need <a href="http://get.adobe.com/reader/">Acrobat Reader</a> to view these files.');
		print('<br /> ');
		print('</p>');
		if(!isset($this->m_db))
		{
			print("<p>Could not connect to database. Try again later.</p>");
			return;
		}
		print("<div id=\"toc_list\">\n");
		$this->ShowTableStart();
		//Decide which list to show:
		switch($_GET['sort'])
		{
			case 1:
			case 2:
			case 3:
			case 4:
			case 5:
			case 6:
				$nPages = $this->ShowList(intval($_GET['page']), $this->SortTypeToName($_GET['sort']));
				break;
			case 7:
			case 8:
				$nPages = $this->ShowGenreList(intval($_GET['page']), $_GET['sort']);
				break;
			default:
				break;
		}
		$this->ShowTableEnd();
		print("</div>\n");
		//Show page navigation:
		print("<div id=\"page_buttons\">\n");
		$this->ShowListNav($_GET['page'], $nPages);
		print("</div>\n");
	}

	private function SortTypeToName($nSortType)
	{
		//Using the sort query to sort titles is probably not very fast,
		//it might be a much better idea to just add a sort column to the
		//table, and create the sort upon creation, so that it can be sorted
		//faster.
		$aaSortTypes = array(
			1 => 'txtSort asc',
			2 => 'txtSort desc',
			3 => 'txtSortDate desc, txtSort asc',
			4 => 'txtSortDate asc, txtSort desc',
			5 => 'txtAuthorLast asc, txtAuthorFirst asc, txtSort asc',
			6 => 'txtAuthorLast desc, txtAuthorFirst desc, txtSort asc');


		return $aaSortTypes[$nSortType];
	}

	private function ShowGenreList($nPage, $nSort)
	{
		$strSort='';
		if($nSort==7)
			$strSort = 'asc';
		else if($nSort==8)
			$strSort = 'desc';

		//Powerfull query to get all the information needed to sort contents by
		//genre. Because a piece may have multiple genres it can appear more than
		//once in the query, or a piece may not appear at all if it has no genres.
		//This cross join is between three tables.
		$strGenreSort ='SELECT
				tblContentGenres.idGenre,
				tblContent.txtFile as txtDataFile,
				tblContent.id as id, '.
				FULL_TITLE_QUERY.' as txtTitle, '.
				'txtSort,'
				.DATE_QUERY.' as dt,
				tblContent.txtDesc,'
				.AUTHOR_QUERY.' as txtAuthor,
				tblContent.txtFile,
				if(date_sub(now(), INTERVAL 1 MONTH)<=dtUpdated, if(dtUpdated>dtPublished, 2, 1), 0) as bNew
			FROM tblContentGenres, tblGenre, tblContent
			WHERE tblContent.bVisible=1 and tblContentGenres.idGenre = tblGenre.id and tblContentGenres.idContent=tblContent.id
			ORDER BY tblGenre.txtDesc '.$strSort.', txtSort '.$strSort;

		//echo $strGenreSort, "<br/>\n";
		$res=$this->DoQuery($strGenreSort);

		if($res)
		{
			$nLinksPerPage = $this->GetGlobalSetting('nContentPerPage');
			$nCurrentGenre = -1;
			$nStart = intval(($nPage-1)*$nLinksPerPage);
			$nEnd   = intval($nStart + $nLinksPerPage - 1);
			$nPages = intval(($res->num_rows-1)/$nLinksPerPage + 1);

			$res->data_seek($nStart);

			for($i = $nStart; ($i < $res->num_rows) && ($i <= $nEnd); $i++)
			{
				$row=$res->fetch_assoc();
				//When a new genre comes up show a heading for it.
				if($nCurrentGenre != $row['idGenre'])
				{
					print('<tr><td colspan="4"><h2>'.$this->GenreToName($row['idGenre']).'</h2></td></tr>');
					$nCurrentGenre = $row['idGenre'];
				}
				$this->ShowRow($row);

			}
			$res->free();
		}

		return $nPages;
	}

	private function ShowTableStart()
	{

		print("<center><table cellpadding=\"0\" cellspacing=\"0\">\n");
		//The header of tables is buttons that determine the sort,
		//if the list is already sorted in that way, it should switch the sort
		//to the opposite order.
		$strTitleButton = '<a class="sort_link" href='.CreateHREF(PAGE_TOC, 'page=1&sort='.($_GET['sort']==1?2:1)).'>Title</a>';
		$strDateButton  = '<a class="sort_link" href='.CreateHREF(PAGE_TOC, 'page=1&sort='.($_GET['sort']==3?4:3)).'>Produced</a>';
		$strGenreButton = '<a class="sort_link" href='.CreateHREF(PAGE_TOC, 'page=1&sort='.($_GET['sort']==7?8:7)).'>Genre(s)</a>';

		print("<tr>\n");
		print("<th>$strTitleButton</th>\n");
		print("<th>$strDateButton</th>\n");
		//print("<th style=\"display:hidden\">Author</th>\n");
		print("<th>$strGenreButton</th>\n");
		print("</tr>\n");
	}

	private function ShowRow($row)
	{
		print("<tr>\n");

		//Calculate if there is text for new or updated.
		switch($row['bNew'])
		{
			case 0:
				$strNew = '';
				break;
			case 1:
				$strNew = ' <i style="color:red">*new*</i>';
				break;
			case 2:
				$strNew = ' <i style="color:red">*updated*</i>';
				break;
		}

		printf("<td><span style=\"white-space:nowrap\"><a target=\"_blank\" href=%s>%s</a>%s</span></td>\n",
			$row['txtDataFile'],//CreateHREF(PAGE_CONTENT, 'id='.$row['id']),
			$row['txtTitle'],
			$strNew);
		printf("<td><i>%s</i></td>\n", $row['dt']);
		//);
		printf("<td>%s</td>\n", $this->GetGenreList($row['id']));
		
		print("</td>\n");
		print("</tr>\n");

		printf("<tr><td colspan=\"4\"><a class=\"big_link\" style=\"text-align:center;color:#aa0000\" href=%2\$s>Notes and Comments</a><p>%1\$s</p></td></tr>\n",
			$row['txtDesc'],
			CreateHREF(PAGE_CONTENT, 'id='.$row['id']));
	}

	private function ShowTableEnd()
	{
		print("</table></center>\n");
	}

	private function ShowListNav($nPage, $nPages)
	{
		//If not on the first page, show a previous page button.
		if($nPage != 1 && $nPages > 1)
		{
			$this->ShowNavBtn($nPage-1, '<<');
		}

		//Get the page range to be displayed.
		$nRange = $this->FindPageRange($nPage, $nPages, 5);
		//print("<p>The pages range is $nRange[0] to $nRange[1]</p>\n");

		//Show a link for the first page if it is not visible:
		if($nRange[0] != 1)
		{
			$this->ShowNavBtn(1, 1);
			print(' ... ');
		}

		//Show all visible navication buttons:
		for($i = $nRange[0]; $i<=$nRange[1]; $i++)
		{
			$this->ShowNavBtn($i, $i, $i==$nPage);
		}

		//Show a link for the last page if it is not visible:
		if($nRange[1] != $nPages)
		{
			print(' ... ');
			$this->ShowNavBtn($nPages, $nPages);
		}

		//Show the next page if there is one.
		if($nPage != $nPages && $nPages > 1)
		{
			$this->ShowNavBtn($nPage+1, '>>');
		}

		printf("<p>Page %d of %d</p>\n", $nPage, $nPages);
	}



	private function ShowList($nPage, $strSort)
	{

		$strQuery = sprintf(
			'select txtSort,
			%s as txtTitle,
			%s as txtAuthor,
			txtFile as txtDataFile,
			id,
			%s as dt,
			txtDesc,
			if(date_sub(now(), INTERVAL 1 MONTH)<=dtUpdated, if(dtUpdated>dtPublished, 2, 1), 0) as bNew
			from tblContent where bVisible=1 order by %s',
			FULL_TITLE_QUERY,
			AUTHOR_QUERY,
			DATE_QUERY,
			$strSort);

		$res = $this->DoQuery($strQuery);
		
		if(true == $res)
		{
			$nLinksPerPage = $this->GetGlobalSetting('nContentPerPage');
			$nStart = intval(($nPage-1)*$nLinksPerPage);
			$nEnd   = intval($nStart + $nLinksPerPage - 1);
			$nPages = intval(($res->num_rows-1)/$nLinksPerPage + 1);

			$res->data_seek($nStart);

			// printf("<p>%d results:</p>", $result->num_rows);
			for($i = $nStart; ($i < $res->num_rows) && ($i <= $nEnd); $i++)
			{
				$row = $res->fetch_assoc();
				$this->ShowRow($row);
			}
			$res->free();
		}

		return $nPages;
	}

	private function ShowNavBtn($nPage, $strName, $bActive=false)
	{
		print('<a'.($bActive?' class="current_page" ':'').' ');
		print('href=');
		print CreateHREF(PAGE_TOC, sprintf('page=%d&sort=%d', $nPage, $_GET['sort']));
		print('>'.$strName.'</a>');
	}

	private static function FindPageRange($nPage, $nPages, $nPadding=5)
	{
		//If there are less pages than padding just return the full range
		//of pages:
		if($nPages < $nPadding*2+1)
		{
			return array(1, $nPages);
		}
		//If the page is lower than the padding range, just start with the
		//first page, and end with whatever page is reached.
		else if(($nPage-$nPadding)<1)
		{
			return array(1, min($nPages, $nPadding*2+1));
		}
		//If the page is greater than the end and the padding just end with the
		//last page, and fill in the padding from there.
		else if(($nPage+$nPadding)>$nPages)
		{
			return array($nPages-$nPadding*2, $nPages);
		}
		//Finally the pages fall in the middle, so just return the desired range:
		else
		{
			return array($nPage-$nPadding, $nPage+$nPadding);
		}


		//Find where we want the range of pages to be.
		//Firs just rawly decide the page numbers by padding before and padding
		//after, for padding*2+1 pages total:
		$nStart = $nPage - $nPadding;
		//Now if the start is less than 1, we need to move the start
		//page to 0.
		$nStart = max(1, $nStart);
		//The end should be ten entries later.
		$nEnd   = $nStart+$nPadding*2;
		if($nEnd > $nPages)
		{
			$nDiff   = ($nEnd-$nPages);
			$nEnd   -= $nDiff;
			$nStart -= $nDiff;
		}
		$nStart = max(1, $nStart);
		$nEnd = max($nEnd, $nStart);
		//$nStart = max(1, $nStart);
		//$nStart = min($nStart, $nPages);
		//$nEnd = min($nEnd, $nPages);
		/*
		$nEnd = min($nEnd, $nPages);
		$nEnd = max($nEnd, 1);
		*/

		return array(20, 30);
	}

	private function GetGenreList($nPieceID)
	{
		//Do a query that gets the name of the genres assigned to the content
		//in alphabetical order.
		$qry = 'select tblGenre.txtDesc as txtGDesc from tblGenre, tblContentGenres';
		$qry.= ' where tblGenre.id = tblContentGenres.idGenre';
		$qry.= ' and tblContentGenres.idContent='.$nPieceID.' and tblGenre.bShowInList=1 order by tblGenre.txtDesc';
		$res = $this->DoQuery($qry);
		if(true == $res)
		{
			//Shoul make each of these into a link.
			for($i = 0; $i < $res->num_rows; $i++)
			{
				$row = $res->fetch_assoc();
				$strOut .= '<span style="white-space:nowrap">'.$row['txtGDesc'];
				if($i < ($res->num_rows-1))
					$strOut .= ', ';
				$strOut.= '</span> ';
			}

			$res->free();
		}
		return $strOut;
	}
}
?>
