<?php
require_once('page_base.php');

class CInstallPage extends CPageBase
{
	public function CInstallPage()
	{
		parent::CPageBase('Install', 5);
	}

	protected function DisplayContent()
	{
		print("<h1>Installing...</h1>\n");
	}

}
?>
