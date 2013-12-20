<?php

class CTablePageHistory extends CTable
{

	public function CTablePageHistory()
	{
		parent::CTable( 'tblPageHistory' );
	}

	public function InsertHistory( $PageId , $Title , $Body , $idUser )
	{
		assert( 'integer' == gettype( $PageId ) );
		assert( 'integer' == gettype( $idUser ) );
		assert( 0 != $idUser );

		$Title = '"'.addslashes( $Title ).'"';
		$Body = '"'.addslashes( $Body ).'"';

		//First thing to do is find out what the current maximum version is
		$History = $this->GetHistory( $PageId );

		$Version = 0;

		if( null != $History )
		{
			foreach( $History as $Item )
			{
				$Version = max( $Version , $Item[ 'idVersion' ] );
			}
		}

		$Version++;

		$data = array
			(
			'idPage' => $PageId ,
			'idVersion' => $Version ,
			'txtTitle' => $Title ,
			'txtBody' => $Body ,
			'dt' => 'now()' ,
			'idUser' => $idUser,
		);

		$this->DoInsert( $data );

		return $Version;
	}

	public function GetHistory( $PageId )
	{
		$items = 'idVersion , txtTitle , dt, date_format(dt, "%M %d %Y, %l:%i %p") as dtPretty, idUser';
		$this->DoSelect( $items , 'idPage='.$PageId , 'dt DESC' );
		return $this->m_rows;
	}

	public function GetPage( $PageId , $Version )
	{
		$items = 'txtTitle , txtBody , dt';
		$selection = 'idPage='.$PageId.' AND idVersion='.$Version;

		$this->DoSelect( $items , $selection );

		//echo 'There are this many rows: '.count($this->m_rows);

		$out = (0 == count( $this->m_rows )) ? null : $this->m_rows[ 0 ];
		$this->m_rows = null;
		if( null != $out )
		{
			
		}
		return $out;
	}

}

?>
