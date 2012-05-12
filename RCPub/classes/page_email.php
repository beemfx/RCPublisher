<?php
require_once('page_base.php');

class CEmailPage extends CPageBase
{
	private $m_MailTable;
	
	public function CEmailPage()
	{
		parent::CPageBase('Email', 1);
	}
	
	protected function DisplayPre()
	{
		$this->m_MailTable = new CTableMail();
	}

	protected function DisplayContent()
	{
		print("<h1>Email</h1>\n");

		//Check to see if we want to delete:
		if(isset($_GET['delete']) && isset($_GET['message']))
		{
			$this->m_MailTable->DeleteMessage((int)$_SESSION['user_id'], (int)$_GET['message']);
			unset($_GET['message']);
		}

		if(isset($_GET['message']))
		{
			$this->ShowMessage((int)$_SESSION['user_id'], (int)$_GET['message']);
		}
		else
		{
			$this->ShowMessageList((int)$_SESSION['user_id']);
		}
	}

	private function ShowMessageList($nUserID)
	{
		$Messages = $this->m_MailTable->GetMessageList($nUserID);
		
		//Show all the messages in a table.
		print("<table>\n");
		print("<tr><th>Action<th>From</th><th>Subject</th><th>Sent</th></tr>\n");
		for($i=0; $i<count($Messages); $i++)
		{
			$row = $Messages[$i];

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
		$Message = $this->m_MailTable->GetMessage($nUserID, $nMsgID);
		
		if(!$Message)
		{
			$this->ShowWarning('The specified message did not exist.');
			return;
		}

		$row = $Message;

		//Since we got a result mark the message as read.
		$this->m_MailTable->MarkAsRead($nUserID, $nMsgID);

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
