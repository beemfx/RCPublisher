<?php

class CRCMarkup
{
	public function CRCMarkup($sText)
	{	
		$this->m_sText = $sText;
		self::$m_db = RCSql_GetDb();
		$this->ResolveTags();
	}
	
	public function GetHTML()
	{
		return $this->m_sText;
	}
	
	private function ResolveTags()
	{
		//Process content is a helper function that takes the $m_strContent parameter, and
		//processes it to be displayed.
		$find = array('/{{year}}/');
		$replace = array(date('Y'));
		$this->m_sText = preg_replace($find, $replace, $this->m_sText);
		$this->Texturize();
		$this->m_sText = $this->ProcessFileTags($this->m_sText);
		$this->m_sText = $this->ProcessBlogTags($this->m_sText);
		$this->m_sText = $this->ProcessContactTags($this->m_sText);
		$this->m_sText = $this->ProcessLinkTags($this->m_sText);
		//T'would be nice to process note tags before internal links but the 
		//[[ and ]] interfere, so we have to do it before, which means we can't 
		//put internal links inside of notes.
		$this->m_sText = $this->ProcessNoteTags($this->m_sText);
		$this->m_sText = $this->ProcessInternalLinks($this->m_sText);
	}
	
	private function Texturize()
	{
		//HTML tags:
		$this->m_sText = strip_tags( $this->m_sText, '<div><center><small><q><s>');
		//Quotes:
		//One problem may be that we want to support <div>.
		$this->m_sText = preg_replace('/\"([^\"]+)\"/s', '<q>$1</q>', $this->m_sText);
		//Bullets (:*)
		$this->m_sText = preg_replace('/\:\*([^\n\r]*)(\r?\n|$)/s', '<ul style="margin:0 2em;padding:0;border:0"><li>$1</li></ul>', $this->m_sText);
		//Bold italics:
		$this->m_sText = preg_replace('/\'\'\'\'((([^\']+)(\'[^\'])?)+)\'\'\'\'/s', '<b><em>$1</em></b>', $this->m_sText);
		//Bold:
		$this->m_sText = preg_replace('/\'\'\'((([^\']+)(\'[^\'])?)+)\'\'\'/s', '<b>$1</b>', $this->m_sText);
		//Italics:
		$this->m_sText = preg_replace('/\'\'((([^\']+)(\'[^\'])?)+)\'\'/s', '<em>$1</em>', $this->m_sText);
		//At this point any single quotes should be apostrophes:
		$this->m_sText = preg_replace('/\'/s', '&rsquo;', $this->m_sText);
		//Headers (and get rid of newlines following them):
		$this->m_sText = preg_replace('/=====([^=]+)=====\r?\n?/s' , '<h4>$1</h4>', $this->m_sText);
		$this->m_sText = preg_replace('/====([^=]+)====\r?\n?/s' , '<h3>$1</h3>', $this->m_sText);
		$this->m_sText = preg_replace('/===([^=]+)===\r?\n?/s' , '<h2>$1</h2>', $this->m_sText);
		$this->m_sText = preg_replace('/==([^=]+)==\r?\n?/s' , '<h1>$1</h1>', $this->m_sText);
		//Newlines:
		$this->m_sText = preg_replace('/\r?\n/s', "<br />\n", $this->m_sText);
	}
	
	private function ResolveSpecialSymbols()
	{
		
	}
	
	static private function PIL_Replace($matches)
	{
		//The first thing to do is go through all the built in links, then try to do a page link.
		$strRef = '';
		switch($matches[1])
		{
			case 'home': $strRef = CreateHREF(PAGE_HOME);break;
			case 'contact': $strRef = CreateHREF(PAGE_CONTACT);break;
			case 'login': $strRef = CreateHREF(PAGE_LOGIN);break;
			case 'news': $strRef = CreateHREF(PAGE_NEWS, 'archive');break;
			case 'pages': $strRef = CreateHREF(PAGE_PAGE); break;
			default: $strRef = CreateHREF(PAGE_PAGE, 'p='.$matches[1]);
		}
			
		return sprintf('<a href=%s>%s</a>', $strRef, isset($matches[2])?$matches[2]:$matches[1]);
	}
		
	static protected function ProcessInternalLinks($strIn)
	{
		
		//ProcessInternalLinks is a regular expression replacement function that
		//attemps to find internal links, and replace them appropriately.
		//Internal links are in the form [[link link text]] where link text is optional.
		//Global links are attempted to be resolved first, then the link is assumed to be
		//a page link.
		
		return preg_replace_callback('/\[\[([A-Za-z0-9_]+)( [^\]\[]*)?\]\]/', "CRCMarkup::PIL_Replace", $strIn);
	}
	
	static private function FormImageTag($Info,$Parms)
	{
		$nNumParms = count($Parms);
		
		$sPoseString = 'display:inline-block';
		$sSizeString = '';
		$sDescString = '';	
		$sBlockType = 'image_block';
		
		$bNoLink = false;
		$bUseThumb = true;
		
		for($i = 0; $i < $nNumParms; $i++)
		{
			if(preg_match('/[0-9\.]?(%|em|px|cm|pt)$/', $Parms[$i]))
			{
				//It is a special case if the width is 100%, we want to show
				//a full image wihtout padding (should this just be a separate
				//paramter?)
				if('100%' == $Parms[$i])
					$sBlockType = 'image_block_full';
				//if(0 == strlen($sSizeString))
					$sSizeString = 'width:'.$Parms[$i].';';
				//else
				//	$sSizeString .= 'height:'.$Parms[$i].';';
			}
			else if(preg_match('/^nolink$/', $Parms[$i]))
			{
				$bNoLink = true;
				$bUseThumb = false;
			}
			else if(preg_match('/^right$|^left$/' , $Parms[$i]))
				$sPoseString='float:'.$Parms[$i].';';
			else if(preg_match('/^center$/' , $Parms[$i]))
				$sPoseString='margin-left:auto;margin-right:auto;';
			else
				$sDescString = $Parms[$i];
		}
		
		$bHasCaption = strlen($sDescString) > 0;
		
		$sCaptionBlock = $bHasCaption ? sprintf('<div class="image_caption">%s</div>', $sDescString) : '';
		
		$sLinkStart = $bNoLink ? '' : sprintf('<a href="%s">', $Info['url']);
		$sLinkEnd   = $bNoLink ? '' : '</a>';
		
		
		return sprintf('<div class="%s" style="%s%s">%s<img src="%s%s" style="width:100%%"/>%s%s</div>', 
					$sBlockType, $sSizeString, $sPoseString, $sLinkStart, $Info['url'], $bUseThumb?'.thumb.jpg':'', $sLinkEnd, $sCaptionBlock);
	}
	
	static private function PFT_Replace($matches)
	{	
		$F = new CFileManager(self::$m_db);
		$Info = $F->ResolveFileInfo($matches[1]);
		
		if(null == $Info)
		{
			return 'INVALID FILE '.$matches[1];
		}
		
		if(preg_match('/image\/.*/' , $Info['type']))
		{
			$F->InsureThumbFor($matches[1]);
			$Atts = isset($matches[3]) ? $matches[3] : '';
			return self::FormImageTag($Info, preg_split('/\|/' , $Atts));
		}
				
		return sprintf('<a href="%s">%s</a>', $Info['url'], strlen($matches[3])>0?$matches[3]:$Info['filename']);
	}
		
	static protected function ProcessFileTags($strIn)
	{
		//ProcessFile tags turns a file tag into a link or embeds the file.
		return preg_replace_callback('/\[\[file:([^ \]\[]+)?( ([^\]\]]*))?\]\]/', "CRCMarkup::PFT_Replace", $strIn);
	}
	
	static private function PBT_Replace($matches)
	{
		$BlogDesc = strlen($matches[3]) > 0 ? $matches[3] : $matches[1];
		$Settings = new CTableSettings();
		
		$sLink = sprintf(preg_replace('/{{slug}}/', $matches[1], $Settings->GetSetting('txtBlogLink')));
		return sprintf('<a href="%s">%s</a>', $sLink, $BlogDesc);
	}
		
	static protected function ProcessBlogTags($strIn)
	{
		//ProcessFile tags turns a file tag into a link or embeds the file.
		
		return preg_replace_callback('/\[\[blog:([^ \]\[]+)?( ([^\]\]]*))?\]\]/', "CRCMarkup::PBT_Replace", $strIn);
	}
	
	static private function PLT_Replace($matches)
	{
		$BlogDesc = strlen($matches[4]) > 0 ? $matches[4] : $matches[2];
		
		global $GLOBAL_SETTINGS_BLOGURL;
		$sLink = sprintf('%s', $matches[2]);
		return sprintf('<a href="%s">%s</a>', $sLink, $BlogDesc);
	}
		
	static protected function ProcessLinkTags($strIn)
	{
		//ProcessFile tags turns a file tag into a link or embeds the file.
		
		return preg_replace_callback('/\[\[(link|site):([^ \]\[]+)?( ([^\]\]]*))?\]\]/', "CRCMarkup::PLT_Replace", $strIn);
	}
	
	static private function PCT_Replace($matches)
	{
		$Desc = strlen($matches[4]) > 0 ? $matches[4] : $matches[1].':'.$matches[2];
				
		$sLink = CreateHREF(PAGE_CONTACT, 'to='.$matches[2]);
		
		return sprintf('<a href=%s>%s</a>', $sLink, $Desc);
	}
		
	static protected function ProcessContactTags($strIn)
	{
		//ProcessFile tags turns a file tag into a link or embeds the file.
		
		return preg_replace_callback('/\[\[(contact):([^ \]\[]+)?( ([^\]\]]*))?\]\]/', "CRCMarkup::PCT_Replace", $strIn);
	}
	
	static private function PNT_Replace($matches)
	{
		$Atts = preg_split('/\|/' , $matches[2]);
		
		$sData = $matches[3];
		$sPos = '';
		$sSize  = '';
		
		for($i=0; $i<count($Atts); $i++)
		{
			if(preg_match('/([0-9\.]?)(%|em|px|cm|pt)$/', $Atts[$i]))
			{
				$sSize = 'width:'.$Atts[$i].';';
			}
			if(preg_match('/^(left|right)$/', $Atts[$i]))
			{
				$sPos  ='float:'.$Atts[$i].';';
			}
			else
			{
				//$sData = $Atts[$i];
			}
		}
		
		return sprintf('<div class="note_block" style="%s%s">%s</div>', $sPos, $sSize, $sData);//ASSIDE';//$matches[2];
	}
		
	static protected function ProcessNoteTags($strIn)
	{
		//ProcessFile tags turns a file tag into a link or embeds the file.
		return preg_replace_callback('/\[\[note( ([^\]\[]+))?\]\]([^\]\[]*)\[\[\/note\]\]/', "CRCMarkup::PNT_Replace", $strIn);
	}
	
	var $m_sText;
	static $m_db;
}

?>
