<?php
require_once('page_base.php');
require_once('table_user.php' );
require_once('table_mail.php' );
//require('smtp_validate.php');

class CContactPage extends CPageBase
{

	public function CContactPage()
	{
		parent::CPageBase( 'Contact' );
	}

	protected function IsPageAllowed()
	{
		return RCSession_IsPermissionAllowed( RCSESSION_CONTACTUSER );
	}

	protected function DisplayPre()
	{
		if( 1 == RCWeb_GetPost( 'stage' , 0 ) )
		{
			$bRes = $this->ProcessInput();
			//If we succeeded, redirect. Otherwise, display the contact form again.
			if( $bRes )
			{
				header( "Location: ".CreateHREF( PAGE_CONTACT , 'sent' , true ) );
				exit;
			}
		}
	}
	
	protected function GetContentHeader()
	{
		return "Contact\n";
	}

	protected function DisplayContent()
	{
		print('<div style="margin:1em">' );
		if( isset( $_GET[ 'sent' ] ) )
		{
			$this->DisplayMessageSent();
		}
		else
		{
			$this->DisplayForm();
		}
		print('</div>' );
	}

	private function CreateSendToChoices( $Selected )
	{
		$UserTable = new CTableUser();

		$Users = $UserTable->GetUsers();


		for( $i = 0; $i < count( $Users ); $i++ )
		{
			$row = $Users[ $i ];

			//If we are trying to contact a specific user we only display that option
			//otherwise we just display the entire list.
			if( (null == $Selected) || (null != $Selected && ($Selected == $row[ 'txtUserName' ] || $Selected == $row[ 'id' ])) )
			{
				printf( "<option value=\"%d\"%s>%s</option>\n" , $row[ 'id' ] , $Selected == $row[ 'txtUserName' ] ? ' selected="selected"' : '' , $row[ 'txtAlias' ] );
			}
		}
	}

	private function DisplayForm()
	{
		?>
		<form method="post" action=<?php print CreateHREF( PAGE_CONTACT ) ?>>
			<input type="hidden" name="stage" value="1"/>
			<table>
				<tr>
					<th width="25%">Send To</th>
					<td>
						<select name="send_to" size="1">
		<?php
		$this->CreateSendToChoices( RCWeb_GetGet( 'to' , null ) );
		?>
						</select>
					</td>
				</tr>
				<tr>
					<th>Name</th>
					<td><input type="text" name="name" value=<?php print '"'.RCWeb_GetPost( 'name' , '' ).'"' ?>/></td>
				</tr>
				<tr>
					<th>Reply Email <span style="color:red">(Required)</span></th>
					<td><input type="text" name="reply_email" value=<?php print '"'.RCWeb_GetPost( 'reply_email' , '' ).'"' ?>/></td>
				</tr>
				<tr>
					<th>Subject</th><td><input type="text" name="subject" value=<?php print '"'.RCWeb_GetPost( 'subject' , '' ).'"' ?>/></td>
				</tr>
				<tr>
					<th colspan="2">Message</th>
				</tr>
				<tr>
					<td colspan="2">
						<textarea name="message" style="height:200px;width:100%"><?php print RCWeb_GetPost( 'message' , '' ) ?></textarea>
					</td>
				</tr>
				<tr>
					<td colspan="2"><?php RCSpam_DisplayQuestion();
					echo '<br/>';
					RCSpam_DisplayResponseArea(); ?></td>
				</tr>
				<tr>
					<td colspan="2"><input class="button" type="submit" value="Send Email"/></td>
				</tr>
			</table>
		</form>
		<?php
	}

	private function ProcessInput()
	{
		if( !$this->ValidateInput() )
		{
			RCError_PushError( 'One or more required fields was missing.' , 'warning' );
			$_GET[ 'to' ] = ( int ) RCWeb_GetPost( 'send_to' , 0 , true );
			return false;
		}

		$MailTable = new CTableMail();

		$MailTable->PostMail
			(
			null , ( int ) RCWeb_GetPost( 'send_to' , '' , true ) , RCWeb_GetPost( 'name' , '' , true ) , RCWeb_GetPost( 'reply_email' , '' , true ) , RCWeb_GetPost( 'subject' , '' , true ) , RCWeb_GetPost( 'message' , '' , true )
		);

		return true;
	}

	private function DisplayMessageSent()
	{
		print('<p>Message sent. Return <a href='.CreateHREF( PAGE_HOME ).'>home</a>.</p>' );
	}

	private function ValidateInput()
	{
		if( !$this->ValidateEmail( RCWeb_GetPost( 'reply_email' , '' , true ) ) )
		{
			RCError_PushError( RCWeb_GetPost( 'reply_email' ).' is not a valid email address.' , 'warning' );
			return false;
		}

		if( strlen( RCWeb_GetPost( 'message' , '' , true ) ) < 1 )
		{
			RCError_PushError( 'A message is required.' , 'warning' );
			return false;
		}

		if( strlen( RCWeb_GetPost( 'subject' , '' , true ) ) < 1 )
		{
			RCWeb_SetPost( 'subject' , 'no subject' );
		}

		if( !RCSpam_IsAnswerCorrect() )
		{
			RCError_PushError( 'The security code was invalid.' , 'warning' );
			return false;
		}

		return true;
	}

	private function ValidateEmail( $strE )
	{
		return RCWeb_ValidateEmail( $strE );
	}

}
?>
