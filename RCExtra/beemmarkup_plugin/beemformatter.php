<?php

class CBeemFormatter {

    private $m_content = '';
    private $m_strMediaDir = '';
    private $m_strImageFormat ='';

    public function CBeemFormatter(& $content, $strMediaDir, $strImgBlock) {
        $this->m_content = & $content;
        $this->m_strMediaDir = $strMediaDir;
        $this->m_strImageFormat = $strImgBlock;
    }

    public function GetText() {
        return $this->m_content;
    }

    public function FormatIt() {
        $this->BasicTextFormatting();
        $this->TagFormatting();
    }

    private function IMGTag_CB($matches) {
        //Find all the parameters
        preg_match('/([^\|]*)\|?([^\|]*)\|?([^\|]*)\|?([^\|]*)\|?([^\|]*)/', $matches[1], $m);
        $file = $m[1];
        $desc = $m[2];
        $fmt = $m[3];
        $alt = $m[4];
        $link = $m[5];
        
        if(1 != preg_match('/http:\/\/.*/', $file))
        {
            $file = $this->m_strMediaDir.$file;
        }

        if(strlen($link) < 1)
        {
            $link = $file;
        }

        //echo 'Filename: ' . $file . '<br/>';
        //echo 'Desc: ' . $desc . '<br/>';
        //echo 'Format: ' . $fmt . '<br/>';
        //echo 'Alt Name: ' . $alt . '<br/>';

        $style = '';
        if(preg_match('/right|left/', $fmt, $m))
        {
            $style .= 'display:block;float:'.$m[0].';';
        }

        if(preg_match('/(\d*)(em|px|cm|%)/', $fmt, $m))
        {
            $style .= 'width:'.$m[0].';';
        }

        $s = array('__SRC__', '__DESC__', '__ALT__', '__STYLE__', '__LINK__');
        $r = array($file, $desc, $alt, $style, $link);
        return str_replace($s, $r, $this->m_strImageFormat);
    }

     private function LINKTag_CB($matches) {
        //Find all the parameters
        preg_match('/([^\|]*)\|?([^\|]*)/', $matches[1], $m);
        $url = $m[1];
        $name = $m[2];

         if(!preg_match('/https?:\/\/.*/', $url))
        {
            $url = 'http://'.$url;
        }

        if(strlen($name) < 1)
        {
            $name = $url;
        }

        return sprintf('<a href="%s">%s</a>', $url, $name);
    }

    private function TagFormatting() {
        $c = & $this->m_content;

        //All tags start with [[ and end with ]]. Pipes (|) are used to
        //separate parameters.

        //Link tags are first, in case we want a link inside of another tag.
        $p = '/\[\[link\s*([^\]]*)\s*\]\]/';
        $c = preg_replace_callback($p, array($this, 'LINKTag_CB'), $c);

        //Process the image tag sytax [[img (filename)|(desc)|(opts)|(alt name)]]
        $p = '/\[\[img\s*([^\]]*)\s*\]\]/';
        $c = preg_replace_callback($p, array($this, 'IMGTag_CB'), $c);
    }

    /* BasicTextFormatting - This does the initial formatting, it replaces common
     * html special characters with their appropriate subsitutions. Things like
     * < and > are replaced.
     */

    private function BasicTextFormatting() {
        $c = & $this->m_content;

        //First thing to replace is &'s because they will be used in other
        //changes.
        //$p = array('&');
        //$r = array('&amp;');
        //$c = str_replace($p, $r, $c);
        //Since < and > are not used in beem markup language. We replace them
        //with the appropriate HTML chars.
        $p = array('/</', '/>/');
        $r = array('&lt;', '&gt;');
        $c = preg_replace($p, $r, $c);

        //Let's get ready to do some formatting, it will hep to temorarily
        //chagne some sytanx.
        $p = array("/'''/", "/''/");
        $r = array('¶BOLD_TAG¶', '¶EMPH_TAG¶');
        $c = preg_replace($p, $r, $c);

        //With that done, we may now actually change the text.
        $p = array("/¶BOLD_TAG¶([^¶]*)¶BOLD_TAG¶/", "/¶EMPH_TAG¶([^¶]*)¶EMPH_TAG¶/");
        $r = array('<b>\1</b>', '<em>\1</em>');
        $c = preg_replace($p, $r, $c);

       //Equal signs are for headres.
        $p = array("/===/");
        $r = array('¶HEAD2_TAG¶');
        $c = preg_replace($p, $r, $c);

        $p = array("/==/");
        $r = array('¶HEAD1_TAG¶');
        $c = preg_replace($p, $r, $c);

        //With that done, we may now actually change the text.
        $p = array("/¶HEAD1_TAG¶([^¶]*)¶HEAD1_TAG¶/", "/¶HEAD2_TAG¶([^¶]*)¶HEAD2_TAG¶/");
        $r = array('<h1>\1</h1>', '<h2>\1</h2>');
        $c = preg_replace($p, $r, $c);

        //We change quotes since they are not used in any Beem Markup syntax.
        //Also we have handled all other quotes, so let's turn single quotes
        //into apostrophes.
        $p = array('/(")([^"]*)(")/', "/'/");
        $r = array('<q>\2</q>', '&#8217;');
        $c = preg_replace($p, $r, $c);

        //Finally we add paragrpahs.
        $p = array("/(\r?\n){2}/");
        $r = array("\n</p>\n<p>\n");
        $c = "\n<p>\n" . preg_replace($p, $r, $c) . "\n</p>\n";

        //And we don't want any paragraphs around headers so we eliminate
        //them.
        $p = array("/<p>[\\s]*(<h[12]>)/");
        $r = array("\\1");
        $c = preg_replace($p, $r, $c);

        //And we dont' want paragraphs around images that are stading by
        //themselves so we wipe them.
        $p = array("/<p>[\\s]*(\[\[img)/");
        $r = array("\\1");
        $c = preg_replace($p, $r, $c);
    }

    /*
      function CreateLists($in)
      {
      $out = $in;
      //Unordered lists (*)
      $out = preg_replace_callback("/((\r?\n|^)\\s*[*](.*))+/", 'ListCallbackUnordered', $out);
      //Ordered lists (#)
      $out = preg_replace_callback("/((\r?\n|^)\\s*[#](.*))+/", 'ListCallbackOrdered', $out);
      return $out;
      }


      function ListCallbackUnordered($matches) {
      return '<ul>'.preg_replace('/\s*[*](.*)/', '<li>$1</li>', $matches[0]).'</ul>';
      }

      function ListCallbackOrdered($matches){
      return '<ol>'.preg_replace('/\s*[#](.*)/', '<li>$1</li>', $matches[0]).'</ol>';
      }


      function MakeTextPretty($text) {
      $out = $text;

      //First create lists:
      $out = CreateLists($out);

      //First get rid of \r that may have been added by windows manchines,
      //and change newlines to <br />'s.
      //Some of the unecessary breaks will be removed later on, such as around
      //various tags like headers, lists and tables.
      $out = preg_replace(
      array("/\r/", "/\n/"),
      array('',    "<br />"),
      $out);

      //Now, for any quotes that might be in tags, we'll need to
      //temporarily get rid of.
      $out = preg_replace_callback('/(<[A-Za-z][^>]*>)/', 'TagQuoteCallback', $out);

      //Let's texturize some things mainly quotes and apostrophes (we also won't do headers higher than 4):
      $pattern = array('/<h[1-3]([^>]*)>/', '/<\/h[0-3]>/', "/([A-Za-z])'([A-Za-z])/", "/s'([.\s])/",   '/"([a-zA-Z0-9])/', '/([a-zA-Z0-9.?!])"([.?!]?)/');
      $replace = array('<h4$1>',            '</h4>',        '$1&#8217;$2',             's&#8217;$1',    '&#8220;$1',        '$1&#8221;$2');
      $out = preg_replace($pattern, $replace, $out);


      //Restore quotes:
      $out = preg_replace(array('/__DQUOTE__/', '/__SQUOTE__/'), array('"', '\''), $out);


      $aaNoBRTags = array(
      '<\/?h[1-6][^>]*>', //Headers
      '<\/?p[^>]*>',      //Paragraphs
      '<\/?li[^>]*>',     //List items
      '<\/?ul[^>]*>',                 //Lists
      '<hr[^\/>]*\/?>',   //HR linebreaks.

      );

      //Make an array of all the tags that we don't want to have line breaks nearby.
      $aaNoBRList = array(
      MakeTagRegex('p'),
      MakeTagRegex('h[1-6]'),
      MakeTagRegex('li'),
      MakeTagRegex('ol'),
      MakeTagRegex('ul'),
      MakeTagRegex('hr'),
      MakeTagRegex('table'),
      MakeTagRegex('tr'),
      MakeTagRegex('td'),
      MakeTagRegex('th')
      );

      //Form all these tags into a string:
      $strNoBRList='';
      $nLen = count($aaNoBRList);
      for($i=0; $i<$nLen; $i++) {
      $strNoBRList.=$aaNoBRList[$i];
      if($i != ($nLen-1)) {
      $strNoBRList.='|';
      }
      }

      //Get rid of line breaks occurring after certain tags:
      $strSearch = sprintf('/(%s)\s*(%s)+/', $strNoBRList, MakeTagRegex('br'));
      $out = preg_replace($strSearch, '$1', $out);
      //Get rid of line breaks occurring before certain tags:
      $strSearch = sprintf('/(%s)+\s*(%s)/', MakeTagRegex('br'), $strNoBRList);
      $out = preg_replace($strSearch, '$2', $out);


      return $out;
      }



      function TagQuoteCallback($matches) {
      return preg_replace(array('/["]/', '/[\']/'), array('__DQUOTE__', '__SQUOTE__'), $matches[0]);
      }

      function MakeTagRegex($in) {
      return '<\/?\s*'.$in.'[^>]*>';
      }
     */
}

?>
