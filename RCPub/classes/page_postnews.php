<?php
require_once('page_base.php');
require('table_news.php');
class CPostNewsPage extends CPageBase
{

	private $m_NewsTable;

	public function CPostNewsPage()
	{
		parent::CPageBase( 'Post News' );
		$this->m_NewsTable = null;
	}

	protected function IsPageAllowed()
	{
		return RCSession_IsPermissionAllowed( RCSESSION_CREATENEWS );
	}
	
	protected function GetContentHeader()
	{
		if( isset( $_GET[ 'mode' ] ) && $_GET[ 'mode' ] == 'edit' )
			return "News Content Manager\n";
		else
			return print("Posting News\n" );
	}

	protected function DisplayContent()
	{
		$this->m_NewsTable = new CTableNews();

		if( isset( $_GET[ 'mode' ] ) && $_GET[ 'mode' ] == 'edit' )
		{
			print('<div style="margin:1em">' );
			if( RCWeb_GetPost( 'stage' ) == 1 )
			{
				$this->Display_Stage1_Edit();
			}
			else
			{
				$this->Dislpay_Stage0_Edit();
			}
			print('</div>' );
		}
		else
		{
			print('<div style="margin:1em">' );
			if( RCWeb_GetPost( 'stage' ) == 1 )
			{
				$this->Display_Stage1();
			}
			else
			{
				$this->Display_Stage0();
			}
			print('</div>' );
		}
	}

	private function Display_Stage0()
	{
		?>
		<form action=<?php print CreateHREF( PAGE_POSTNEWS ) ?> method="post">
			<input type="hidden" name="stage" value="1"/>
			<p><b>Article Title: </b><input type="text" name="title" style="width:50%"/></p>
			<p><b>News Article (Limited HTML allowed):</b></br>
				<textarea style="height:200px;width:100%" name="body" cols="80" rows="20"></textarea>
			</p>
			<center><input class="button" type="submit" value="Post"/></center>
		</form>
		<?php
	}

	private function Display_Stage1()
	{
		print '<p>Posting news item...</p>';

		//The news item needs to be modifed so that it can be stored in html
		//and retrived, and displayed.

		$this->m_NewsTable->InsertStory( RCWeb_GetPost( 'title' ) , RCWeb_GetPost( 'body' ) );

		print('<p>New item posted. Return <a href='.CreateHREF( PAGE_HOME ).'>home</a>.</p>' );
	}

	private function Dislpay_Stage0_Edit()
	{
		//Query for the information on the current article:
		$story = $this->m_NewsTable->GetStory( ( int ) $_GET[ 'id' ] );

		if( null == $story )
		{
			RCError_PushError( 'Trying to edit an invalid news article.' , 'error' );
			return;
		}
		?>
		<form action=<?php print CreateHREF( PAGE_POSTNEWS , 'mode=edit&id='.$_GET[ 'id' ] ) ?> method="post">
			<input type="hidden" name="stage" value="1"/>
			<p><b>Article Title: </b><input type="text" name="title" value=<?php printf( '"%s"' , $story[ 'txtTitle' ] ) ?> style="width:50%"/></p>
			<p><b>News Article (Limited HTML allowed):</b></br>
				<textarea style="height:200px;width:100%" name="body" cols="80" rows="20"><?php print $story[ 'txtBody' ] ?></textarea>
			</p>
			<center><input class="button" type="submit" value="Post"/></center>
		</form>
		<?php
	}

	private function Display_Stage1_Edit()
	{
		print '<p>Updating news item...</p>';
		$this->m_NewsTable->UpdateStory( ( int ) $_GET[ 'id' ] , RCWeb_GetPost( 'title' ) , RCWeb_GetPost( 'body' ) );
		print('<p>New item updated. Return <a href='.CreateHREF( PAGE_HOME ).'>home</a>.</p>' );
	}

}
?>
