<?php

class CTableUser extends CTable
{
	function CTableUser()
	{
		parent::CTable('tblUser');
	}
	
	public function GetUsers()
	{
		$this->DoSelect('id,txtUserName,txtAlias', '', 'txtAlias');
		return $this->m_rows;
	}
	
	public function GetUserId($strUser, $strFullHashPwd, $strSalt)
	{
		$this->DoSelect('id,txtUserName' , 'txtUserName="'.$strUser.'" and sha1(concat(txtPassword, "'.$strSalt.'")) = "'.$strFullHashPwd.'"');
		
		return count($this->m_rows) == 1 ? (int)$this->m_rows[0]['id'] : false;		
	}
	
	public function InsertNew($sUname, $sAlias, $sEmail, $nAccess, $sPass)
	{
		assert('integer' == gettype($nAccess));
		
		//We'll do some verification to make sure this is valid:
		if(!preg_match('/^[A-Za-z0-9]{6,}$/', $sUname))
		{
			RCError_PostError('User names may only contain letters and numbers and must be at least 6 charactrs long.');
			return false;
		}
		
		if(!preg_match('/^[A-Za-z0-9_ ]*$/', $sAlias))
		{
			RCError_PostError('Aliases may only contain leters numbers and -,_');
			return false;
		}
		
		$strEmailRegEx = "/^[^0-9][A-z0-9_]+([.][A-z0-9_]+)*[@][A-z0-9_]+([.][A-z0-9_]+)*[.][A-z]{2,4}$/";

		if(!preg_match($strEmailRegEx, $sEmail))
		{
			RCError_PostError('That email address isn\'t valid.');
			return false;
		}
		
		if( !(1<=$nAccess && $nAccess <=10))
		{
			RCError_PostError('The access level must be between 1 and 10');
			return false;
		}
		
		if(!preg_match('/^[^ ]{6,}$/', $sPass))
		{
			RCError_PostError('Passwords must be 6 characters long and cannot contain spaces.');
			return false;
		}
		
		//We are now ready to insert.
		$data = array
		(
			 'txtUserName' => '"'.addslashes($sUname).'"',
			 'txtPassword' => 'md5("'.addslashes($sPass).'")',
			 'txtAlias'    => '"'.addslashes($sAlias).'"',
			 'txtEmail'    => '"'.addslashes($sEmail).'"',
			 'txtLastIP'   => '"0.0.0.0"',
			 'nAccessLevel'=> $nAccess,
		);
		
		$this->DoInsert($data);
		return true;
	}
	
	public function GetUserInfo($nID)
	{
		$this->DoSelect('id, txtUserName, txtAlias, txtEmail, nAccessLevel, txtLastIP', 'id='.$nID);
			
		assert(count($this->m_rows) == 1);
		
		return $this->m_rows[0];
	}
	
	public function GetUserPassword($nID)
	{
		$this->DoSelect('txtPassword', 'id='.$nID);
			
		assert(count($this->m_rows) == 1);
		
		return $this->m_rows[0]['txtPassword'];
	}
	
	public function SetUserPassword($nID, $sPass)
	{
		if(!preg_match('/^[^ ]{6,}$/', $sPass))
		{
			RCError_PostError('Passwords must be 6 characters long and cannot contain spaces.');
			return false;
		}
		
		$data = array
		(
			 'txtPassword' => '"'.$sPass.'"',
		);
		
		$this->DoUpdate($nID, $data);
	}
	
	public function UpdateIP($nID, $sIP)
	{
		$data = array
		(
			 'txtLastIP' => '"'.$sIP.'"',
		);
		
		$this->DoUpdate($nID, $data);
	}
}
?>
