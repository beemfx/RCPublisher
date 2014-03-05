<?php
/* * *****************************************************************************
 * File:   page_user.php
 * Class:  CPageUser
 * Purpose: Page for editing user settings.
 *
 * Copyright (C) 2012 Beem Software
 * **************************************************************************** */
require_once('page_base.php');

$PAGEUSER_MODIFIABLE_SETTINGS = array
(
	array( 'type' => RCSESSION_CREATEPAGE , 'desc' => 'Create New Page' ) ,
	array( 'type' => RCSESSION_MODIFYPAGE , 'desc' => 'Modify Page' ) ,
	array( 'type' => RCSESSION_CREATENEWS , 'desc' => 'Post News' ) ,
	array( 'type' => RCSESSION_MODIFYNEWS , 'desc' => 'Edit News' ) ,
	array( 'type' => RCSESSION_CREATEFILE , 'desc' => 'Upload File' ) ,
	array( 'type' => RCSESSION_MODIFYFILE , 'desc' => 'Edit File' ) ,
	array( 'type' => RCSESSION_CREATEUSER , 'desc' => 'Create New User' ) ,
	array( 'type' => RCSESSION_EDITSETTINGS , 'desc' => 'Edit Settings' ) ,
	array( 'type' => RCSESSION_MODIFYUSER , 'desc' => 'Change User Permissions' ) ,
	array( 'type' => RCSESSION_MODIFYFEEDBACK , 'desc' => 'Approve Comments' ) ,
	array( 'type' => RCSESSION_DELETEFEEDBACK , 'desc' => 'Delete Comments' ) ,
	array( 'type' => RCSESSION_CREATEFEEDBACK , 'desc' => 'Post Comments (If global comments are allowed, this has no effect.)' ) ,
);
class CPageUser extends CPageBase
{

	private $m_UserTable;

	public function CPageUser()
	{
		parent::CPageBase( 'User Settings' );
	}

	protected function IsPageAllowed()
	{
		return RCSession_IsPermissionAllowed( RCSESSION_CREATEUSER ) || RCSession_IsPermissionAllowed( RCSESSION_MODIFYUSER ) || RCSession_IsPermissionAllowed( RCSESSION_DELETEUSER );
	}

	protected function DisplayPre()
	{
		$this->m_UserTable = new CTableUser();

		if( RCWeb_GetPost( 'stage' ) == 'upass' )
		{
			$this->UpdateUser();
		}
		else if( RCWeb_GetPost( 'stage' ) == 'nuser' )
		{
			$this->InsertNewUser();
		}
	}
	
	protected function GetContentHeader()
	{
		return sprintf( 'User Settings [%s]' , RCSession_GetUserProp( 'user' ) );
	}

	protected function DisplayContent()
	{
		//No matter what we display the form.

		$this->DisplayChangeUser();

		if( RCSession_IsPermissionAllowed( RCSESSION_CREATEUSER ) )
		{
			$this->DisplayCreateNewUser();
		}
		
		$this->DisplayListOfUsers();
	}
	
	private function DisplayListOfUsers()
	{
		$Users = $this->m_UserTable->GetUsers();
		echo '<h3>All Users</h3>';
		
		for( $i=0; $i < count($Users); $i++ )
		{
			$User = $Users[$i];
			printf( "<p>%s (%d) - %s; Last IPs: %s , %s</p>\n", $User['txtUserName'], $User['id'], $User['txtAlias'], $User['txtLastIP'], $User['txtLastIP2']);
		}
	}

	private function DisplayUserPermsChecklist( $Label , $ImportChecks = true , $EditUser = false )
	{
		assert( 'string' == gettype( $Label ) );
		assert( 'boolean' == gettype( $ImportChecks ) );
		print( "<tr><th>Permissions</th><td></td></tr>\n" );
		global $PAGEUSER_MODIFIABLE_SETTINGS;
		for( $i = 0; $i < count( $PAGEUSER_MODIFIABLE_SETTINGS ); $i++ )
		{
			$Set = $PAGEUSER_MODIFIABLE_SETTINGS[ $i ];
			if( !$EditUser && RCSESSION_MODIFYUSER == $Set['type'] )
			{
				continue;
			}
			
			$IsSet = RCSession_IsPermissionAllowed( $Set[ 'type' ] );
			printf( "<tr><td>%s</td><td><input type=\"checkbox\" name=\"%s%s\" %s/></td></tr>\n" , $Set[ 'desc' ] , $Label , $Set[ 'type' ] , $IsSet || !$ImportChecks ? 'checked' : ''  );
		}
	}

	private function DisplayChangeUser()
	{
		?>
		<h3>Change User Settings</h3>
		Leave password fields blank if you don't wish to change it.
		<div style="width:100%;margin:0;padding:1em">
			<form action=<?php print CreateHREF( PAGE_USER ) ?> method="post" name="PChangeForm">

				<input type="hidden" name="stage" value="upass"/>
				<table style ="width:50%">
					<tr><th>Name:</th><td><input type="text" name="nuname" value="<?php echo RCSession_GetUserProp( 'user_alias' ); ?>" style="width:75%"/></td></tr>
					<tr><th>Email:</th><td><input type="text" name="nuemail" value="<?php echo RCSession_GetUserProp( 'user_email' ); ?>" style="width:75%"/></td></tr>
					<tr><th>Old Password:</th><td><input type="password" name="opass" value="<?php ?>" style="width:75%"/></td></tr>
					<tr><th>New Password:</th><td><input type="password" name="npass" value="<?php ?>" style="width:75%"/></td></tr>
					<tr><th>Confirm New:</th><td><input type="password" name="npassc" value="<?php ?>" style="width:75%"/></td></tr>
					<?php
					if( RCSession_IsPermissionAllowed( RCSESSION_MODIFYUSER ) )
					{
						$this->DisplayUserPermsChecklist( 'user_perm_' );
					}
					?>
				</table>
				<center><input class="button" type="button" value="Submit" onclick="javascript:onSubmitPChange()"/></center>
			</form>
		</div>
		<?php
	}

	private function DisplayCreateNewUser()
	{
		?>
		<h3>Create New User</h3>
		<div style="width:100%;margin:0;padding:1em">
			<form action=<?php print CreateHREF( PAGE_USER ) ?> method="post" name="NewUserForm">

				<input type="hidden" name="stage" value="nuser"/>
				<table style ="width:50%">
					<tr><th>Username:</th><td><input type="text" name="uname" value="<?php echo RCWeb_GetPost( 'uname' ) ?>" style="width:75%"/></td></tr>
					<tr><th>Real Name:</th><td><input type="text" name="ualias" value="<?php echo RCWeb_GetPost( 'ualias' ) ?>" style="width:75%"/></td></tr>
					<tr><th>Email:</th><td><input type="text" name="uemail" value="<?php echo RCWeb_GetPost( 'uemail' ) ?>" style="width:75%"/></td></tr>
					<tr><th>Password:</th><td><input type="password" name="npass" value="<?php ?>" style="width:75%"/></td></tr>
					<tr><th>Confirm Password:</th><td><input type="password" name="npassc" value="<?php ?>" style="width:75%"/></td></tr>
					<?php
					$this->DisplayUserPermsChecklist( 'create_user_perm_' , false  , true );
					?>
				</table>
				<center><input class="button" type="submit" value="Submit"/></center>
			</form>
		</div>
		<?php
	}

	private function UpdateUser_UpdatePasswordAndName()
	{
		if( preg_match( RCRX_USERALIAS , RCWeb_GetPost( 'nuname' ) ) )
		{
				$this->m_UserTable->SetUserAlias(  RCSession_GetUserProp( 'user_id' ) , RCWeb_GetPost( 'nuname' ) );
		}
		else
		{
			RCError_PushError( RCRX_USERALIAS_REQ , 'warning' );
		}
		
		if( RCWeb_ValidateEmail( RCWeb_GetPost( 'nuemail' ) ) )
		{
				$this->m_UserTable->SetUserEmail(  RCSession_GetUserProp( 'user_id' ) , RCWeb_GetPost( 'nuemail' ) );
		}
		else
		{
			RCError_PushError( 'Invalid email address.' , 'warning' );
		}
		
		$NewPw = RCWeb_GetPost( 'npass' );

		if( 0 == strlen( $NewPw ) && 0 == strlen( RCWeb_GetPost( 'npassc' ) ) )
		{
			//We didn't want to update the password, so don't show a warning.
			return;
		}

		if( strlen( $NewPw ) <= 0 )
		{
			RCError_PushError( RCRX_PASSWORD_REQ , 'warning' );
			return;
		}


		if( $NewPw != RCWeb_GetPost( 'npassc' ) )
		{
			RCError_PushError( 'New passwords do not match.' , 'warning' );
			return;
		}

		$sPass = $this->m_UserTable->GetUserPassword( ( int ) RCSession_GetUserProp( 'user_id' ) );

		if( RCWeb_GetPost( 'opass' ) != $sPass )
		{
			RCError_PushError( 'Old password is not correct.' , 'warning' );
			return;
		}

		//We're good to go, stuff it in.
		$this->m_UserTable->SetUserPassword(  RCSession_GetUserProp( 'user_id' ) , RCWeb_GetPost( 'npass' ) );
		RCError_PushError( 'Password updated.' , 'message' );
	}

	private function UpdateUser_UpdatePerms()
	{		
		if( !RCSession_IsPermissionAllowed( RCSESSION_MODIFYUSER ) )
		{
			return;
		}

		global $PAGEUSER_MODIFIABLE_SETTINGS;
		$UserPerms = $this->m_UserTable->GetPerms( ( int ) RCSession_GetUserProp( 'user_id' ) );

		for( $i = 0; $i < count( $PAGEUSER_MODIFIABLE_SETTINGS ); $i++ )
		{
			$Set = $PAGEUSER_MODIFIABLE_SETTINGS[ $i ];
			$NewSetting = RCWeb_GetPost( 'user_perm_'.$Set[ 'type' ] );
			//RCError_PushError( 'The new setting is '.($NewSetting?'true':'false').' for user_perm_'.$Set['type'] );
			//Always clear the settings
			$UserPerms &= ~$Set[ 'type' ];
			if( $NewSetting || RCSESSION_MODIFYUSER == $Set['type'])
			{
				$UserPerms |= $Set[ 'type' ];
			}
		}
		$UserPerms |= RCSESSION_MODIFYUSER;
		$this->m_UserTable->SetPerms( RCSession_GetUserProp( 'user_id' ) , $UserPerms );
		RCSession_SetPermissions( $UserPerms );
	}

	private function UpdateUser()
	{
		$this->UpdateUser_UpdatePasswordAndName();
		$this->UpdateUser_UpdatePerms();
		RCError_PushError( 'User settings updated.' , 'message' );
	}

	protected function InsertNewUser()
	{
		if( RCWeb_GetPost( 'npass' ) != RCWeb_GetPost( 'npassc' ) )
		{
			RCError_PushError( 'Passwords must match' , 'warning' );
			return;
		}
		
		//Give all permissions, but remove the ones that weren't selected.
		$Perms = 0x0FFFFFFF;
		global $PAGEUSER_MODIFIABLE_SETTINGS;
		for( $i = 0; $i < count( $PAGEUSER_MODIFIABLE_SETTINGS ); $i++ )
		{
			$Set = $PAGEUSER_MODIFIABLE_SETTINGS[ $i ];
			$NewSetting = RCWeb_GetPost( 'create_user_perm_'.$Set[ 'type' ] );
			
			$Perms &= ~$Set['type'];
			
			if( $NewSetting )
			{
				$Perms |= $Set['type'];
			}
		}
		
		
		$Success = $this->m_UserTable->InsertNew( RCWeb_GetPost( 'uname' ) , RCWeb_GetPost( 'ualias' ) , RCWeb_GetPost( 'uemail' ) , $Perms, RCWeb_GetPost( 'npass' ) );
		if( $Success )
		{
			RCError_PushError( 'New user created. Log out to log in with the new user.' , 'message' );
			RCWeb_ClearPostData();
		}
	}

	protected function DisplayPost()
	{
		?>
		<script type="text/javascript" src="js/functions.js"></script>
		<script type="text/javascript" src="js/md5.js"></script>
		<script type="text/javascript" src="js/sha1.js"></script>

		<script type="text/javascript">
					function onSubmitPChange()
					{
						if (document.PChangeForm.npass.value.length > 0)
						{
							encryptPassword();
						}
						document.PChangeForm.submit();
					}

					function encryptPassword()
					{
						var opass = document.PChangeForm.opass;
						var npass = document.PChangeForm.npass;
						var npassc = document.PChangeForm.npassc;

						var re = <?php echo RCRX_PASSWORD; ?>;

						if (re.test(npass.value))
						{
							npass.value = hex_md5(npass.value);
						}
						else
						{
							npass.value = '';
						}
						opass.value = hex_md5(opass.value);
						npassc.value = hex_md5(npassc.value);
					}
		</script>
		<?php
	}

}
?>
