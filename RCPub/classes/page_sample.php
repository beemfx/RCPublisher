<?php
/*******************************************************************************
 * File:   sample_page.php
 * Class:  CSampePage
 * Purpose: Page that gives a sample layout for developing other pages.
 *
 * Copyright (C) 2009 Blaine Myers
 ******************************************************************************/
require_once('page_base.php');

class CPageSample extends CPageBase
{
	public function CPageSample()
	{
		parent::CPageBase('Sample');
	}

	protected function DisplayContent()
	{
		print('<p>The content goes here</p>');
	}
        
        protected function IsPageAllowed()
        {
            return true;
        }
}
?>
