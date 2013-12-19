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
	array( 'type' => RCSESSION_MODIFYUSER , 'desc' => 'Edit User' ) ,
	array( 'type' => RCSESSION_EDITSETTINGS , 'desc' => 'Edit Settings' ) ,
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

	protected function DisplayContent()
	{
		printf( '<h1>User Settings [%s]</h1>' , RCSession_GetUserProp( 'user' ) );
		//No matter what we display the form.
		if( RCSession_IsPermissionAllowed( RCSESSION_MODIFYUSER ) )
		{
			$this->DisplayChangeUser();
		}

		if( RCSession_IsPermissionAllowed( RCSESSION_CREATEUSER ) )
		{
			$this->DisplayCreateNewUser();
		}
	}

	private function DisplayChangeUser()
	{
		?>
		<h3>Change Password</h3>
		<div style="width:100%;margin:0;padding:1em">
			<form action=<?php print CreateHREF( PAGE_USER ) ?> method="post" name="PChangeForm">

				<input type="hidden" name="stage" value="upass"/>
				<table style ="width:50%">
					<tr><th>Old Password:</th><td><input type="password" name="opass" value="<?php ?>" style="width:50%"/></td></tr>
					<tr><th>New Password:</th><td><input type="password" name="npass" value="<?php ?>" style="width:50%"/></td></tr>
					<tr><th>Confirm New:</th><td><input type="password" name="npassc" value="<?php ?>" style="width:50%"/></td></tr>
		<?php
		global $PAGEUSER_MODIFIABLE_SETTINGS;
		for( $i = 0; $i < count( $PAGEUSER_MODIFIABLE_SETTINGS ); $i++ )
		{
			$Set = $PAGEUSER_MODIFIABLE_SETTINGS[ $i ];
			$IsSet = RCSession_IsPermissionAllowed( $Set[ 'type' ] );
			printf( "<tr><th>%s</th><td><input type=\"checkbox\" name=\"user_perm_%s\" %s/></td></tr>\n" , $Set[ 'desc' ] , $Set[ 'type' ] , $IsSet ? 'checked' : ''  );
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
					<tr><th>Username:</th><td><input type="text" name="uname" value="<?php echo RCWeb_GetPost( 'uname' ) ?>" style="width:50%"/></td></tr>
					<tr><th>Alias:</th><td><input type="text" name="ualias" value="<?php echo RCWeb_GetPost( 'ualias' ) ?>" style="width:50%"/></td></tr>
					<tr><th>Email:</th><td><input type="text" name="uemail" value="<?php echo RCWeb_GetPost( 'uemail' ) ?>" style="width:50%"/></td></tr>
					<tr><th>Access Level:</th><td><input type="text" name="uaccess" value="<?php echo RCWeb_GetPost( 'uaccess' ) ?>" style="width:50%"/></td></tr>
					<tr><th>Password:</th><td><input type="password" name="npass" value="<?php ?>" style="width:50%"/></td></tr>
					<tr><th>Confirm Password:</th><td><input type="password" name="npassc" value="<?php ?>" style="width:50%"/></td></tr>
				</table>
				<center><input class="button" type="submit" value="Submit"/></center>
			</form>
		</div>
		<?php
	}

	private function UpdateUser_UpdatePassword()
	{
		$NewPw = RCWeb_GetPost( 'npass' );

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
		$this->m_UserTable->SetUserPassword( ( int ) RCSession_GetUserProp( 'user_id' ) , RCWeb_GetPost( 'npass' ) );
		RCError_PushError( 'Password updated.' , 'message' );
	}

	private function UpdateUser_UpdatePerms()
	{
		global $PAGEUSER_MODIFIABLE_SETTINGS;
		$UserPerms = $this->m_UserTable->GetPerms( ( int ) RCSession_GetUserProp( 'user_id' ) );

		for( $i = 0; $i < count( $PAGEUSER_MODIFIABLE_SETTINGS ); $i++ )
		{
			$Set = $PAGEUSER_MODIFIABLE_SETTINGS[ $i ];
			$NewSetting = RCWeb_GetPost( 'user_perm_'.$Set[ 'type' ] );
			//RCError_PushError( 'The new setting is '.($NewSetting?'true':'false').' for user_perm_'.$Set['type'] );
			//Always clear the settings
			$UserPerms &= ~$Set[ 'type' ];
			if( $NewSetting )
			{
				$UserPerms |= $Set[ 'type' ];
			}
		}
		$this->m_UserTable->SetPerms( ( int ) RCSession_GetUserProp( 'user_id' ) , $UserPerms );
		RCSession_SetPermissions( $UserPerms );
	}

	private function UpdateUser()
	{
		$this->UpdateUser_UpdatePassword();
		$this->UpdateUser_UpdatePerms();
	}

	protected function InsertNewUser()
	{
		if( RCWeb_GetPost( 'npass' ) != RCWeb_GetPost( 'npassc' ) )
		{
			RCError_PushError( 'Passwords must match' , 'warning' );
			return;
		}
		$this->m_UserTable->InsertNew( RCWeb_GetPost( 'uname' ) , RCWeb_GetPost( 'ualias' ) , RCWeb_GetPost( 'uemail' ) , ( int ) RCWeb_GetPost( 'uaccess' ) , RCWeb_GetPost( 'npass' ) );
		RCError_PushError( 'New user created. Log out to log in with the new user.' , 'message' );
		RCWeb_ClearPostData();
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
						if (document.PChangeForm.npass.value.length >= 0)
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
