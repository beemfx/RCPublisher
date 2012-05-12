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
	
	public function GetUserInfo($nID)
	{
		$this->DoSelect('id, txtUserName, txtAlias, txtEmail, nAccessLevel, txtLastIP', 'id='.$nID);
			
		assert(count($this->m_rows) == 1);
		
		return $this->m_rows[0];
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
