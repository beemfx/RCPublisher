<?php

class CTableUser extends CTable
{

	function CTableUser()
	{
		parent::CTable( 'tblUser' );
	}

	public function GetUsers()
	{
		$this->DoSelect( 'id,txtUserName,txtAlias' , '' , 'txtAlias' );
		return $this->m_rows;
	}

	public function GetUserId( $strUser , $strFullHashPwd , $strSalt )
	{
		$this->DoSelect( 'id,txtUserName' , 'txtUserName="'.$strUser.'" and sha1(concat(txtPassword, "'.$strSalt.'")) = "'.$strFullHashPwd.'"' );

		return count( $this->m_rows ) == 1 ? ( int ) $this->m_rows[ 0 ][ 'id' ] : false;
	}

	public function InsertNew( $sUname , $sAlias , $sEmail , $Perms , $sPass )
	{
		assert( 'integer' == gettype( $Perms ) );
		
		$sUname = substr( $sUname , 0 , 32 );
		$sAlias = substr( strip_tags( $sAlias ) , 0, 32 );
		$sEmail = substr( $sEmail , 0, 32 );
		$sPass = substr( $sPass, 0, 41 );

		//We'll do some verification to make sure this is valid:
		if( !preg_match( RCRX_USERNAME , $sUname ) )
		{
			RCError_PushError( 'User names may only contain letters and numbers and must be at least 5 charactrs long.' , 'warning' );
			return false;
		}
		
		if( $this->IsUserNameTaken( $sUname  ) )
		{
			RCError_PushError( 'That username is already taken.' , 'warning' );
			return false;
		}

		if( !preg_match( RCRX_USERALIAS , $sAlias ) )
		{
			RCError_PushError( 'Names may only contain letters, numbers, spaces, and -,_' , 'warning' );
			return false;
		}

		if( !RCWeb_ValidateEmail( $sEmail ) )
		{
			RCError_PushError( 'That email address is not valid.' , 'warning' );
			return false;
		}

		if( !preg_match( RCRX_PASSWORD , $sPass ) )
		{
			RCError_PushError( RCRX_PASSWORD_REQ , 'warning' );
			return false;
		}

		//We are now ready to insert.
		$data = array
			(
			'txtUserName' => '"'.addslashes( $sUname ).'"' ,
			'txtPassword' => 'md5("'.addslashes( $sPass ).'")' ,
			'txtAlias' => '"'.addslashes( $sAlias ).'"' ,
			'txtEmail' => '"'.addslashes( $sEmail ).'"' ,
			'txtLastIP' => '"0.0.0.0"' ,
			'nAccessLevel' => 100 ,
			'nPerms' => $Perms ,
		);

		$this->DoInsert( $data );
		return true;
	}

	public function GetUserInfo( $nID )
	{
		$this->DoSelect( 'id, txtUserName, txtAlias, txtEmail, nAccessLevel, txtLastIP, nPerms' , 'id='.$nID );

		assert( count( $this->m_rows ) == 1 );

		return $this->m_rows[ 0 ];
	}

	public function GetUserPassword( $nID )
	{
		$this->DoSelect( 'txtPassword' , 'id='.$nID );

		assert( count( $this->m_rows ) == 1 );

		return $this->m_rows[ 0 ][ 'txtPassword' ];
	}
	
	public function SetUserAlias( $Id , $Alias )
	{
		$Alias = substr( $Alias , 0, 32 );
		if( !preg_match( RCRX_USERALIAS , $Alias ) )
		{
			RCError_PushError( RCRX_USERALIAS_REQ , 'warning' );
			return false;
		}

		$data = array
			(
			'txtAlias' => '"'.addslashes($Alias).'"' ,
		);

		$this->DoUpdate( $Id , $data );
		RCSession_SetUserProp( 'user_alias', $Alias );
	}
	
	public function SetUserEmail( $Id , $Email )
	{
		$Email = substr( $Email , 0, 32 );
		
		if( !RCWeb_ValidateEmail( $Email ) )
		{
			RCError_PushError( 'Invalid email address.' , 'warning' );
			return false;
		}

		$data = array
			(
			'txtEmail' => '"'.addslashes($Email).'"' ,
		);

		$this->DoUpdate( $Id , $data );
		RCSession_SetUserProp( 'user_email', $Email );
	}

	public function SetUserPassword( $nID , $sPass )
	{
		$sPass = substr( $sPass , 0, 41 );
		
		if( !preg_match( RCRX_PASSWORD , $sPass ) )
		{
			RCError_PushError( RCRX_PASSWORD_REQ , 'warning' );
			return false;
		}

		$data = array
			(
			'txtPassword' => '"'.$sPass.'"' ,
		);

		$this->DoUpdate( $nID , $data );
	}

	public function GetPerms( $nID )
	{
		$this->DoSelect( 'nPerms' , 'id='.$nID );
		assert( count( $this->m_rows ) == 1 );
		return $this->m_rows[ 0 ][ 'nPerms' ];
	}

	public function SetPerms( $nID , $Perms )
	{
		assert( 'integer' == gettype( $Perms ) );
		$data = array( 'nPerms' => '"'.$Perms.'"' , );
		$this->DoUpdate( $nID , $data );
	}

	public function UpdateIP( $nID , $sIP )
	{
		$data = array
			(
			'txtLastIP' => '"'.$sIP.'"' ,
		);

		$this->DoUpdate( $nID , $data );
	}
	
	public function IsUserNameTaken( $UserName )
	{
		$this->DoSelect( 'id','txtUserName="'.$UserName.'"');
		return count( $this->m_rows ) > 0;
	}

}

?>
