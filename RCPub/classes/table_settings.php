<?php

class CTableSettings extends CTable
{

	public function CTableSettings()
	{
		parent::CTable( 'tblGlobalSettings' );
	}

	function GetSettingID( $strSetting )
	{
		$this->DoSelect( 'id' , 'txtName="'.$strSetting.'"' );
		return count( $this->m_rows ) == 1 ? ( int ) $this->m_rows[ 0 ][ 'id' ] : null;
	}

	function GetSetting( $strSetting )
	{
		$this->DoSelect( 'txtSetting' , 'txtName="'.$strSetting.'"' );

		return count( $this->m_rows ) == 1 ? $this->m_rows[ 0 ][ 'txtSetting' ] : null;
	}

	function SetSetting( $strSetting , $strNewValue )
	{
		assert( strlen( $strSetting ) <= 20 );
		
		$data = array
			(
			'txtName' => '"'.addslashes( $strSetting ).'"' ,
			'txtSetting' => '"'.addslashes( $strNewValue ).'"' ,
		);

		$OldSetting = $this->GetSettingID( $strSetting );

		if( null == $OldSetting )
		{
			//Create an all new setting...
			$this->DoInsert( $data );
		}
		else
		{
			//Update the old setting.
			$this->DoUpdate( $OldSetting , $data );
		}
	}

}

?>
