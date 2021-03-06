<?php

require_once('table_page_history.php');
class CTablePage extends CTable
{

	const TITLE_COLUMN = 'txtTitle';

	public function CTablePage()
	{
		parent::CTable( 'tblPage' );
	}

	public function IsSlugTaken( $strSlug )
	{
		$this->DoSelect( 'id' , 'txtSlug="'.$strSlug.'"' );
		return count( $this->m_rows ) != 0;
	}
	
	public function GetPageTitle( $Id )
	{
		assert( 'integer' == gettype( $Id) );
		$this->DoSelect( self::TITLE_COLUMN , 'id='.$Id );
		return count( $this->m_rows ) == 1 ? $this->m_rows[0][self::TITLE_COLUMN] : 'NO PAGE';
	}

	public function CreatePage( $strSlug , $strTitle , $strBody, $idCreator )
	{
		assert( 'integer' == gettype($idCreator) );
		$Cached = new CRCMarkup( $strBody );
		
		$strSlug = substr( $strSlug, 0, 32 );
		$strTitle = substr( strip_tags($strTitle) , 0, 64 );

		$Title = $strTitle;
		$Body = $strBody;

		$strSlug = '"'.addslashes( $strSlug ).'"';
		$strTitle = '"'.addslashes( $strTitle ).'"';
		$strCached = '"'.addslashes( $Cached->GetHTML() ).'"';

		$data = array
			(
			'txtSlug' => $strSlug ,
			self::TITLE_COLUMN => $strTitle ,
			'txtBodyHTMLCache' => $strCached ,
			'idVersion_Current' => 1 ,
			'idCreator' => $idCreator,
			'idOwner' => $idCreator,
		);

		$PageId = $this->DoInsert( $data );

		if( $PageId > 0 )
		{
			$History = new CTablePageHistory();
			$History->InsertHistory( $PageId , $Title , $Body , $idCreator );
		}
		else
		{
			assert( false );
		}
	}

	public function UpdatePage( $nID , $strSlug , $strTitle , $strBody , $idUser )
	{
		assert( 'integer' == gettype( $idUser ) );
		$Cached = new CRCMarkup( $strBody );

		$Title = $strTitle;
		$Body = $strBody;

		$History = new CTablePageHistory();
		$LatestVersion = $History->InsertHistory( $nID , $Title , $Body , $idUser );

		$strSlug = '"'.addslashes( $strSlug ).'"';
		$strTitle = '"'.addslashes( $strTitle ).'"';
		$strBody = '"'.addslashes( $strBody ).'"';
		$strCached = '"'.addslashes( $Cached->GetHTML() ).'"';

		$data = array
			(
			'txtSlug' => $strSlug ,
			self::TITLE_COLUMN => $strTitle ,
			'txtBodyHTMLCache' => $strCached ,
			'idVersion_Current' => $LatestVersion ,
			'idOwner' => $idUser,
		);

		$this->DoUpdate( $nID , $data );
	}

	public function DeletePage( $unkIdOrSlug )
	{
		
	}
	
	public function GetOwner( $PageId )
	{
		$this->DoSelect( 'idCreator, idOwner', 'id='.$PageId );
		assert( 1 == count($this->m_rows) );
		return $this->m_rows[0];
	}

	public function GetPages()
	{
		$items = 'id,txtSlug,'.self::TITLE_COLUMN;
		$this->DoSelect( $items , '' , self::TITLE_COLUMN );
		return $this->m_rows;
	}

	public function GetPage( $unkIdOrSlug , $Version = null )
	{
		$out = null;

		if( null == $Version )
		{
			$items = 'id,txtSlug as slug,idOwner,'.self::TITLE_COLUMN.' as title,txtBodyHTMLCache as formatted';

			if( 'integer' == gettype( $unkIdOrSlug ) )
			{
				$selection = 'id='.$unkIdOrSlug;
			}
			else if( 'string' == gettype( $unkIdOrSlug ) )
			{
				$selection = 'txtSlug="'.$unkIdOrSlug.'"';
			}
			else
			{
				assert( false );
			}
			$this->DoSelect( $items , $selection );

			//echo 'There are this many rows: '.count($this->m_rows);

			$out = (0 == count( $this->m_rows )) ? null : $this->m_rows[ 0 ];
			$this->m_rows = null;
			if( null != $out )
			{
				
			}
		}
		else
		{
			//Get the version of the page.
		}

		return $out;
	}

	public function GetContentForEdit( $unkIdOrSlug , $Version = 0 )
	{
		$items = 'id , idVersion_Current';

		if( 'integer' == gettype( $unkIdOrSlug ) )
		{
			$selection = 'id='.$unkIdOrSlug;
		}
		else if( 'string' == gettype( $unkIdOrSlug ) )
		{
			$selection = 'txtSlug="'.$unkIdOrSlug.'"';
		}
		else
		{
			assert( false );
		}
		$this->DoSelect( $items , $selection );

		//echo 'There are this many rows: '.count($this->m_rows);

		$out = (0 == count( $this->m_rows )) ? null : $this->m_rows[ 0 ];
		$this->m_rows = null;
		if( null != $out )
		{
			$History = new CTablePageHistory();
			$Page = $History->GetPage( $out[ 'id' ] , 0 == $Version ? $out[ 'idVersion_Current' ] : $Version  );
			return $Page;
		}
		return null;
	}

	public function ResetCache()
	{
		//Basically there better be a id, and txtBodyHTMLCache.
		//Get all the ids
		$this->DoSelect( 'id,idVersion_Current' );

		$ids = $this->m_rows;

		for( $i = 0; $i < count( $ids ); $i++ )
		{
			$nID = ( int ) $ids[ $i ][ 'id' ];
			$Version = ( int ) $ids[ $i ][ 'idVersion_Current' ];

			$History = new CTablePageHistory();
			$Item = $History->GetPage( $nID , $Version );
			$RCMarkup = new CRCMarkup( $Item[ 'txtBody' ] );
			$sRC = $RCMarkup->GetHTML();
			$data = array
				(
				'txtBodyHTMLCache' => '"'.addslashes( $sRC ).'"' ,
			);
			$this->DoUpdate( $nID , $data );
		}
	}

}

?>
