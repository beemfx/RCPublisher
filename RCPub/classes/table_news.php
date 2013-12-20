<?php

class CTableNews extends CTable
{

	public function CTableNews()
	{
		parent::CTable( 'tblNews' );
	}

	public function GetStory( $nID )
	{
		assert( 'integer' == gettype( $nID ) );
		$this->DoSelect( 'id,date_format(dtPosted, "%M %e, %Y") as dt,txtTitle,txtBody,txtBodyHTMLCache as formatted' , 'id='.$nID );

		$out = (0 == count( $this->m_rows )) ? null : $this->m_rows[ 0 ];
		$this->m_rows = null;
		return $out;
	}

	public function GetArchiveByYear( $year )
	{
		$this->DoSelect(
			'id,date_format(dtPosted, "%M") as dtMonth,date_format(dtPosted, "%b %d, %Y") as dt, txtTitle' , 'date_format(dtPosted, "%Y")="'.$year.'"' , 'dtPosted desc' );

		$rows = $this->m_rows;
		$this->m_rows = null;
		return $rows;
	}

	public function UpdateStory( $nID , $title , $body )
	{
		$Cached = new CRCMarkup( $body );

		$title = '"'.addslashes( $title ).'"';
		$body = '"'.addslashes( $body ).'"';
		$strCached = '"'.addslashes( $Cached->GetHTML() ).'"';

		$UserId = RCSession_GetUserProp( 'user_id' );

		$data = array
			(
			'idUser' => $UserId ,
			'txtTitle' => $title ,
			'txtBody' => $body ,
			'txtBodyHTMLCache' => $strCached ,
		);

		$this->DoUpdate( $nID , $data );
	}

	public function InsertStory( $title , $body )
	{
		$Cached = new CRCMarkup( $body );

		$title = '"'.addslashes( $title ).'"';
		$body = '"'.addslashes( $body ).'"';
		$strCached = '"'.addslashes( $Cached->GetHTML() ).'"';
		$UserId = RCSession_GetUserProp( 'user_id' );

		$insert = array
			(
			'idUser' => $UserId ,
			'txtTitle' => $title ,
			'txtBody' => $body ,
			'dtPosted' => 'now()' ,
			'txtBodyHTMLCache' => $strCached ,
		);

		$this->DoInsert( $insert );
	}

	public function ObtainRecentNews( $count )
	{
		//$res = $this->DoQuery('select txtTitle, date_format(dtPosted, "%M %e, %Y") as dt, txtBody from tblNews order by dtPosted desc limit '.$nNewsStories);
		$this->DoSelect( 'id' , '' , 'dtPosted desc' , ( int ) $count );
		return $this->m_rows;
	}

	public function GetYears()
	{
		$years = null;

		$nRows = $this->DoSelect( 'distinct(date_format(dtPosted, \'%Y\')) as dtYear' , '' , 'dtYear desc' );

		for( $i = 0; $i < $nRows; $i++ )
		{
			$years[ $i ] = $this->m_rows[ $i ][ 'dtYear' ];
		}

		$this->m_rows = null;

		return $years;
	}

	public function ResetCache()
	{
		//Basically there better be a id, and txtBodyHTMLCache.
		//Get all the ids
		$this->DoSelect( 'id' );

		$ids = $this->m_rows;

		for( $i = 0; $i < count( $ids ); $i++ )
		{
			$nID = ( int ) $ids[ $i ][ 'id' ];

			$this->DoSelect( 'txtBody' , 'id='.$nID );
			$sRC = $this->m_rows[ 0 ][ 'txtBody' ];
			$RCMarkup = new CRCMarkup( $sRC );
			$sRC = $RCMarkup->GetHTML();
			$data = array
				(
				'txtBodyHTMLCache' => '"'.addslashes( $sRC ).'"' ,
			);
			$this->DoUpdate( $nID , $data );
		}
	}

	public function DisplayArticle( $nID )
	{
		if( RCSession_IsPermissionAllowed( RCSESSION_MODIFYNEWS ) )
		{
			$strEditLink = sprintf(
				' [<a href=%s>Edit</a>]' , CreateHREF( PAGE_POSTNEWS , 'mode=edit&id='.$nID ) );
		}
		else
		{
			$strEditLink = '';
		}

		$story = $this->GetStory( ( int ) $nID );


		if( null != $story )
		{
			printf( "<h3>%s - <i><small>%s</small></i>%s</h3>\n" , $story[ 'txtTitle' ] , $story[ 'dt' ] , $strEditLink );

			print('<div class="news_body">' );
			print($story[ 'formatted' ] );
			print('</div>' );
		}
	}

	public function DisplayRecentArticles( $Count )
	{
		$Stories = $this->ObtainRecentNews( $Count );

		for( $i = 0; $i < count( $Stories ); $i++ )
		{
			$this->DisplayArticle( $Stories[ $i ][ 'id' ] );
		}

		if( 0 == count( $Stories ) )
		{
			echo 'No news.';
		}
	}

}

?>
