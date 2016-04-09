<?php
/**
 * Cut string to n symbols and add delim but do not break words.
 *
 * Example:
 * <code>
 *  $string = 'this sentence is way too long';
 *  echo neat_trim($string, 16);
 * </code>
 *
 * Output: 'this sentence is...'
 *
 * @access public
 * @param string string we are operating with
 * @param integer character count to cut to
 * @param string|NULL delimiter. Default: '...'
 * @return string processed string
 **/
function neat_trim($str, $n, $delim='...') {
   $len = strlen($str);
   if ($len > $n) {
       preg_match('/(.{' . $n . '}.*?)\b/', $str, $matches);
       return rtrim($matches[1]) . $delim;
   }
   else {
       return $str;
   }
}

function pretty_up_text($str)
{
	//The main idea is to prepare text for output, we are chaning all
	//newlines to a doble break, reducing all headers to h4 or less,
	//changing the contraction apostrophe, making quotes look like
	//real quotes. Use for formatting b2evo posts.

	//We shall temporarily remove quotes from html tags:

	$pattern = array('/=[\s]*"([^"]*)"/');
	$replace = array('=_QUOTE_$1_QUOTE_');
	$str = preg_replace($pattern, $replace, $str);

	$pattern = array("/(\r?\n)+/",      '/<h[0-3][^>]*>/', '/<\/h[0-3]>/', "/([A-Za-z])'([A-Za-z])/", "/s'[. ]/", '/"([a-zA-Z0-9])/', '/([a-zA-Z0-9.])"(.?)/');
	$replace = array("<br /><br />\n",  '<h4>',            '</h4>',        '$1&#8217;$2',             's&#8217;$1', '&#8220;$1',        '$1&#8221;$2');
	$str = preg_replace($pattern, $replace, $str);

	//Put quuotes back in:
	$pattern = array('/_QUOTE_/');
	$replace = array('"');
	$str = preg_replace($pattern, $replace, $str);

	//Also taking on beginning paragraph symbols. May not be necessary if
	//There were already paragraph tags used in the original post.
	return $str;
}
?>
