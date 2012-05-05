<?php
require_once('page_base.php');

class CEmailPage extends CPageBase
{
	public function CEmailPage()
	{
		parent::CPageBase('Email', 1);
	}

	protected function DisplayContent()
	{
		print("<h1>Email</h1>\n");

		//Check to see if we want to delete:
		if(isset($_GET['delete']) && isset($_GET['message']))
		{
			//If so do the delete
			$qry = 'delete from tblMessage where id='.$_GET['message'].' and idUser_To='.$_SESSION['user_id'];
			$this->DoQuery($qry);
			unset($_GET['message']);
		}

		if(isset($_GET['message']))
		{
			$this->ShowMessage($_SESSION['user_id'], $_GET['message']);
		}
		else
		{
			$this->ShowMessageList($_SESSION['user_id']);
		}
	}

	private function ShowMessageList($nUserID)
	{
		$qry = 'select *
				, date_format(dtSent, "%a %c/%e/%Y %l:%i %p") as dt
				, if(txtName is not null, concat(txtName, " [", txtEmail, "]"), txtEmail) as txtDispName
			from tblMessage where idUser_To='.$nUserID.' order by dtSent desc';
		$res = $this->DoQuery($qry);

		if(!$res)
		{
			$this->ShowWarning('Could not get message list.');
			return;
		}

		//Show all the messages in a table.
		print("<table>\n");
		print("<tr><th>Action<th>From</th><th>Subject</th><th>Sent</th></tr>\n");
		for($i=0; $i<$res->num_rows; $i++)
		{
			$row = $res->fetch_assoc();

			$strClass = $row['bRead']?'elink_read':'elink_unread';
			print("<tr>\n");

			//First display the option buttons.
			print("<td>\n");
			$this->ShowButtons(intval($row['id']));
			print("</td>\n");
			//Then display the From:
			print("<td>\n");
			print('<a class="'.$strClass.'" href="mailto:'.$row['txtEmail'].'">');
			print($row['txtDispName']);
			print('</a>');
			print("</td>\n");
			//Display the subject:
			print("<td>\n");
			printf('<a class="'.$strClass.'" href=%s>%s</a>',
					CreateHREF(PAGE_EMAIL, 'message='.$row['id']),
					$row['txtSubject']);
			print("</td>\n");
			//Display the date:
			printf("<td style=\"font-size:10pt\">%s</td>\n", $row['dt']);
	
			printf("</tr>\n");
		}
		print("</table>\n");
		$res->free();
	}

	private function ShowButtons($nMsgID)
	{
		//Want a read and delete button (should use pcitures):
		print('<a href='.CreateHREF(PAGE_EMAIL, 'message='.$nMsgID).'>');
		print('<img src="images/email_read.png" alt="Read" style="border:0" />');
		print('</a> ');
		print('<a href='.CreateHREF(PAGE_EMAIL, 'message='.$nMsgID.'&delete').'>');
		print('<img src="images/email_delete.png" alt="Delete" style="border:0" />');
		print('</a>');
	}

	private function ShowMessage($nUserID, $nMsgID)
	{
		$qry = 'select *
				, date_format(dtSent, "%a %c/%e/%Y %l:%i %p") as dt
				, if(txtName is not null, concat(txtName, " [", txtEmail, "]"), txtEmail) as txtDispName
			from tblMessage where idUser_To='.$nUserID.' and id='.$nMsgID;
		$res = $this->DoQuery($qry);

		if(!$res)
		{
			$this->ShowWarning('The specified message did not exist.');
			return;
		}

		$row = $res->fetch_assoc();
		$res->free();

		//Since we got a result mark the message as read.
		$this->DoQuery('update tblMessage set bRead=true where id='.$nMsgID);

		//Just show the message:
		echo '<h3>', $row['txtSubject'], '</h3>', "\n";
		print('<p><a class="elink_unread" href="mailto:'.$row['txtEmail'].'">');
		print($row['txtDispName']);
		print('</p></a>');
		printf("<p style=\"font-size:80%%;color:blue\">Sent: %s</p>\n", $row['dt']);
		printf("<p style=\"font-size:80%%;color:blue\">To: %s [%s]</p>\n",
			$_SESSION['user_alias'],
			$_SESSION['user']);

		//print("<hr>\n");
		print('<p style="border:2px solid gray;padding:5px">');
		print($row['txtMessage']);
		print('</p>');
	}

}

?>
