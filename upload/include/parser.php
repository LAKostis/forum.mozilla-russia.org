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


// Here you can add additional smilies if you like (please note that you must escape singlequote and backslash)
$smiley_text = array(
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
);
$smiley_img = array(
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
);
$smiley_limit = 17;

// Uncomment the next row if you add smilies that contain any of the characters &"'<>
//$smiley_text = array_map('pun_htmlspecialchars', $smiley_text);

$browser_text = array(
	'[firefox]',
	'[fx]',
	'[thunderbird]',
	'[tb]',
	'[mozilla]',
	'[mz]',
	'[seamonkey]',
	'[sm]',
	'[flock]',
	'[fl]',
	'[fennec]',
	'[fn]',
	'[songbird]',
	'[sgb]',
	'[sunbird]',
	'[sb]',
	'[k-meleon]',
	'[km]',
	'[nvu]',
	'[bugzilla]',
	'[minefield]',
	'[shiretoko]',
	'[netscape]',
	'[ns]',
	'[ie]',
	'[ie7]',
	'[opera]',
	'[safari]',
	'[sunrise]',
	'[chrome]',
	'[konqueror]',
	'[camino]',
	'[arora]',
	'[etna]',
	'[linux]',
	'[windows]',
	'[macos]'
);
$browser_img = array(
	'firefox.png',
	'firefox.png',
	'thunderbird.png',
	'thunderbird.png',
	'mozilla.png',
	'mozilla.png',
	'seamonkey.png',
	'seamonkey.png',
	'flock.png',
	'flock.png',
	'fennec.png',
	'fennec.png',
	'songbird.png',
	'songbird.png',
	'sunbird.png',
	'sunbird.png',
	'k-meleon.png',
	'k-meleon.png',
	'nvu.png',
	'bugzilla.png',
	'minefield.png',
	'shiretoko.png',
	'netscape.png',
	'netscape.png',
	'ie.png',
	'ie7.png',
	'opera.png',
	'safari.png',
	'sunrise.png',
	'chrome.png',
	'konqueror.png',
	'camino.png',
	'arora.png',
	'etna.png',
	'linux.png',
	'windows.png',
	'macos.png'
);
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
	$a = array('[B]', '[I]', '[U]', '[/B]', '[/I]', '[/U]');
	$b = array('[b]', '[i]', '[u]', '[/b]', '[/i]', '[/u]');
	$text = str_replace($a, $b, $text);

	// Do the more complex BBCodes (also strip excessive whitespace and useless quotes)
	$a = array(	'#\[url=("|\'|)(.*?)\\1\]\s*#i',
				'#\[url\]\s*#i',
				'#\s*\[/url\]#i',
				'#\[email=("|\'|)(.*?)\\1\]\s*#i',
				'#\[email\]\s*#i',
				'#\s*\[/email\]#i',
				'#\[img\]\s*(.*?)\s*\[/img\]#is',
				'#\[colou?r=("|\'|)(.*?)\\1\](.*?)\[/colou?r\]#is',
				'#\[spoiler=("|\'|)(.*?)\\1\]\s*#i',
				'#\[spoiler\]\s*#i',
				'#\s*\[/spoiler\]#i',
				'#\[noindex\]\s*#i',
				'#\s*\[/noindex\]#i');

	$b = array(	'[url=$2]',
				'[url]',
				'[/url]',
				'[email=$2]',
				'[email]',
				'[/email]',
				'[img]$1[/img]',
				'[color=$2]$3[/color]',
				'[spoiler=$2]',
				'[spoiler]',
				'[/spoiler]',
				'[noindex]',
				'[/noindex]');

	if (!$is_signature)
	{
		// For non-signatures, we have to do the quote and code tags as well
		$a[] = '#\[quote=(&quot;|"|\'|)(.*?)\\1\]\s*#i';
		$a[] = '#\[quote\]\s*#i';
		$a[] = '#\s*\[/quote\]\s*#i';
		$a[] = '#\[code\][\r\n]*(.*?)\s*\[/code\]\s*#is';

		$b[] = '[quote=$1$2$1]';
		$b[] = '[quote]';
		$b[] = '[/quote]'."\n";
		$b[] = '[code]$1[/code]'."\n";
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
			$text = substr($text, 0, $overflow[0]).substr($text, $overflow[1], (strlen($text) - $overflow[0]));
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
				$num_lines = ((substr_count($inside[$i], "\n")) + 3) * 1.5;
				$height_str = ($num_lines > 35) ? '35em' : $num_lines.'em';
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
		$q3_start = ($q_start < $q2_start) ? $q_start : $q2_start;

		// We are interested in the first spoiler (regardless of the type of spoiler)
		$s3_start = ($s_start < $s2_start) ? $s_start : $s2_start;

		// We found a [quote] or a [quote=username]
		if ($q3_start < min($c_start, $c_end, $q_end, $s_start, $s_end, $n_start, $n_end))
		{
			$step = ($q_start < $q2_start) ? 7 : strlen($matches[0]);

			$cur_index += $q3_start + $step;

			// Did we reach $max_depth?
			if ($q_depth == $max_depth)
				$overflow_begin = $cur_index - $step;

			++$q_depth;
			$text = substr($text, $q3_start + $step);
		}

		// We found a [/quote]
		else if ($q_end < min($c_start, $c_end, $q_start, $s_start, $s_end, $n_start, $n_end))
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
		else if ($c_start < min($c_end, $q_start, $q_end, $s_start, $s_end, $n_start, $n_end))
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
		else if ($c_end < min($c_start, $q_start, $q_end, $s_start, $s_end, $n_start, $n_end))
		{
			$error = $lang_common['BBCode error'].' '.$lang_common['BBCode error 3'];
			return;
		}

		// We found a [spoiler]
		else if ($s_start < min($c_start, $c_end, $q_start, $q_end, $s_end, $n_start, $n_end))
		{
			$step = ($s_start < $s2_start) ? 9 : strlen($matches2[0]);

			// Make sure there's a [/spoiler] and that any new [spoiler] doesn't occur before the end tag
			$tmp = strpos($text, '[/spoiler]');
			$tmp2 = strpos(substr($text, $s_start+$step), '[spoiler]');
			if ($tmp2 !== false)
				$tmp2 += $s_start+$step;

			if ($tmp === false || ($tmp2 !== false && $tmp2 < $tmp))
			{
				$error = $lang_common['BBCode error'].' '.$lang_common['BBCode error 6'];
				return;
			}
			else
				$text = substr($text, $tmp+7);

			$cur_index += $tmp+7;
		}

		// We found a [/spoiler] (this shouldn't happen since we handle both start and end tag in the if clause above)
		else if ($s_end < min($c_start, $c_end, $q_start, $q_end, $s_start, $n_start, $n_end))
		{
			$error = $lang_common['BBCode error'].' '.$lang_common['BBCode error 7'];
			return;
		}

		// We found a [noindex]
		else if ($n_start < min($c_start, $c_end, $q_start, $q_end, $s_start, $s_end, $n_end))
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
		else if ($n_end < min($c_start, $c_end, $q_start, $q_end, $s_start, $s_end, $n_start))
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
		return array($overflow_begin, $overflow_end);
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
		$spaces = str_repeat(' ', $pun_config['o_indent_num_spaces']);
		$inside = str_replace("\t", $spaces, $inside);
	}

	return array($inside, $outside);
}


//
// Truncate URL if longer than 55 characters (add http:// or ftp:// if missing)
//
function handle_url_tag($url, $link = '')
{
	global $pun_user;

	$full_url = str_replace(array(' ', '\'', '`'), array('%20', '', ''), $url);
	if (strpos($url, 'www.') === 0)			// If it starts with www, we add http://
		$full_url = 'http://'.$full_url;
	else if (strpos($url, 'ftp.') === 0)	// Else if it starts with ftp, we add ftp://
		$full_url = 'ftp://'.$full_url;
	else if (!preg_match('#^([a-z0-9]{3,6})://#', $url, $bah))	// Else if it doesn't start with abcdef://, we add http://
		$full_url = 'http://'.$full_url;

	// Ok, not very pretty :-)
	$link = ($link == '' || $link == $url) ? ((strlen($url) > 55) ? substr($url, 0 , 39).' &hellip; '.substr($url, -10) : $url) : stripslashes($link);

	return '<a href="'.$full_url.'">'.$link.'</a>';
}


//
// Turns an URL from the [img] tag into an <img> tag or a <a href...> tag
//
function handle_img_tag($url, $is_signature = false)
{
	global $lang_common, $pun_config, $pun_user;

	$img_tag = '<a href="'.$url.'">&lt;'.$lang_common['Image link'].'&gt;</a>';

	if ($is_signature && $pun_user['show_img_sig'] != '0')
		$img_tag = '<img class="sigimage" src="'.$url.'" alt="'.htmlspecialchars($url).'" />';
	else if (!$is_signature && $pun_user['show_img'] != '0')
		$img_tag = '<img class="postimg" src="'.$url.'" alt="'.htmlspecialchars($url).'" />';

	return $img_tag;
}


//
// Convert BBCodes to their HTML equivalent
//
function do_bbcode($text)
{
	global $lang_common, $pun_user, $lang_post;

	$pattern = array(
		'#\[b\](.*?)\[/b\]#s',
		'#\[i\](.*?)\[/i\]#s',
		'#\[u\](.*?)\[/u\]#s',
		'#\[url\]([^\[<]*?)\[/url\]#e',
		'#\[url=([^\[<]*?)\](.*?)\[/url\]#e',
		'#\[email\]([^\[<]*?)\[/email\]#e',
		'#\[email=([^\[<]*?)\](.*?)\[/email\]#e',
		'#\[list\](.*?)\[/list\]#s',
		'#\[list=s\](.*?)\[/list\]#s',
		'#\[list=a\](.*?)\[/list\]#s',
		'#\[list=1\](.*?)\[/list\]#s',
		'#\[\*\]#',
		'#\[color=([a-zA-Z]*|\#?[0-9a-fA-F]{6})\](.*?)\[/color\]#s',
		'#\[font=(.*?)\](.*?)\[/font\]#s',
		'#\[align=(.*?)\](.*?)\[/align\]#s',
		'#\[hr /\]#',
		'#\[hr\]#',
		'#\[table\](.*?)\[/table\]#s',
		'#\[caption\](.*?)\[/caption\]#s',
		'#\[tr\](.*?)\[/tr\]#s',
		'#\[td\](.*?)\[/td\]#s',
		'#\[s\](.*?)\[/s\]#s',
		'#\[pre\](.*?)\[/pre\]#s',
		'#\[sup\](.*?)\[/sup\]#s',
		'#\[sub\](.*?)\[/sub\]#s',
		'#\[h\](.*?)\[/h\]#s',
		'#\[spoiler\](.*?)\[/spoiler\]#s',
		'#\[spoiler=(.*?)\](.*?)\[/spoiler\]#s',
		'#\[noindex\](.*?)\[/noindex\]#es'
	);

	$replace = array(
		'<strong>$1</strong>',
		'<em>$1</em>',
		'<span class="bbu">$1</span>',
		'handle_url_tag(\'$1\')',
		'handle_url_tag(\'$1\', \'$2\')',
		'handle_email_tag(\'$1\')',
		'handle_email_tag(\'$1\',\'$2\')',
		'<ul type="disc" class="list">$1</ul>',
		'<ul type="circle" class="list">$1</ul>',
		'<ol type="A" class="list">$1</ol>',
		'<ol type="1" class="list">$1</ol>',
		'<li class="list"/>',
		'<span style="color: $1">$2</span>',
		'<span style="font-family: $1">$2</span>',
		'<div align="$1">$2</div>',
		'<hr />',
		'<hr />',
		'<table>$1</table>',
		'<div align="center">$1</div>',
		'<tr>$1</tr>',
		'<td>$1</td>',
		'<del>$1</del>',
		'<pre>$1</pre>',
		'<sup>$1</sup>',
		'<sub>$1</sub>',
		'<span style="background-color: #FFFF00; color: #000000">$1</span>',
		'<div class="spoiler"><div class="spoiler-plus" onclick="toggleSpoiler(this)">' . $lang_common['Spoiler'].'</div><div class="spoiler-body">$1</div></div>',
		'<div class="spoiler"><div class="spoiler-plus" onclick="toggleSpoiler(this)">$1</div><div class="spoiler-body">$2</div></div>',
		'handle_noindex_tag(\'$1\')'
	);

	// This thing takes a while! :)
	$text = preg_replace($pattern, $replace, $text);

	if (strpos($text, 'quote') !== false)
	{
		$text = str_replace('[quote]', '</p><blockquote><div class="incqbox"><p>', $text);
		$text = preg_replace('#\[quote=(&quot;|"|\'|)(.*)\\1\]#seU', '"</p><blockquote><div class=\"incqbox\"><h4>".str_replace(\'[\', \'&#91;\', \'$2\')." ".$lang_common[\'wrote\'].":</h4><p>"', $text);
		$text = preg_replace('#\[\/quote\]\s*#', '</p></div></blockquote><p>', $text);
	}

	if (strpos($text, 'added') !== false)
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
	global $pun_user;

	$text = ' '.$text;

	$text = preg_replace('#([\s\(\)])(https?|ftp|news){1}://([\w\-]+\.([\w\-]+\.)*[\w]+(:[0-9]+)?(/[^"\s\(\)<\[]*)?)#ie', '\'$1\'.handle_url_tag(\'$2://$3\')', $text);
	$text = preg_replace('#([\s\(\)])(www|ftp)\.(([\w\-]+\.)*[\w]+(:[0-9]+)?(/[^"\s\(\)<\[]*)?)#ie', '\'$1\'.handle_url_tag(\'$2.$3\', \'$2.$3\')', $text);

	return substr($text, 1);
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
		$iconize_forums = !empty($pun_config['o_iconize_subforums']) ? split(',', $pun_config['o_iconize_subforums']) : array();

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
	{
		list($inside, $outside) = split_text($text, '[code]', '[/code]');
		$outside = array_map('ltrim', $outside);
		$text = implode('<">', $outside);
	}

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

		if ($pun_config['p_message_img_tag'] == '1')
			$text = preg_replace('#\[img\]((ht|f)tps?://)([^\s<"]*?)\[/img\]#e', 'handle_img_tag(\'$1$3\')', $text);
	}

	// Deal with newlines, tabs and multiple spaces
	$pattern = array("\n", "\t", '  ', '  ');
	$replace = array('<br />', '&nbsp; &nbsp; ', '&nbsp; ', ' &nbsp;');
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
				$num_lines = ((substr_count($inside[$i], "\n")) + 3) * 1.5;
				$height_str = ($num_lines > 35) ? '35em' : $num_lines.'em';
				$text .= '</p><div class="codebox"><div class="incqbox"><h4>'.$lang_common['Code'].':</h4><div class="scrollbox" style="height: '.$height_str.'"><pre>'.$inside[$i].'</pre></div></div></div><p>';
			}
		}
	}

	// Add paragraph tag around post, but make sure there are no empty paragraphs
	$text = str_replace('<p></p>', '', '<p>'.$text.'</p>');

	return $text;
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
		$text = do_bbcode($text);

		if ($pun_config['p_sig_img_tag'] == '1')
			$text = preg_replace('#\[img\]((ht|f)tps?://)([^\s<"]*?)\[/img\]#e', 'handle_img_tag(\'$1$3\', true)', $text);
	}

	// Deal with newlines, tabs and multiple spaces
	$pattern = array("\n", "\t", '  ', '  ');
	$replace = array('<br />', '&nbsp; &nbsp; ', '&nbsp; ', ' &nbsp;');
	$text = str_replace($pattern, $replace, $text);

	return $text;
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
