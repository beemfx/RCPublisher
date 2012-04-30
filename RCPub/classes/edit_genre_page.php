<?php
/*******************************************************************************
 * File:   editgenrepage.php
 * Class:  CEditGenrePage
 * Purpose: Page used to edit the genre list.
 *
 * Copyright (C) 2009 Blaine Myers
 ******************************************************************************/
require_once('page_base.php');

define('MAX_NEW_GENRES', 5);

class CEditGenrePage extends CPageBase
{
	public function CEditGenrePage()
	{
		parent::CPageBase('Edit Genres', true, 5);
	}

	protected function DisplayContent()
	{
		print("<h1>Edit Genres</h1>\n");

		if($_POST['stage']==0)
		{
			$this->DisplayStage0();
		}
		else if($_POST['stage']==1)
		{
			$this->DisplayStage1();
			//Show stage 0 again as well in case more genres want to be edited.
			$this->DisplayStage0();
		}
	}

	private function DisplayStage0()
	{
		$res = $this->DoQuery('select * from tblGenre order by txtDesc');

		if(!$res)
			return;

		//First show a list that allows genres to be deleted.
		if($res->num_rows>0)
		{
			print("<h3>Genres</h3>\n");

			print("<form action=".CreateHREF(PAGE_EDITG)." method=\"post\">\n");
			print("<input type=\"hidden\" name=\"stage\" value=\"1\"/>");
			for($i=0; $i<$res->num_rows; $i++)
			{
				$row = $res->fetch_assoc();
				//Write the checkbox, one on each line,
				//also check it if it was previously checked.

				$strName = 'genre_'.$row['id'];

				printf(
					"<input class=\"checkbox\" type=\"checkbox\" name=\"%s\">%s</input><br/>\n",
					$strName,
					$row['txtDesc']
					);
			}
			print("<input class=\"button\" type=\"submit\" name=\"Delete\" value=\"Delete Selected\"/>");
			print("</form>\n");
		}

		$res->free();

		//Now do another form that allows the addition of new genres.
		print("<h3>New Genres</h3>\n");
		print("<form action=".CreateHREF(PAGE_EDITG)." method=\"post\">\n");
		print("<input type=\"hidden\" name=\"stage\" value=\"1\"/>");
		
		for($i=0; $i<MAX_NEW_GENRES; $i++)
		{
			printf("<p>Description: <input style = \"width:25%%\" type=\"text\" name=\"newg_%s\" /></p>", ($i+1));
		}
		print("<input class=\"button\" type=\"submit\" name=\"Insert\" value=\"Insert\"/>");
		print("</form>\n");
	}

	private function DisplayStage1()
	{
		if(isset($_POST['Delete']))
		{
			$this->DoDelete();
		}
		else if(isset($_POST['Insert']))
		{
			$this->DoInsert();
		}
	}

	private function DoDelete()
	{
		//Now insert all the genre's.
		foreach($_POST as $key => $value)
		{
			if(!strncmp('genre_', $key, 6) && $value==='on')
			{
				$this->DoQuery('delete from tblContentGenres where idGenre='.substr($key, 6));
				$this->DoQuery('delete from tblGenre where id='.substr($key, 6));
			}
		}
	}

	private function DoInsert()
	{
		foreach($_POST as $key => $value)
		{
			if(!strncmp('newg_', $key, 5) && strlen($value)>0)
			{
				$this->DoQuery('insert into tblGenre (txtDesc) value ("'.$value.'")');
			}
		}
	}

}
?>
