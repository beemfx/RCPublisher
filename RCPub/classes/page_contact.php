<?php
require_once('page_base.php');
require_once('table_user.php' );
require_once('table_mail.php' );
//require('smtp_validate.php');

class CContactPage extends CPageBase
{
	public function CContactPage()
	{
		parent::CPageBase('Contact', 0);
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

	private function CreateSendToChoices($Selected)
	{
		$UserTable = new CTableUser();
		
		$Users = $UserTable->GetUsers();
	

		for($i=0; $i<count($Users); $i++)
		{
			$row=$Users[$i];
			
			//If we are trying to contact a specific user we only display that option
			//otherwise we just display the entire list.
			if((null == $Selected) || (null != $Selected && ($Selected == $row['txtUserName'] || $Selected == $row['id'])))
			{
				printf("<option value=\"%d\"%s>%s</option>\n",
					$row['id'],
					$Selected == $row['txtUserName']?' selected="selected"':'',
					$row['txtAlias']);
			}
		}
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
		$this->CreateSendToChoices(isset($_GET['to'])?$_GET['to']:null);
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
			$_GET['to'] = (int)$_POST['send_to'];
			$this->DisplayStage0();
			return;
		}
		
		$MailTable = new CTableMail();
		
		$MailTable->PostMail(null, (int)$_POST['send_to'], $_POST['name'], $_POST['reply_email'], $_POST['subject'], $_POST['message']);

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
