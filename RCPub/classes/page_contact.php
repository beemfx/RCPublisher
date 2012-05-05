<?php
require_once('page_base.php');
//require('smtp_validate.php');

class CContactPage extends CPageBase
{
	public function CContactPage()
	{
		parent::CPageBase('Contact', true, 0);
	}

	protected function DisplayContent()
	{
		print("<h1>Contact</h1>\n");
		print('<div style="margin:1em">');
		if($_POST['stage']==0)
		{
			$this->DisplayStage0();
		}
		else
		{
			$this->DisplayStage1();
		}
		print('</div>');
	}

	private function CreateSendToChoices()
	{
		$qry = 'select id, txtAlias from tblUser';
		$res = $this->DoQuery($qry);
		if(!$res)
			return;

		for($i=0; $i<$res->num_rows; $i++)
		{
			$row=$res->fetch_assoc();
			printf("<option value=\"%d\">%s</option>\n",
				$row['id'],
				$row['txtAlias']);
		}
		$res->free();
	}

	private function DisplayStage0()
	{
		?>
		<form method="post" action=<?php print CreateHREF(PAGE_CONTACT)?>>
		<input type="hidden" name="stage" value="1"/>
		<table>
		<tr>
		<th width="25%">Send To</th>
		<td>
		<select name="send_to" size="1">
		<?php
		$this->CreateSendToChoices();
		?>
		</select>
		</td>
		</tr>
		<tr>
		<th>Name</th>
		<td><input type="text" name="name" value=<?php print '"'.$_POST['name'].'"'?>/></td>
		</tr>
		<tr>
		<th>Reply Email <span style="color:red">(Required)</span></th>
		<td><input type="text" name="reply_email" value=<?php print '"'.$_POST['reply_email'].'"'?>/></td>
		</tr>
		<tr>
		<th>Subject</th><td><input type="text" name="subject" value=<?php print '"'.$_POST['subject'].'"'?>/></td>
		</tr>
		<tr>
		<th colspan="2">Message</th>
		</tr>
		<tr>
		<td colspan="2">
		<textarea name="message" style="height:200px;width:100%"><?php print $_POST['message']?></textarea>
		</td>
		</tr>
		<tr>
		<td><input type="text" name="secure"/><br />Type in the security code.</td>
		<td><img src="captcha/captcha_image.php" alt="Security Image" border="0"/></td>
		</tr>
		<tr>
		<td colspan="2"><input class="button" type="submit" value="Send Email"/></td>
		</tr>
		</table>
		</form>
		<?php
	}

	private function DisplayStage1()
	{

		if(!$this->ValidateInput())
		{
			$this->ShowWarning('One or more required fields was missing.');
			$this->DisplayStage0();
			return;
		}

		$strOrgMsg = $_POST['message'];
		//Modify the message so that it has paragraph markers
		//replace all newlines with <br/>s.
		$_POST['message'] = str_replace(array("\r\n", "\n", "\r"), '<br/>', $_POST['message']);

		$_POST['subject'] = addslashes($_POST['subject']);
		$_POST['message'] = addslashes($_POST['message']);
		$_POST['name'] = addslashes($_POST['name']);

		//Create the query.

		//The following query does two things, first it insures that the user that the mail is going to is
		//valid, and secondily it is used to forward the mail to the actual email.

		$resto = $this->DoQuery('select txtEmail from tblUser where id='.$_POST['send_to']);

		if(!$resto)
		{
			$this->ShowWarning('User does not exist, email most likely sent by bot, deleting message.');
			return;
		}

		$qry = sprintf('insert into tblMessage (idUser_To, idUser_From, txtName, txtEmail, txtSubject, txtMessage, bRead, dtSent)
					                       values ("%s",         %s,          %s,      "%s",  "%s",      "%s",         false, now())',
													       $_POST['send_to'],
															 'null',
			                                     strlen($_POST['name'])>0?'"'.$_POST['name'].'"':'null',
															 $_POST['reply_email'],
															 $_POST['subject'],
															 $_POST['message']);
		$this->DoQuery($qry);

		//Now send the message by acutal mail:
		if($resto==true)
		{
			$row = $resto->fetch_assoc();


			$msg = sprintf("From: %s\nReply Email: %s\nSubject: %s\n\n%s",
				$_POST['name'],
				$_POST['reply_email'],
				$_POST['subject'],
				$strOrgMsg);

			$headers = 'From: '.$_POST['reply_email']."\r\n" .
			'Reply-To: '.$_POST['reply_email']. "\r\n" .
			'X-Mailer: PHP/'. phpversion();
			mail($row['txtEmail'], 'RC Mail: '.$_POST['subject'], $msg, $headers);
			$resto->free();
		}


		print('<p>Message sent. Return <a href='.CreateHREF(PAGE_HOME).'>home</a>.</p>');
	}

	private function ValidateInput()
	{
		if(!$this->ValidateEmail($_POST['reply_email']))
		{
			$this->ShowWarning($_POST['reply_email'].' is not a valid email address.');
			return false;
		}

		if(strlen($_POST['message'])<1)
		{
			$this->ShowWarning('A message is required.');
			return false;
		}

		if(strlen($_POST['subject'])<1)
		{
			$_POST['subject'] = 'no subject';
		}

		if(strtoupper(trim(strip_tags($_POST['secure']))) != $_SESSION['captcha'])
		{
			$this->ShowWarning('The security code was invalid.');
			return false;
		}

		return true;
	}

	private function ValidateEmail($strE)
	{
		
		$strEmailRegEx = "/^[^0-9][A-z0-9_]+([.][A-z0-9_]+)*[@][A-z0-9_]+([.][A-z0-9_]+)*[.][A-z]{2,4}$/";

		return preg_match($strEmailRegEx, $strE)!=0;
		
		//return SMTP_validateEmail::ValidateEmail($strE, 'support@beemsoft.com');
	}

}
?>
