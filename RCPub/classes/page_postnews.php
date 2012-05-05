<?php
require_once('page_base.php');
require_once('php_ex.php');
require('table_news.php');

class CPostNewsPage extends CPageBase
{
	private $m_NewsTable;
		
	public function CPostNewsPage()
	{
		parent::CPageBase('Post News', 5);
		$this->m_NewsTable = null;
	}

	protected function DisplayContent()
	{
		$this->m_NewsTable = new CTableNews();
		
		if($_GET['mode']=='edit')
		{
			print("<h1>Updating News Item</h1>\n");
			print('<div style="margin:1em">');
			if($_POST['stage']==1)
			{
				$this->Display_Stage1_Edit();
			}
			else
			{
				$this->Dislpay_Stage0_Edit();
			}
			print('</div>');
		}
		else
		{
			print("<h1>Posting News</h1>\n");
			print('<div style="margin:1em">');
			if($_POST['stage']==1)
			{
				$this->Display_Stage1();
			}
			else
			{
				$this->Display_Stage0();
			}
			print('</div>');
		}
	}

	private function Display_Stage0()
	{
		?>
		<form action=<?php print CreateHREF(PAGE_POSTNEWS)?> method="post">
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
		
		$this->m_NewsTable->InsertStory($_POST['title'], $_POST['body']);
		
		print('<p>Notifying Twitter...</p>');
		$this->PostToTwitter($_POST['title'], $_POST['body']);

		print('<p>New item posted. Return <a href='.CreateHREF(PAGE_HOME).'>home</a>.</p>');
	}

		private function Dislpay_Stage0_Edit()
	{
		//Query for the information on the current article:
		$story = $this->m_NewsTable->GetStory((int)$_GET['id']);
		
		if(null == $story)
		{
			$this->ShowWarning('Trying to edit an invalid news article.');
			return;
		}

		?>
		<form action=<?php print CreateHREF(PAGE_POSTNEWS, 'mode=edit&id='.$_GET['id'])?> method="post">
		<input type="hidden" name="stage" value="1"/>
		<p><b>Article Title: </b><input type="text" name="title" value=<?php printf('"%s"', $story['txtTitle'])?> style="width:50%"/></p>
		<p><b>News Article (Limited HTML allowed):</b></br>
		<textarea style="height:200px;width:100%" name="body" cols="80" rows="20"><?php print $story['txtBody']?></textarea>
		</p>
		<center><input class="button" type="submit" value="Post"/></center>
		</form>
		<?php
	}

	private function Display_Stage1_Edit()
	{
		print '<p>Updating news item...</p>';		
		$this->m_NewsTable->UpdateStory((int)$_GET['id'], $_POST['title'], $_POST['body']);
		print('<p>New item updated. Return <a href='.CreateHREF(PAGE_HOME).'>home</a>.</p>');
	}

	function PostToTwitter($strTitle, $strBody)
	{
		return;
		
		//First form the message:
		$msg = strip_tags(sprintf("New web update, \"%s\" @ http://www.roughconcept.com: %s", $strTitle, $strBody));
		//If the sentance is way too, long, which it almost always will be, do a neat trim.
		if(strlen($msg)>140)
		{
			$msg = neat_trim($msg, 136);
		}

		$username = $this->GetGlobalSetting('txtTwitterUser');
		$password = $this->GetGlobalSetting('txtTwitterPwd');

		print("<p>The twitter status update: \"".$msg."\" is being posted.</p>\n");

		if( extension_loaded( 'curl' ) )
		{ // CURL available
			$session = curl_init();
			curl_setopt( $session, CURLOPT_URL, 'http://twitter.com/statuses/update.xml' );
			curl_setopt( $session, CURLOPT_POSTFIELDS, 'status='.urlencode($msg));
			curl_setopt( $session, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
			curl_setopt( $session, CURLOPT_HEADER, false );
			curl_setopt( $session, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt( $session, CURLOPT_USERPWD, $username.':'.$password );
			curl_setopt( $session, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $session, CURLOPT_POST, 1);
			$result = curl_exec ( $session ); // will be an XML message
			curl_close( $session );
		}
		else
		{
			$this->ShowWarning('cURL not availabe for posting Twitter updates.');
		}
	}

}

?>
