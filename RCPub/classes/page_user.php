<?php
/*******************************************************************************
 * File:   page_user.php
 * Class:  CPageUser
 * Purpose: Page for editing user settings.
 *
 * Copyright (C) 2012 Beem Software
 ******************************************************************************/
require_once('page_base.php');


class CPageUser extends CPageBase
{
	const RQ_USERLEVEL = 1;
	
	private $m_UserTable;
	
	public function CPageUser()
	{
		parent::CPageBase('User Settings', self::RQ_USERLEVEL);
	}
	
	protected function DisplayPre()
	{
		$this->m_UserTable = new CTableUser();
		
		if($_POST['stage'] == 'upass')
		{
			$this->UpdatePassword();
		}
	}

	protected function DisplayContent()
	{
		printf( '<h1>User Settings [%s]</h1>', RCSession_GetUserProp('user'));
		RCError_ShowErrors();
		//No matter what we display the form.
		$this->DisplayChangePassword();
	}
	
	private function DisplayChangePassword()
	{
		?>
		<div style="width:100%;margin:0;padding:1em">
		<form action=<?php print CreateHREF(PAGE_USER)?> method="post" name="PChangeForm">
			
		<input type="hidden" name="stage" value="upass"/>
		<table style ="width:50%">
		<tr><th>Old Password:</th><td><input type="password" name="opass" value="<?php ?>" style="width:50%"/></td></tr>
		<tr><th>New Password:</th><td><input type="password" name="npass" value="<?php ?>" style="width:50%"/></td></tr>
		<tr><th>Confirm New:</th><td><input type="password" name="npassc" value="<?php ?>" style="width:50%"/></td></tr>
		</table>
		<center><input class="button" type="button" value="Submit" onclick="javascript:onSubmitPChange()"/></center>
		</form>
		</div>
		<?php
	}
	
	private function UpdatePassword()
	{
		if($_POST['npass'] != $_POST['npassc'])
		{
			RCError_PostError('New passwords do not match.');
			return;
		}
		
		$sPass = $this->m_UserTable->GetUserPassword((int)RCSession_GetUserProp('user_id'));
		
		if($_POST['opass'] != $sPass)
		{
			RCError_PostError('Old password is not correct.');
			return;
		}
		
		//We're good to go, stuff it in.
		$this->m_UserTable->SetUserPassword((int)RCSession_GetUserProp('user_id'), $_POST['npass']);
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
		if(document.PChangeForm.npass.value.length >= 6)
		{
			encryptPassword();
			document.PChangeForm.submit();
		}
	}

	function encryptPassword()
	{
		var opass = document.PChangeForm.opass;
		var npass = document.PChangeForm.npass;
		var npassc = document.PChangeForm.npassc;

		opass.value  = hex_md5(opass.value);
		npass.value  = hex_md5(npass.value);
		npassc.value = hex_md5(npassc.value);
	}
	</script>
	<?php
	}
}
?>
