<?php
/***********************************************************************

  Copyright (C) 2002-2005  Rickard Andersson (rickard@punbb.org)
  Copyright (C) 2005-2006  LAKostis (lakostis@mozilla-russia.org)

  This file is part of Russian Mozilla Team PunBB modification.

  PunBB is free software; you can redistribute it and/or modify it
  under the terms of the GNU General Public License as published
  by the Free Software Foundation; either version 2 of the License,
  or (at your option) any later version.

  PunBB is distributed in the hope that it will be useful, but
  WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston,
  MA  02111-1307  USA

************************************************************************/

// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;

// Global variables
/* regular expression to match nested BBCode LIST tags
'%
\[list                # match opening bracket and tag name of outermost LIST tag
(?:=([1a*]))?+        # optional attribute capture in group 1
\]                    # closing bracket of outermost opening LIST tag
(                     # capture contents of LIST tag in group 2
  (?:                 # non capture group for either contents or whole nested LIST
	[^\[]*+           # unroll the loop! consume everything up to next [ (normal *)
	(?:               # (See "Mastering Regular Expressions" chapter 6 for details)
	  (?!             # negative lookahead ensures we are NOT on [LIST*] or [/LIST]
		\[list        # opening LIST tag
		(?:=[1a*])?+  # with optional attribute
		\]            # closing bracket of opening LIST tag
		|             # or...
		\[/list\]     # a closing LIST tag
	  )               # end negative lookahead assertion (we are not on a LIST tag)
	  \[              # match the [ which is NOT the start of LIST tag (special)
	  [^\[]*+         # consume everything up to next [ (normal *)
	)*+               # finish up "unrolling the loop" technique (special (normal*))*
  |                   # or...
	(?R)              # recursively match a whole nested LIST element
  )*                  # as many times as necessary until deepest nested LIST tag grabbed
)                     # end capturing contents of LIST tag into group 2
\[/list\]             # match outermost closing LIST tag
%iex' */
$re_list = '%\[list(?:=([1a*]))?+\]((?:[^\[]*+(?:(?!\[list(?:=[1a*])?+\]|\[/list\])\[[^\[]*+)*+|(?R))*)\[/list\]%i';

// Here you can add additional smilies if you like (please note that you must escape singlequote and backslash)
$smiley_text = [
	':)',
	'=)',
	':|',
	'=|',
	':(',
	'=(',
	':D',
	'=D',
	':o',
	':O',
	'=o',
	'=O',
	';)',
	':/',
	'=/',
	':P',
	'=P',
	':lol:',
	':mad:',
	':rolleyes:',
	':cool:',
	':blush:',
	':usch:',
	':angry:',
	':sick:',
	':music:',
	':cry:',
	':whistle:',
	':beer:',
	':angel:',
	':rock:',
	':tongue2:',
	':zzz:',
	':iron:',
	':dumb:',
	':puss:',
	':heart:',
	':couple:',
	':whiteflag:',
	':offtopic:'
];
$smiley_img = [
	'smile.png',
	'smile.png',
	'neutral.png',
	'neutral.png',
	'sad.png',
	'sad.png',
	'big_smile.png',
	'big_smile.png',
	'yikes.png',
	'yikes.png',
	'yikes.png',
	'yikes.png',
	'wink.png',
	'hmm.png',
	'hmm.png',
	'tongue.png',
	'tongue.png',
	'lol.png',
	'mad.png',
	'roll.png',
	'cool.png',
	'blush.gif',
	'usch.gif',
	'angry.gif',
	'sick.gif',
	'music.gif',
	'cry.gif',
	'whistle.gif',
	'beer.gif',
	'angel.gif',
	'rock.gif',
	'tongue2.gif',
	'zzz.gif',
	'iron.gif',
	'dumb.gif',
	'puss.gif',
	'heart.gif',
	'couple.gif',
	'whiteflag.gif',
	'offtopic.gif'
];
$smiley_limit = 17;

// Uncomment the next row if you add smilies that contain any of the characters &"'<>
//$smiley_text = array_map('pun_htmlspecialchars', $smiley_text);

$browser_text = [
	'[firefox]',
	'[fx]',
	'[thunderbird]',
	'[tb]',
	'[seamonkey]',
	'[sm]',
	'[mozilla]',
	'[mz]',
	'[fennec]',
	'[fn]',
	'[sunbird]',
	'[sb]',
	'[songbird]',
	'[sgb]',
	'[camino]',
	'[bugzilla]',
	'[nvu]',
	'[k-meleon]',
	'[km]',
	'[flock]',
	'[fl]',
	'[aurora]',
	'[nightly]',
	'[ie]',
	'[opera]',
	'[chrome]',
	'[chromium]',
	'[safari]',
	'[netscape]',
	'[ns]',
	'[sunrise]',
	'[konqueror]',
	'[arora]',
	'[windows]',
	'[linux]',
	'[macos]',
	'[android]'
];
$browser_img = [
	'firefox.png',
	'firefox.png',
	'thunderbird.png',
	'thunderbird.png',
	'seamonkey.png',
	'seamonkey.png',
	'mozilla.png',
	'mozilla.png',
	'fennec.png',
	'fennec.png',
	'sunbird.png',
	'sunbird.png',
	'songbird.png',
	'songbird.png',
	'camino.png',
	'bugzilla.png',
	'nvu.png',
	'k-meleon.png',
	'k-meleon.png',
	'flock.png',
	'flock.png',
	'aurora.png',
	'nightly.png',
	'ie9.png',
	'opera.png',
	'chrome.png',
	'chromium.png',
	'safari.png',
	'netscape.png',
	'netscape.png',
	'sunrise.png',
	'konqueror.png',
	'arora.png',
	'windows.png',
	'linux.png',
	'macos.png',
	'android.png'
];
$browser_limit = 3;

//
// Make sure all BBCodes are lower case and do a little cleanup
//
function preparse_bbcode($text, &$errors, $is_signature = false)
{
	global $lang_common;

	// If the message contains a code tag we have to split it up (text within [code][/code] shouldn't be touched)
	if (strpos($text, '[code]') !== false && strpos($text, '[/code]') !== false)
	{
		list($inside, $outside) = split_text($text, '[code]', '[/code]');
		$outside = array_map('ltrim', $outside);
		$text = implode('<">', $outside);
	}

	// Change all simple BBCodes to lower case
	$a = ['[B]', '[I]', '[U]', '[/B]', '[/I]', '[/U]'];
	$b = ['[b]', '[i]', '[u]', '[/b]', '[/i]', '[/u]'];
	$text = str_replace($a, $b, $text);

	// Do the more complex BBCodes (also strip excessive whitespace and useless quotes)
	$a = [	'#\[url=("|\'|)(.*?)\\1\]\s*#i',
				'#\[url\]\s*#i',
				'#\s*\[/url\]#i',
				'#\[email=("|\'|)(.*?)\\1\]\s*#i',
				'#\[email\]\s*#i',
				'#\s*\[/email\]#i',
				'#\[img\]\s*(.*?)\s*\[/img\]#is',
				'#\[colou?r=("|\'|)(.*?)\\1\](.*?)\[/colou?r\]#is'];

	$b = [	'[url=$2]',
				'[url]',
				'[/url]',
				'[email=$2]',
				'[email]',
				'[/email]',
				'[img]$1[/img]',
				'[color=$2]$3[/color]'];

	if (!$is_signature)
	{
		// For non-signatures, we have to do the quote and code tags as well
		$a[] = '#\[quote=(&quot;|"|\'|)(.*?)\\1\]\s*#i';
		$a[] = '#\[quote\]\s*#i';
		$a[] = '#\s*\[/quote\]\s*#i';
		$a[] = '#\[code\][\r\n]*(.*?)\s*\[/code\]\s*#is';
		$a[] = '#\[spoiler=(&quot;|"|\'|)(.*?)\\1\]\s*#i';
		$a[] = '#\[spoiler\]\s*#i';
		$a[] = '#\s*\[/spoiler\]\s*#i';
		$a[] = '#\[noindex\]\s*#i';
		$a[] = '#\s*\[/noindex\]\s*#i';

		$b[] = '[quote=$1$2$1]';
		$b[] = '[quote]';
		$b[] = '[/quote]'."\n";
		$b[] = '[code]$1[/code]'."\n";
		$b[] = '[spoiler=$1$2$1]';
		$b[] = '[spoiler]';
		$b[] = '[/spoiler]'."\n";
		$b[] = '[noindex]';
		$b[] = '[/noindex]'."\n";
	}

	// Run this baby!
	$text = preg_replace($a, $b, $text);

	if (!$is_signature)
	{
		$overflow = check_tag_order($text, $error);

		if ($error)
			// A BBCode error was spotted in check_tag_order()
			$errors[] = $error;
		else if ($overflow)
			// The quote depth level was too high, so we strip out the inner most quote(s)
			$text = substr($text, 0, $overflow[0]).substr($text, $overflow[1], strlen($text) - $overflow[0]);
	}
	else
	{
		global $lang_prof_reg;

		if (preg_match('#\[quote=(&quot;|"|\'|)(.*)\\1\]|\[quote\]|\[/quote\]|\[code\]|\[/code\]|\[spoiler=(&quot;|"|\'|)(.*)\\1\]|\[spoiler\]|\[/spoiler\]|\[noindex\]|\[/noindex\]#i', $text))
			message($lang_prof_reg['Signature quote/code']);
	}

	// If we split up the message before we have to concatenate it together again (code tags)
	if (isset($inside))
	{
		$outside = explode('<">', $text);
		$text = '';

		$num_tokens = count($outside);

		for ($i = 0; $i < $num_tokens; ++$i)
		{
			$text .= $outside[$i];
			if (isset($inside[$i]))
			{
				$num_lines = (substr_count($inside[$i], "\n") + 3) * 1.5;
				$height_str = $num_lines > 35 ? '35em' : $num_lines.'em';
				$text .= '[code]'.$inside[$i].'[/code]';
			}
		}
	}

	return trim($text);
}


//
// Parse text and make sure that [code] and [quote] syntax is correct
//
function check_tag_order($text, &$error)
{
	global $lang_common;

	// The maximum allowed quote depth
	$max_depth = 3;

	$cur_index = 0;
	$q_depth = 0;
	$s_depth = 0;

	while (true)
	{
		// Look for regular code and quote tags
		$c_start = strpos($text, '[code]');
		$c_end = strpos($text, '[/code]');
		$q_start = strpos($text, '[quote]');
		$q_end = strpos($text, '[/quote]');
		$s_start = strpos($text, '[spoiler]');
		$s_end = strpos($text, '[/spoiler]');
		$n_start = strpos($text, '[noindex]');
		$n_end = strpos($text, '[/noindex]');

		// Look for [quote=username] style quote tags
		if (preg_match('#\[quote=(&quot;|"|\'|)(.*)\\1\]#sU', $text, $matches))
			$q2_start = strpos($text, $matches[0]);
		else
			$q2_start = 65536;

		// Look for [spoiler=text] style spoiler tags
		if (preg_match('#\[spoiler=(&quot;|"|\'|)(.*)\\1\]#sU', $text, $matches2))
			$s2_start = strpos($text, $matches2[0]);
		else
			$s2_start = 65536;

		// Deal with strpos() returning false when the string is not found
		// (65536 is one byte longer than the maximum post length)
		if ($c_start === false) $c_start = 65536;
		if ($c_end === false) $c_end = 65536;
		if ($q_start === false) $q_start = 65536;
		if ($q_end === false) $q_end = 65536;
		if ($s_start === false) $s_start = 65536;
		if ($s_end === false) $s_end = 65536;
		if ($n_start === false) $n_start = 65536;
		if ($n_end === false) $n_end = 65536;

		// If none of the strings were found
		if (min($c_start, $c_end, $q_start, $q_end, $q2_start, $s_start, $s_end, $s2_start, $n_start, $n_end) == 65536)
			break;

		// We are interested in the first quote (regardless of the type of quote)
		$q3_start = $q_start < $q2_start ? $q_start : $q2_start;

		// We are interested in the first spoiler (regardless of the type of spoiler)
		$s3_start = $s_start < $s2_start ? $s_start : $s2_start;

		// We found a [quote] or a [quote=username]
		if ($q3_start < min($c_start, $c_end, $q_end, $s3_start, $s_end, $n_start, $n_end))
		{
			$step = $q_start < $q2_start ? 7 : strlen($matches[0]);

			$cur_index += $q3_start + $step;

			// Did we reach $max_depth?
			if ($q_depth == $max_depth)
				$overflow_begin = $cur_index - $step;

			++$q_depth;
			$text = substr($text, $q3_start + $step);
		}

		// We found a [/quote]
		else if ($q_end < min($c_start, $c_end, $q3_start, $s3_start, $s_end, $n_start, $n_end))
		{
			if ($q_depth == 0)
			{
				$error = $lang_common['BBCode error'].' '.$lang_common['BBCode error 1'];
				return;
			}

			$q_depth--;
			$cur_index += $q_end+8;

			// Did we reach $max_depth?
			if ($q_depth == $max_depth)
				$overflow_end = $cur_index;

			$text = substr($text, $q_end+8);
		}

		// We found a [code]
		else if ($c_start < min($c_end, $q3_start, $q_end, $s3_start, $s_end, $n_start, $n_end))
		{
			// Make sure there's a [/code] and that any new [code] doesn't occur before the end tag
			$tmp = strpos($text, '[/code]');
			$tmp2 = strpos(substr($text, $c_start+6), '[code]');
			if ($tmp2 !== false)
				$tmp2 += $c_start+6;

			if ($tmp === false || ($tmp2 !== false && $tmp2 < $tmp))
			{
				$error = $lang_common['BBCode error'].' '.$lang_common['BBCode error 2'];
				return;
			}
			else
				$text = substr($text, $tmp+7);

			$cur_index += $tmp+7;
		}

		// We found a [/code] (this shouldn't happen since we handle both start and end tag in the if clause above)
		else if ($c_end < min($c_start, $q3_start, $q_end, $s3_start, $s_end, $n_start, $n_end))
		{
			$error = $lang_common['BBCode error'].' '.$lang_common['BBCode error 3'];
			return;
		}

		// We found a [spoiler]
		else if ($s3_start < min($c_start, $c_end, $q3_start, $q_end, $s_end, $n_start, $n_end))
		{
			$step = $s_start < $s2_start ? 9 : strlen($matches2[0]);

			$cur_index += $s3_start + $step;

			// Did we reach $max_depth?
			if ($s_depth == $max_depth)
				$overflow_begin = $cur_index - $step;

			++$s_depth;
			$text = substr($text, $s3_start + $step);
		}

		// We found a [/spoiler] (this shouldn't happen since we handle both start and end tag in the if clause above)
		else if ($s_end < min($c_start, $c_end, $q3_start, $q_end, $s3_start, $n_start, $n_end))
		{
			if ($s_depth == 0)
			{
				$error = $lang_common['BBCode error'].' '.$lang_common['BBCode error 1'];
				return;
			}

			$s_depth--;
			$cur_index += $s_end+10;

			// Did we reach $max_depth?
			if ($s_depth == $max_depth)
				$overflow_end = $cur_index;

			$text = substr($text, $s_end+10);
		}

		// We found a [noindex]
		else if ($n_start < min($c_start, $c_end, $q3_start, $q_end, $s3_start, $s_end, $n_end))
		{
			// Make sure there's a [/noindex] and that any new [noindex] doesn't occur before the end tag
			$tmp = strpos($text, '[/noindex]');
			$tmp2 = strpos(substr($text, $n_start+9), '[noindex]');
			if ($tmp2 !== false)
				$tmp2 += $n_start+9;

			if ($tmp === false || ($tmp2 !== false && $tmp2 < $tmp))
			{
				$error = $lang_common['BBCode error'].' '.$lang_common['BBCode error 8'];
				return;
			}
			else
				$text = substr($text, $tmp+7);

			$cur_index += $tmp+7;
		}

		// We found a [/noindex] (this shouldn't happen since we handle both start and end tag in the if clause above)
		else if ($n_end < min($c_start, $c_end, $q3_start, $q_end, $s3_start, $s_end, $n_start))
		{
			$error = $lang_common['BBCode error'].' '.$lang_common['BBCode error 9'];
			return;
		}

	}

	// If $q_depth <> 0 something is wrong with the quote syntax
	if ($q_depth)
	{
		$error = $lang_common['BBCode error'].' '.$lang_common['BBCode error 4'];
		return;
	}
	else if ($q_depth < 0)
	{
		$error = $lang_common['BBCode error'].' '.$lang_common['BBCode error 5'];
		return;
	}

	// If the quote depth level was higher than $max_depth we return the index for the
	// beginning and end of the part we should strip out
	if (isset($overflow_begin))
		return [$overflow_begin, $overflow_end];
	else
		return null;
}


//
// Split text into chunks ($inside contains all text inside $start and $end, and $outside contains all text outside)
//
function split_text($text, $start, $end)
{
	global $pun_config;

	$tokens = explode($start, $text);

	$outside[] = $tokens[0];

	$num_tokens = count($tokens);
	for ($i = 1; $i < $num_tokens; ++$i)
	{
		$temp = explode($end, $tokens[$i]);
		$inside[] = $temp[0];
		$outside[] = $temp[1];
	}

	if ($pun_config['o_indent_num_spaces'] != 8 && $start == '[code]')
	{
		$spaces = str_repeat(' ', (int)$pun_config['o_indent_num_spaces']);
		$inside = str_replace("\t", $spaces, $inside);
	}

	return [$inside, $outside];
}


//
// Preparse the contents of [list] bbcode
//
function preparse_list_tag($content, $type = '*')
{
	global $lang_common, $re_list;

	if (strlen($type) != 1)
		$type = '*';

	if (strpos($content,'[list') !== false)
	{
		$content = preg_replace_callback($re_list, create_function('$matches', 'return preparse_list_tag($matches[2], $matches[1]);'), $content);
	}

	$items = explode('[*]', str_replace('\"', '"', $content));

	$content = '';
	foreach ($items as $item)
	{
		if (pun_trim($item) != '')
			$content .= '[*'."\0".']'.str_replace('[/*]', '', pun_trim($item)).'[/*'."\0".']'."\n";
	}

	return '[list='.$type.']'."\n".$content.'[/list]';
}


//
// Truncate URL if longer than 55 characters (add http:// or ftp:// if missing)
//
function handle_url_tag($url, $link = '', $bbcode = false)
{
	$url = pun_trim($url);

	// Deal with [url][img]http://example.com/test.png[/img][/url]
	if (preg_match('%<img src=\"(.*?)\"%', $url, $matches))
		return handle_url_tag($matches[1], $url, $bbcode);

	$full_url = str_replace([' ', '\'', '`', '"'], ['%20', '', '', ''], $url);
	if (strpos($url, 'www.') === 0) // If it starts with www, we add http://
		$full_url = 'http://'.$full_url;
	else if (strpos($url, 'ftp.') === 0) // Else if it starts with ftp, we add ftp://
		$full_url = 'ftp://'.$full_url;
	else if (strpos($url, '/') === 0) // Allow for relative URLs that start with a slash
		$full_url = get_base_url(true).$full_url;
	else if (!preg_match('#^([a-z0-9]{3,6})://#', $url)) // Else if it doesn't start with abcdef://, we add http://
		$full_url = 'http://'.$full_url;

	// Ok, not very pretty :-)
	if ($bbcode)
	{
		if ($full_url == $link)
			return '[url]'.$link.'[/url]';
		else
			return '[url='.$full_url.']'.$link.'[/url]';
	}
	else
	{
		if ($link == '' || $link == $url)
		{
			$url = pun_htmlspecialchars_decode($url);
			$link = utf8_strlen($url) > 55 ? utf8_substr($url, 0 , 39).' … '.utf8_substr($url, -10) : $url;
			$link = pun_htmlspecialchars($link);
		}
		else
			$link = stripslashes($link);

		return '<a href="'.$full_url.'" rel="nofollow">'.$link.'</a>';
	}
}


//
// Turns an URL from the [img] tag into an <img> tag or a <a href...> tag
//
function handle_img_tag($url, $is_signature = false, $alt = null)
{
	global $lang_common, $pun_user;

	if (is_null($alt))
		$alt = basename($url);

	$img_tag = '<a href="'.$url.'" rel="nofollow">&lt;'.$lang_common['Image link'].' - '.$alt.'&gt;</a>';

	if ($is_signature && $pun_user['show_img_sig'] != '0')
		$img_tag = '<img class="sigimage" src="'.$url.'" alt="'.$alt.'" />';
	else if (!$is_signature && $pun_user['show_img'] != '0')
		$img_tag = '<span class="postimg"><img src="'.$url.'" alt="'.$alt.'" /></span>';

	return $img_tag;
}


//
// Parse the contents of [list] bbcode
//
function handle_list_tag($content, $type = '*')
{
	global $re_list;

	if (strlen($type) != 1)
		$type = '*';

	if (strpos($content,'[list') !== false)
	{
		$content = preg_replace_callback($re_list, create_function('$matches', 'return handle_list_tag($matches[2], $matches[1]);'), $content);
	}

	$content = preg_replace('#\s*\[\*\](.*?)\[/\*\]\s*#s', '<li><p>$1</p></li>', pun_trim($content));

	if ($type == '*')
		$content = '<ul type="disc" class="list">'.$content.'</ul>';
	elseif ($type == 's')
		$content = '<ul type="circle" class="list">'.$content.'</ul>';
	else
		if ($type == 'a')
			$content = '<ol class="alpha">'.$content.'</ol>';
		else
			$content = '<ol class="decimal">'.$content.'</ol>';

	return '</p>'.$content.'<p>';
}


//
// Convert BBCodes to their HTML equivalent
//
function do_bbcode($text, $is_signature = false)
{
	global $lang_common, $pun_user, $lang_post, $pun_config, $re_list;

	if (strpos($text, '[quote') !== false)
	{
		$text = preg_replace('%\[quote\]\s*%', '</p><div class="quotebox"><blockquote><div><p>', $text);
		$text = preg_replace_callback('%\[quote=(&quot;|&\#039;|"|\'|)([^\r\n]*?)\\1\]%s', create_function('$matches', 'global $lang_common; return "</p><div class=\"quotebox\"><cite>".str_replace(array(\'[\', \'\\"\'), array(\'&#91;\', \'"\'), $matches[2])." ".$lang_common[\'wrote\']."</cite><blockquote><div><p>";'), $text);
		$text = preg_replace('%\s*\[\/quote\]%S', '</p></div></blockquote></div><p>', $text);
	}
	if (!$is_signature)
	{
		$pattern_callback[] = $re_list;
		$replace_callback[] = 'handle_list_tag($matches[2], $matches[1])';
	}

	$pattern[] = '%\[b\](.*?)\[/b\]%ms';
	$pattern[] = '%\[i\](.*?)\[/i\]%ms';
	$pattern[] = '%\[u\](.*?)\[/u\]%ms';
	$pattern[] = '%\[s\](.*?)\[/s\]%ms';
	$pattern[] = '%\[del\](.*?)\[/del\]%ms';
	$pattern[] = '%\[ins\](.*?)\[/ins\]%ms';
	$pattern[] = '%\[em\](.*?)\[/em\]%ms';
	$pattern[] = '%\[colou?r=([a-zA-Z]{3,20}|\#[0-9a-fA-F]{6}|\#[0-9a-fA-F]{3})](.*?)\[/colou?r\]%ms';
	$pattern[] = '%\[h\](.*?)\[/h\]%ms';
	$pattern[] = '%\[font=(.*?)\](.*?)\[/font\]%ms';
	$pattern[] = '%\[align=(.*?)\](.*?)\[/align\]%ms';
	$pattern[] = '%\[hr ?/?\][\r\n]?%';
	$pattern[] = '%[\r\n]?\[table\][\r\n]*(.*?)[\r\n]*\[/table\]%ms';
	$pattern[] = '%[\r\n]?\[caption\][\r\n]*(.*?)[\r\n]*\[/caption\]%ms';
	$pattern[] = '%[\r\n]?\[tr\][\r\n]*(.*?)[\r\n]*\[/tr\]%ms';
	$pattern[] = '%[\r\n]?\[td\][\r\n]*(.*?)[\r\n]*\[/td\]%ms';
	$pattern[] = '%\[s\](.*?)\[/s\]%ms';
	$pattern[] = '%\[pre\](.*?)\[/pre\]%ms';
	$pattern[] = '%\[sup\](.*?)\[/sup\]%ms';
	$pattern[] = '%\[sub\](.*?)\[/sub\]%ms';
	$pattern[] = '%\[spoiler\](.*?)\[/spoiler\]%ms';
	$pattern[] = '%\[spoiler=(.*?)\](.*?)\[/spoiler\]%ms';

	$replace[] = '<strong>$1</strong>';
	$replace[] = '<em>$1</em>';
	$replace[] = '<span class="bbu">$1</span>';
	$replace[] = '<span class="bbs">$1</span>';
	$replace[] = '<del>$1</del>';
	$replace[] = '<ins>$1</ins>';
	$replace[] = '<em>$1</em>';
	$replace[] = '<span style="color: $1">$2</span>';
	$replace[] = '</p><h5>$1</h5><p>';
        $replace[] = '<span style="font-family: $1">$2</span>';
	$replace[] = '<div align="$1">$2</div>';
	$replace[] = '<hr/>';
	$replace[] = '<table border="1">$1</table>';
	$replace[] = '<div align="center">$1</div>';
	$replace[] = '<tr>$1</tr>';
	$replace[] = '<td>$1</td>';
	$replace[] = '<del>$1</del>';
	$replace[] = '<pre>$1</pre>';
	$replace[] = '<sup>$1</sup>';
	$replace[] = '<sub>$1</sub>';
	$replace[] = '<span style="background-color: #FFFF00; color: #000000">$1</span>';
	$replace[] = '<div class="spoiler"><div class="spoiler-plus" onclick="toggleSpoiler(this)">' . $lang_common['Spoiler'].'</div><div class="spoiler-body">$1</div></div>';
	$replace[] = '<div class="spoiler"><div class="spoiler-plus" onclick="toggleSpoiler(this)">$1</div><div class="spoiler-body">$2</div></div>';

	if (($is_signature && $pun_config['p_sig_img_tag'] == '1') || (!$is_signature && $pun_config['p_message_img_tag'] == '1'))
	{
		$pattern_callback[] = '%\[img\]((ht|f)tps?://)([^\s<"]*?)\[/img\]%';
		$pattern_callback[] = '%\[img=([^\[]*?)\]((ht|f)tps?://)([^\s<"]*?)\[/img\]%';
		if ($is_signature)
		{
			$replace_callback[] = 'handle_img_tag($matches[1].$matches[3], true)';
			$replace_callback[] = 'handle_img_tag($matches[2].$matches[4], true, $matches[1])';
		}
		else
		{
			$replace_callback[] = 'handle_img_tag($matches[1].$matches[3], false)';
			$replace_callback[] = 'handle_img_tag($matches[2].$matches[4], false, $matches[1])';
		}
	}

	$pattern_callback[] = '%\[url\]([^\[]*?)\[/url\]%';
	$pattern_callback[] = '%\[url=([^\[]+?)\](.*?)\[/url\]%';
	$pattern[] = '%\[email\]([^\[]*?)\[/email\]%';
	$pattern[] = '%\[email=([^\[]+?)\](.*?)\[/email\]%';
	$pattern[] = '%\[noindex\](.*?)\[/noindex\]%ms';

	$replace_callback[] = 'handle_url_tag($matches[1])';
	$replace_callback[] = 'handle_url_tag($matches[1], $matches[2])';
	$replace[] = '<a href="mailto:$1">$1</a>';
	$replace[] = '<a href="mailto:$1">$2</a>';
	$replace_callback[] = 'handle_noindex_tag($matches[1])';

	// This thing takes a while! :)
	$text = preg_replace($pattern, $replace, $text);
	$count = count($pattern_callback);
	for($i = 0 ; $i < $count ; $i++)
	{
		$text = preg_replace_callback($pattern_callback[$i], create_function('$matches', 'return '.$replace_callback[$i].';'), $text);
	}

	if (strpos((string)$text, 'quote') !== false)
	{
		$text = str_replace('[quote]', '</p><blockquote><div class="incqbox"><p>', $text);
		$text = preg_replace_callback(
			'#\[quote=(&quot;|"|\'|)(.*)\\1\]#sU',
			function($matches){
				foreach($matches as $match) {
					return str_replace('[\', \'&#91;\', \'$matches[2]\')." ".$lang_common[\'wrote\'].": </h4><p>"', $matches);
				}
			},
			$text
		);
		$text = preg_replace('#\[\/quote\]\s*#', '</p></div></blockquote><p>', $text);
	}

	if (strpos((string)$text, 'added') !== false)
	{
		preg_match_all('#\[added=((\d){9,11})\]#i', $text, $added, PREG_SET_ORDER);
		if(count($added))
		{
			foreach ($added as $match)
				if(isset($match[1]))
					$text = str_replace($match[0], '<span style="color: #808080"><em><i>' . $lang_post['Added'].' '.format_time($match[1]) . '</i></em></span>', $text);
		}
	}

	return $text;
}


//
// Make hyperlinks clickable
//
function do_clickable($text)
{
	$text = ' '.$text;
	$text = ucp_preg_replace_callback('%(?<=[\s\]\)])(<)?(\[)?(\()?([\'"]?)(https?|ftp|news){1}://([\p{L}\p{N}\-]+\.([\p{L}\p{N}\-]+\.)*[\p{L}\p{N}]+(:[0-9]+)?(/(?:[^\s\[]*[^\s.,?!\[;:-])?)?)\4(?(3)(\)))(?(2)(\]))(?(1)(>))(?![^\s]*\[/(?:url|img)\])%ui', 'stripslashes($matches[1].$matches[2].$matches[3].$matches[4]).handle_url_tag($matches[5]."://".$matches[6], $matches[5]."://".$matches[6], true).stripslashes($matches[4].forum_array_key($matches, 10).forum_array_key($matches, 11).forum_array_key($matches, 12))', $text);
	$text = ucp_preg_replace_callback('%(?<=[\s\]\)])(<)?(\[)?(\()?([\'"]?)(www|ftp)\.(([\p{L}\p{N}\-]+\.)+[\p{L}\p{N}]+(:[0-9]+)?(/(?:[^\s\[]*[^\s.,?!\[;:-])?)?)\4(?(3)(\)))(?(2)(\]))(?(1)(>))(?![^\s]*\[/(?:url|img)\])%ui','stripslashes($matches[1].$matches[2].$matches[3].$matches[4]).handle_url_tag($matches[5].".".$matches[6], $matches[5].".".$matches[6], true).stripslashes($matches[4].forum_array_key($matches, 10).forum_array_key($matches, 11).forum_array_key($matches, 12))', $text);

	return substr($text, 1);
}


//
// Return an array key, if it exists, otherwise return an empty string
//
function forum_array_key($arr, $key)
{
	return isset($arr[$key]) ? $arr[$key] : '';
}


//
// Convert a series of smilies to images
//
function do_smilies($text)
{
	global $smiley_text, $smiley_img;

	$text = ' '.$text.' ';

	$num_smilies = count($smiley_text);
	for ($i = 0; $i < $num_smilies; ++$i)
		$text = preg_replace("#(?<=.\W|\W.|^\W)".preg_quote($smiley_text[$i], '#')."(?=.\W|\W.|\W$)#m", '$1<img src="img/smilies/'.$smiley_img[$i].'" class="smileyimg" alt="'.$smiley_text[$i].'" title="'.substr($smiley_img[$i], 0, strrpos($smiley_img[$i], '.')).'" />$2', $text);

	return substr($text, 1, -1);
}


//
// Convert a series of browsers to images
//
function do_browsers($text)
{
	global $browser_text, $browser_img;

	$text = ' '.$text.' ';

	$num_browsers = count($browser_text);
	for ($i = 0; $i < $num_browsers; ++$i)
		$text = preg_replace("#(?<=.\W|\W.|^\W)".preg_quote($browser_text[$i], '#')."(?=.\W|\W.|\W$)#mi", '$1<img src="img/browsers/'.$browser_img[$i].'" class="browserimg" alt="'.$browser_text[$i].'" title="'.substr($browser_img[$i], 0, strrpos($browser_img[$i], '.')).'" />$2', $text);

	return substr($text, 1, -1);
}

function iconize_topic($text, $fid)
{
	global $pun_config;
	static $iconize_forums;

	if (!isset($iconize_forums))
		$iconize_forums = !empty($pun_config['o_iconize_subforums']) ? explode(',', $pun_config['o_iconize_subforums']) : [];

	if ($fid && in_array($fid, $iconize_forums))
		return do_browsers($text);
	return $text;
}


//
// Parse message text
//
function parse_message($text, $hide_smilies)
{
	global $pun_config, $lang_common, $pun_user;

	if ($pun_config['o_censoring'] == '1')
		$text = censor_words($text);

	// Convert applicable characters to HTML entities
	$text = pun_htmlspecialchars($text);

	// If the message contains a code tag we have to split it up (text within [code][/code] shouldn't be touched)
	if (strpos($text, '[code]') !== false && strpos($text, '[/code]') !== false)
		list($inside, $text) = extract_blocks($text, '[code]', '[/code]');

	if ($pun_config['o_make_links'] == '1')
		$text = do_clickable($text);

	if ($pun_config['o_smilies'] == '1' && $pun_user['show_smilies'] == '1' && $hide_smilies == '0')
	{
		$text = do_smilies($text);
		$text = do_browsers($text);
	}

	if ($pun_config['p_message_bbcode'] == '1' && strpos($text, '[') !== false && strpos($text, ']') !== false)
	{
		$text = do_bbcode($text);
	}

	// Deal with newlines, tabs and multiple spaces
	$pattern = ["\n", "\t", '  ', '  '];
	$replace = ['<br />', '&nbsp; &nbsp; ', '&nbsp; ', ' &nbsp;'];
	$text = str_replace($pattern, $replace, $text);

	// If we split up the message before we have to concatenate it together again (code tags)
	if (isset($inside))
	{
		$outside = explode('<">', $text);
		$text = '';

		$num_tokens = count($outside);

		for ($i = 0; $i < $num_tokens; ++$i)
		{
			$text .= $outside[$i];
			if (isset($inside[$i]))
			{
				$num_lines = (substr_count($inside[$i], "\n") + 3) * 1.5;
				$height_str = $num_lines > 35 ? '35em' : $num_lines.'em';
				$text .= '</p><div class="codebox"><div class="incqbox"><a href="#" style="float:right" onclick="return codeSelect(this)">'.$lang_common['Code select'].'</a><h4>'.$lang_common['Code'].':</h4><div class="scrollbox" style="height: '.$height_str.'"><pre>'.$inside[$i].'</pre></div></div></div><p>';
			}
		}
	}

	return clean_paragraphs($text);
}

//
// Clean up paragraphs and line breaks
//
function clean_paragraphs($text)
{
	// Add paragraph tag around post, but make sure there are no empty paragraphs

	$text = '<p>'.$text.'</p>';

	// Replace any breaks next to paragraphs so our replace below catches them
	$text = preg_replace('%(</?p>)(?:\s*?<br />){1,2}%i', '$1', $text);
	$text = preg_replace('%(?:<br />\s*?){1,2}(</?p>)%i', '$1', $text);

	// Remove any empty paragraph tags (inserted via quotes/lists/code/etc) which should be stripped
	$text = str_replace('<p></p>', '', $text);

	$text = preg_replace('%<br />\s*?<br />%i', '</p><p>', $text);

	$text = str_replace('<p><br />', '<br /><p>', $text);
	$text = str_replace('<br /></p>', '</p><br />', $text);
	return str_replace('<p></p>', '<br /><br />', $text);
}


//
// Parse signature text
//
function parse_signature($text)
{
	global $pun_config, $lang_common, $pun_user;

	if ($pun_config['o_censoring'] == '1')
		$text = censor_words($text);

	$text = pun_htmlspecialchars($text);

	if ($pun_config['o_make_links'] == '1')
		$text = do_clickable($text);

	if ($pun_config['o_smilies_sig'] == '1' && $pun_user['show_smilies'] != '0')
	{
		$text = do_smilies($text);
		$text = do_browsers($text);
	}

	if ($pun_config['p_sig_bbcode'] == '1' && strpos($text, '[') !== false && strpos($text, ']') !== false)
	{
		$text = do_bbcode($text, true);
	}

	// Deal with newlines, tabs and multiple spaces
	$pattern = ["\n", "\t", '  ', '  '];
	$replace = ['<br />', '&nbsp; &nbsp; ', '&nbsp; ', ' &nbsp;'];
	$text = str_replace($pattern, $replace, $text);

	return clean_paragraphs($text);
}

//
// Spam protect e-mails
//
function handle_email_tag($email,$text = '')
{
	$enc_email='';
	$url = 'mailto:'.$email;
	for ($a=0;$a<strlen($url);$a++)
	{
		$charValue = ord(substr($url,$a,1));
		$charValue+=intval(2);
		$enc_email.=chr($charValue);
	}

	if ($text == '')
	{
		$text = str_replace('@','@<span style="display:none">remove-this.</span>',$email);
	}
	return '<a href="#" onclick="mailTo(\''.$enc_email.'\');return false;">'.$text.'</a>';
}

//
// NoIndex tag
//
function handle_noindex_tag($text)
{
	global $pun_user, $pun_config;

	if ($pun_user['g_id'] == PUN_GUEST)
		return '<div class="noindex" title="Текстовый блок не индексируется поисковыми системами"><a href="' . $pun_config['o_base_url']. '/login.php">Войдите</a> или <a href="' . $pun_config['o_base_url']. '/register.php">зарегистрируйтесь</a>, чтобы увидеть скрытый текст.</div>';
	return '<div class="noindex" title="Текстовый блок не индексируется поисковыми системами">' . stripslashes($text) . '</div>';
}
