<?php
/*******************************************************************************
 * File:   contentpage.php
 * Class:  CContentPage
 * Purpose: Shows the content for a particular posted item.
 * Access: 0 (All).
 *
 * Copyright (C) 2009 Blaine Myers
 ******************************************************************************/
require_once('page_base.php');
require_once('mysqlex.php');

class CContentPage extends CPageBase
{
	public function CContentPage()
	{
		//May want to modify this constructor to show the actual
		//title of the peice.
		parent::CPageBase('Content', true, 0);
	}

	protected function DisplayContent()
	{
		$nID = $_GET['id'];

		if($nID==0)
		{
			$this->ShowWarning('No content page was specified.');
			return;
		}

		if(isset($_POST['stage']))
		{
			$this->PostNewComment();
		}

		$this->ShowContent($nID);
		$this->ShowComments();
		$this->ShowCommentBox();
		
	}

	private function PostNewComment()
	{
		$strN = $_POST['name'];
		$strE = $_POST['email'];
		$strC = $_POST['comment'];

		if(strlen($strN)<1)
		{
			$this->ShowWarning("Comments require a name or alias.");
			return;
		}


		if(strlen($strC)<1)
		{
			$this->ShowWarning("A comment must be present to be posted.");
			return;
		}

		//Set the email address to either null or put quotes around it.
		if(strlen($strE)<1)
		{
			$strE = 'null';
		}
		else
		{
			$strE = sprintf('"%s"', $strE);
		}

		$qry = sprintf('insert into tblComment
		(idContent, idUser, txtName, txtEmail, txtComment, dtPosted, bApproved)
		values(%s,   %s,    "%s",    %s,      "%s",      now(),    %s)',
		$_GET['id'], 'null', $strN, $strE, $strC, 'true');
		$this->DoQuery($qry);
	}
	
	private function ShowContent($nID)
	{
		$strFullTitle = FULL_TITLE_QUERY;

		//Run a query to get all the desired information:
		$strQ = sprintf('select %s as txtTitle, %s as txtAuthor, txtFile, %s as dtP, txtDesc, txtFileType from tblContent where id=%d',
			FULL_TITLE_QUERY,
			AUTHOR_QUERY,
			DATE_QUERY,
			$nID);

		$res = $this->DoQuery($strQ);

		if($res == true)
		{
			$row = $res->fetch_assoc();
			$strEditLink = CreateHREF(PAGE_EDITC, 'id='.$_GET['id']);
			printf("<h1>%s <small>- <i>%s</i></small>%s</h1>\n",
				$row['txtTitle'],
				$row['dtP'],
				$_SESSION['user_level']>0?"[<a href =$strEditLink>edit</a>]":'');
			print('<p style="margin:1em 2em">'.$row['txtDesc'].'</p>');
			printf('<p style="margin:1em;text-align:right">%s</p>', $row['txtAuthor']);
			print("<p style=\"text-align:center;margin:1em 0\"><a href=\"#comment_box_anchor\">Comment on this item</a>.</p>\n");
			print('<div style="margin:0 30%;border:2px solid black;padding:0;text-align:center">');
			printf('<a href="%s" target="_blank">Open %s in a new window</a>.', $row['txtFile'], $row['txtTitle']);
			print('<br />(Or right click the above link and select "save link as" to copy the content to your hard drive. Note that all downloads are copyright and for personal use only. They may not be modified or distributed without written consent from the author.)');
			print("</div>\n");


			/*
			print('<div id="content-imbed" style="text-align:center;margin:2em;border:2px solid black" >');

			$this->ShowAsObject($row['txtFile'], 800, $row['txtFileType']);

			print("</div>\n");
			*/
			$res->free();
		}	
	}

	private function ShowAsObject($strFile, $nHeight, $strType)
	{
		if($strType=='application/pdf' && 1)
		{
			$strFile.='#toolbar=1&navpanes=0&scrollbar=1&page=1';
		}

		printf("<object data=\"%s\" height=\"%s\" width=\"100%%\" type=\"%s\">\n", $strFile, $nHeight, $row['txtFileType']);
		printf("<p>Your browser does not support pdf files, <a href=\"%s\" target=\"_blank\">use this link to download the content</a>.</p>\n", $row['txtFile']);
		print("</object>\n");
	}

	private function ShowAsEmbed($strFile, $nHeight, $strType)
	{
		printf('<embed src="%s" width="100%%" height="%d"/>',$strData, $nHeight);
	}

	private function ShowAsIFrame($strFile, $nHeight, $strType)
	{
		printf('<iframe src="%s" style="width:100%%;height:%dpx"></iframe>',
					$strData,
					$nHeight);
	}

	private function ShowAsGoogleDoc($strFile, $nHeight, $strType)
	{
		?>

		<?php
		printf('<iframe src="http://docs.google.com/gview?url=%s&embed=true" style="width:100%%; height:%dpx;” frameborder="0"></iframe>',
					'http://www.roughconcept.com/rc/'.$strFile,
					$nHeight);
	}

	private function ShowCommentBox()
	{
		//Show the insert comments box:
		print("<a name=\"comment_box_anchor\"></a>");
		print('<form action='.CreateHREF(PAGE_CONTENT, 'id='.$_GET['id']).' method="post">');
		?>
		<center><table style="width:65%;border:1px dashed gray;margin:5px 0">
		<tr><th class="cmts center" colspan="2">Post a Comment</th></tr>
		<tr>
		<th class="cmts" width="10%">Name</th><td width="35%"><input type="text" name="name"/></td>
		</tr>
		<tr>
		<th class="cmts">Email<span style="color:red">*</span></th><td><input width="35%" type="text" name="email"/></td>
		<tr><th class="cmts" colspan="2">Comment</th></tr>
		<tr>
		<td colspan="2">
		<textarea style="width:95%" name="comment"></textarea>
		</td>
		</tr>
		<tr><td style="font-size:8pt" colspan="2"><span style="color:red">*</span><i>
		Email addresses are optional, and are subject to the 
		<a href=<?php print CreateHREF(PAGE_ABOUT, "page=privacy")?>>privacy policy</a>.</i></td>
		<tr><td colspan="2"><center><input type="submit" class="button" name="submit" value="Post Comment"/></center></td></tr>
		<input type="hidden" name="stage" value="1"/>

		</table></center>
		<?php
		print('</form>');
	}

	private function ShowComments()
	{
		print("<h3 class=\"center\">Comments</h3>\n");
		print("<center><a class=\"a_cmt\" href=\"#comment_box_anchor\">Post a comment</a>.</center>\n");
		//Show comments:
		$qry = 'select txtName, txtComment,
		date_format(dtPosted, "%b %e, %Y at %l:%i %p") as dt from tblComment
			where idContent='.$_GET['id'].' and bApproved=true order by dtPosted desc';
		$res2 = $this->DoQuery($qry);
		if($res2 == true)
		{
			for($i=0; $i<$res2->num_rows; $i++)
			{
				$row=$res2->fetch_assoc();
				printf("<div style=\"margin:1em 25%%\"><p style=\"margin:0\">%s</p><p style=\"margin:0\"><small><i>by %s on %s</i></small></p><hr/></div>\n", $row['txtComment'], $row['txtName'], $row['dt']);
			}
				$res2->free();
		}
	}
}

?>
