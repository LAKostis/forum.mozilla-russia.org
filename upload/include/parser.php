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
(?:=([1as*]))?+       # optional attribute capture in group 1
\]                    # closing bracket of outermost opening LIST tag
(                     # capture contents of LIST tag in group 2
  (?:                 # non capture group for either contents or whole nested LIST
    [^\[]*+           # unroll the loop! consume everything up to next [ (normal *)
    (?:               # (See "Mastering Regular Expressions" chapter 6 for details)
      (?!             # negative lookahead ensures we are NOT on [LIST*] or [/LIST]
        \[list        # opening LIST tag
        (?:=[1as*])?+ # with optional attribute
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
$re_list = '%\[list(?:=([1as*]))?+\]((?:[^\[]*+(?:(?!\[list(?:=[1as*])?+\]|\[/list\])\[[^\[]*+)*+|(?R))*)\[/list\]%i';

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

function preparse_bbcode($text, &$errors, $is_signature = false)
{
	global $pun_config, $lang_common, $lang_post, $re_list;

	// Remove empty tags
	while (($new_text = strip_empty_bbcode($text)) !== false)
	{
		if ($new_text != $text)
		{
			$text = $new_text;
			if ($new_text == '')
			{
				$errors[] = $lang_post['Empty after strip'];
				return '';
			}
		}
		else
			break;
	}

	if ($is_signature)
	{
		global $lang_profile;

		if (preg_match('%\[/?(?:quote|code|list|h|hr|size|after|spoiler|noindex)\b[^\]]*\]%i', $text))
			$errors[] = $lang_profile['Signature quote/code/list/h'];
	}


	// If the message contains a code tag we have to split it up (text within [code][/code] shouldn't be touched)
	if (strpos($text, '[code]') !== false && strpos($text, '[/code]') !== false)
		list($inside, $text) = extract_blocks($text, '[code]', '[/code]');

        // Tidy up lists
	$temp = preg_replace_callback($re_list, function($matches) { return preparse_list_tag($matches[2], $matches[1]); }, $text);

	// If the regex failed
	if (is_null($temp))
		$errors[] = $lang_common['BBCode list size error'];
	else
		$text = str_replace('*'."\0".']', '*]', $temp);

	if ($pun_config['o_make_links'] == '1')
		$text = do_clickable($text);

	$temp_text = false;
	if (empty($errors))
		$temp_text = preparse_tags($text, $errors, $is_signature);

	if ($temp_text !== false)
		$text = $temp_text;

	// If we split up the message before we have to concatenate it together again (code tags)
	if (isset($inside))
	{
		$outside = explode("\1", $text);
		$text = '';

		$num_tokens = count($outside);
		for ($i = 0; $i < $num_tokens; ++$i)
		{
			$text .= $outside[$i];
			if (isset($inside[$i]))
				$text .= '[code]'.$inside[$i].'[/code]';
		}

		unset($inside);
	}

	// Remove empty tags
	while (($new_text = strip_empty_bbcode($text)) !== false)
	{
		if ($new_text != $text)
		{
			$text = $new_text;
			if ($new_text == '')
			{
				$errors[] = $lang_post['Empty after strip'];
				break;
			}
		}
		else
			break;
	}

	return pun_trim($text);
}


//
// Strip empty bbcode tags from some text
//
function strip_empty_bbcode($text)
{
	// If the message contains a code tag we have to split it up (empty tags within [code][/code] are fine)
	if (strpos($text, '[code]') !== false && strpos($text, '[/code]') !== false)
		list($inside, $text) = extract_blocks($text, '[code]', '[/code]');

	// Remove empty tags
	while (!is_null($new_text = preg_replace('%\[(b|u|s|ins|del|em|i|h|colou?r|quote|img|imgl|imgr|url|email|list|topic|post|forum|user|hr|size|after|spoiler|noindex|right|center|justify|mono)(?:\=[^\]]*)?\]\s*\[/\1\]%', '', $text)))
	{
		if ($new_text != $text)
			$text = $new_text;
		else
			break;
	}

	// If we split up the message before we have to concatenate it together again (code tags)
	if (isset($inside))
	{
		$parts = explode("\1", $text);
		$text = '';
		foreach ($parts as $i => $part)
		{
			$text .= $part;
			if (isset($inside[$i]))
				$text .= '[code]'.$inside[$i].'[/code]';
		}
	}

	// Remove empty code tags
	while (!is_null($new_text = preg_replace('%\[(code)\]\s*\[/\1\]%', '', $text)))
	{
		if ($new_text != $text)
			$text = $new_text;
		else
			break;
	}

	return $text;
}


//
// Check the structure of bbcode tags and fix simple mistakes where possible
//
function preparse_tags($text, &$errors, $is_signature = false)
{
	global $lang_common, $pun_config;

	// Start off by making some arrays of bbcode tags and what we need to do with each one

	// List of all the tags
	$tags = array('quote', 'code', 'b', 'i', 'u', 's', 'ins', 'del', 'em', 'color', 'colour', 'url', 'email', 'img', 'imgl', 'imgr', 'list', '*', 'h', 'topic', 'post', 'forum', 'user', 'size', 'spoiler', 'right', 'center', 'justify', 'mono', 'noindex');
	// List of tags that we need to check are open (You could not put b,i,u in here then illegal nesting like [b][i][/b][/i] would be allowed)
	$tags_opened = $tags;
	// and tags we need to check are closed (the same as above, added it just in case)
	$tags_closed = array_diff($tags, array('*'));
	// Tags we can nest and the depth they can be nested to
	$tags_nested = array('quote' => '3', 'list' => 5, '*' => 99, 'spoiler' => 5, 'noindex' => 5);
	// Tags to ignore the contents of completely (just code)
	$tags_ignore = array('code');
	// Tags not allowed
	$tags_forbidden = array();
	// Block tags, block tags can only go within another block tag, they cannot be in a normal tag
	$tags_block = array('quote', 'code', 'list', 'h', '*', 'spoiler', 'noindex');
	// Inline tags, we do not allow new lines in these
	$tags_inline = array('b', 'i', 'u', 's', 'ins', 'del', 'em', 'color', 'colour', 'h', 'topic', 'post', 'forum', 'user');
	// Tags we trim interior space
	$tags_trim = array('img', 'imgl', 'imgr', 'url', 'email');
	// Tags we remove quotes from the argument
	$tags_quotes = array('url', 'email', 'img', 'imgl', 'imgr', 'topic', 'post', 'forum', 'user');
	// Tags we limit bbcode in
	$tags_limit_bbcode = array(
		'*' 	=> array('b', 'i', 'u', 's', 'ins', 'del', 'em', 'color', 'colour', 'url', 'email', 'list', 'img', 'imgl', 'imgr', 'code', 'topic', 'post', 'forum', 'user'),
		'list' 	=> array('*'),
		'url' 	=> array('img', 'imgr', 'imgl'),
		'email' => array('img', 'imgr', 'imgl'),
		'topic' => array('img', 'imgr', 'imgl'),
		'post'  => array('img', 'imgr', 'imgl'),
		'forum' => array('img', 'imgr', 'imgl'),
		'user'  => array('img', 'imgr', 'imgl'),
		'img' 	=> array(),
		'imgr' 	=> array(),
		'imgl' 	=> array(),
		'h'		=> array('b', 'i', 'u', 's', 'ins', 'del', 'em', 'color', 'colour', 'url', 'email', 'topic', 'post', 'forum', 'user'),
	);
	// Tags we can automatically fix bad nesting
	$tags_fix = array('quote', 'b', 'i', 'u', 's', 'ins', 'del', 'em', 'color', 'colour', 'url', 'email', 'h', 'topic', 'post', 'forum', 'user', 'spoiler', 'noindex');

	$split_text = preg_split('%(\[[\*a-zA-Z0-9-/]*?(?:=.*?)?\])%', $text, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);

	$open_tags = array('fluxbb-bbcode');
	$open_args = array('');
	$opened_tag = 0;
	$new_text = '';
	$current_ignore = '';
	$current_nest = '';
	$current_depth = array();
	$limit_bbcode = $tags;
	$count_ignored = array();

	foreach ($split_text as $current)
	{
		if ($current == '')
			continue;

		// Are we dealing with a tag?
		if (substr($current, 0, 1) != '[' || substr($current, -1, 1) != ']')
		{
			// It's not a bbcode tag so we put it on the end and continue
			// If we are nested too deeply don't add to the end
			if ($current_nest)
				continue;

			$current = str_replace("\r\n", "\n", $current);
			$current = str_replace("\r", "\n", $current);
			if (in_array($open_tags[$opened_tag], $tags_inline) && strpos($current, "\n") !== false)
			{
				// Deal with new lines
				$split_current = preg_split('%(\n\n+)%', $current, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
				$current = '';

				if (!pun_trim($split_current[0], "\n")) // The first part is a linebreak so we need to handle any open tags first
					array_unshift($split_current, '');

				for ($i = 1; $i < count($split_current); $i += 2)
				{
					$temp_opened = array();
					$temp_opened_arg = array();
					$temp = $split_current[$i - 1];
					while (!empty($open_tags))
					{
						$temp_tag = array_pop($open_tags);
						$temp_arg = array_pop($open_args);

						if (in_array($temp_tag , $tags_inline))
						{
							array_push($temp_opened, $temp_tag);
							array_push($temp_opened_arg, $temp_arg);
							$temp .= '[/'.$temp_tag.']';
						}
						else
						{
							array_push($open_tags, $temp_tag);
							array_push($open_args, $temp_arg);
							break;
						}
					}
					$current .= $temp.$split_current[$i];
					$temp = '';
					while (!empty($temp_opened))
					{
						$temp_tag = array_pop($temp_opened);
						$temp_arg = array_pop($temp_opened_arg);
						if (empty($temp_arg))
							$temp .= '['.$temp_tag.']';
						else
							$temp .= '['.$temp_tag.'='.$temp_arg.']';
						array_push($open_tags, $temp_tag);
						array_push($open_args, $temp_arg);
					}
					$current .= $temp;
				}

				if (array_key_exists($i - 1, $split_current))
					$current .= $split_current[$i - 1];
			}

			if (in_array($open_tags[$opened_tag], $tags_trim))
				$new_text .= pun_trim($current);
			else
				$new_text .= $current;

			continue;
		}

		// Get the name of the tag
		$current_arg = '';
		if (strpos($current, '/') === 1)
		{
			$current_tag = substr($current, 2, -1);
		}
		else if (strpos($current, '=') === false)
		{
			$current_tag = substr($current, 1, -1);
		}
		else
		{
			$current_tag = substr($current, 1, strpos($current, '=')-1);
			$current_arg = substr($current, strpos($current, '=')+1, -1);
		}
		$current_tag = strtolower($current_tag);

		// Is the tag defined?
		if (!in_array($current_tag, $tags))
		{
			// It's not a bbcode tag so we put it on the end and continue
			if (!$current_nest)
				$new_text .= $current;

			continue;
		}

		// We definitely have a bbcode tag

		// Make the tag string lower case
		if ($equalpos = strpos($current,'='))
		{
			// We have an argument for the tag which we don't want to make lowercase
			if (strlen(substr($current, $equalpos)) == 2)
			{
				// Empty tag argument
				$errors[] = sprintf($lang_common['BBCode error empty attribute'], $current_tag);
				return false;
			}
			$current = strtolower(substr($current, 0, $equalpos)).substr($current, $equalpos);
		}
		else
			$current = strtolower($current);

		// This is if we are currently in a tag which escapes other bbcode such as code
		// We keep a count of ignored bbcodes (code tags) so we can nest them, but
		// only balanced sets of tags can be nested
		if ($current_ignore)
		{
			// Increase the current ignored tags counter
			if ('['.$current_ignore.']' == $current)
				$count_ignored[$current_tag]++;

			// Decrease the current ignored tags counter
			if ('[/'.$current_ignore.']' == $current)
				$count_ignored[$current_tag]--;

			if ('[/'.$current_ignore.']' == $current && $count_ignored[$current_tag] == 0)
			{
				// We've finished the ignored section
				$current = '[/'.$current_tag.']';
				$current_ignore = '';
				$count_ignored = array();
			}

			$new_text .= $current;

			continue;
		}

		// Is the tag forbidden?
		if (in_array($current_tag, $tags_forbidden))
		{
			if (isset($lang_common['BBCode error tag '.$current_tag.' not allowed']))
				$errors[] = sprintf($lang_common['BBCode error tag '.$current_tag.' not allowed']);
			else
				$errors[] = sprintf($lang_common['BBCode error tag not allowed'], $current_tag);

			return false;
		}

		if ($current_nest)
		{
			// We are currently too deeply nested so lets see if we are closing the tag or not
			if ($current_tag != $current_nest)
				continue;

			if (substr($current, 1, 1) == '/')
				$current_depth[$current_nest]--;
			else
				$current_depth[$current_nest]++;

			if ($current_depth[$current_nest] <= $tags_nested[$current_nest])
				$current_nest = '';

			continue;
		}

		// Check the current tag is allowed here
		if (!in_array($current_tag, $limit_bbcode) && $current_tag != $open_tags[$opened_tag])
		{
			$errors[] = sprintf($lang_common['BBCode error invalid nesting'], $current_tag, $open_tags[$opened_tag]);
			return false;
		}

		if (substr($current, 1, 1) == '/')
		{
			// This is if we are closing a tag
			if ($opened_tag == 0 || !in_array($current_tag, $open_tags))
			{
				// We tried to close a tag which is not open
				if (in_array($current_tag, $tags_opened))
				{
					$errors[] = sprintf($lang_common['BBCode error no opening tag'], $current_tag);
					return false;
				}
			}
			else
			{
				// Check nesting
				while (true)
				{
					// Nesting is ok
					if ($open_tags[$opened_tag] == $current_tag)
					{
						array_pop($open_tags);
						array_pop($open_args);
						$opened_tag--;
						break;
					}

					// Nesting isn't ok, try to fix it
					if (in_array($open_tags[$opened_tag], $tags_closed) && in_array($current_tag, $tags_closed))
					{
						if (in_array($current_tag, $open_tags))
						{
							$temp_opened = array();
							$temp_opened_arg = array();
							$temp = '';
							while (!empty($open_tags))
							{
								$temp_tag = array_pop($open_tags);
								$temp_arg = array_pop($open_args);

								if (!in_array($temp_tag, $tags_fix))
								{
									// We couldn't fix nesting
									$errors[] = sprintf($lang_common['BBCode error no closing tag'], $temp_tag);
									return false;
								}
								array_push($temp_opened, $temp_tag);
								array_push($temp_opened_arg, $temp_arg);

								if ($temp_tag == $current_tag)
									break;
								else
									$temp .= '[/'.$temp_tag.']';
							}
							$current = $temp.$current;
							$temp = '';
							array_pop($temp_opened);
							array_pop($temp_opened_arg);

							while (!empty($temp_opened))
							{
								$temp_tag = array_pop($temp_opened);
								$temp_arg = array_pop($temp_opened_arg);
								if (empty($temp_arg))
									$temp .= '['.$temp_tag.']';
								else
									$temp .= '['.$temp_tag.'='.$temp_arg.']';
								array_push($open_tags, $temp_tag);
								array_push($open_args, $temp_arg);
							}
							$current .= $temp;
							$opened_tag--;
							break;
						}
						else
						{
							// We couldn't fix nesting
							$errors[] = sprintf($lang_common['BBCode error no opening tag'], $current_tag);
							return false;
						}
					}
					else if (in_array($open_tags[$opened_tag], $tags_closed))
						break;
					else
					{
						array_pop($open_tags);
						array_pop($open_args);
						$opened_tag--;
					}
				}
			}

			if (in_array($current_tag, array_keys($tags_nested)))
			{
				if (isset($current_depth[$current_tag]))
					$current_depth[$current_tag]--;
			}

			if (in_array($open_tags[$opened_tag], array_keys($tags_limit_bbcode)))
				$limit_bbcode = $tags_limit_bbcode[$open_tags[$opened_tag]];
			else
				$limit_bbcode = $tags;

			$new_text .= $current;

			continue;
		}
		else
		{
			// We are opening a tag
			if (in_array($current_tag, array_keys($tags_limit_bbcode)))
				$limit_bbcode = $tags_limit_bbcode[$current_tag];
			else
				$limit_bbcode = $tags;

			if (in_array($current_tag, $tags_block) && !in_array($open_tags[$opened_tag], $tags_block) && $opened_tag != 0)
			{
				// We tried to open a block tag within a non-block tag
				$errors[] = sprintf($lang_common['BBCode error invalid nesting'], $current_tag, $open_tags[$opened_tag]);
				return false;
			}

			if (in_array($current_tag, $tags_ignore))
			{
				// It's an ignore tag so we don't need to worry about what's inside it
				$current_ignore = $current_tag;
				$count_ignored[$current_tag] = 1;
				$new_text .= $current;
				continue;
			}

			// Deal with nested tags
			if (in_array($current_tag, $open_tags) && !in_array($current_tag, array_keys($tags_nested)))
			{
				// We nested a tag we shouldn't
				$errors[] = sprintf($lang_common['BBCode error invalid self-nesting'], $current_tag);
				return false;
			}
			else if (in_array($current_tag, array_keys($tags_nested)))
			{
				// We are allowed to nest this tag

				if (isset($current_depth[$current_tag]))
					$current_depth[$current_tag]++;
				else
					$current_depth[$current_tag] = 1;

				// See if we are nested too deep
				if ($current_depth[$current_tag] > $tags_nested[$current_tag])
				{
					$current_nest = $current_tag;
					continue;
				}
			}

			// Remove quotes from arguments for certain tags
			if (strpos($current, '=') !== false && in_array($current_tag, $tags_quotes))
			{
				$current = preg_replace('%\['.$current_tag.'=("|\'|)(.*?)\\1\]\s*%i', '['.$current_tag.'=$2]', $current);
			}

			if (in_array($current_tag, array_keys($tags_limit_bbcode)))
				$limit_bbcode = $tags_limit_bbcode[$current_tag];

			$open_tags[] = $current_tag;
			$open_args[] = $current_arg;
			$opened_tag++;
			$new_text .= $current;
			continue;
		}
	}

	// Check we closed all the tags we needed to
	foreach ($tags_closed as $check)
	{
		if (in_array($check, $open_tags))
		{
			// We left an important tag open
			$errors[] = sprintf($lang_common['BBCode error no closing tag'], $check);
			return false;
		}
	}

	if ($current_ignore)
	{
		// We left an ignore tag open
		$errors[] = sprintf($lang_common['BBCode error no closing tag'], $current_ignore);
		return false;
	}

	return $new_text;
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
		$content = preg_replace_callback($re_list, function($matches) { return preparse_list_tag($matches[2], $matches[1]); }, $content);
	}

	$items = explode('[*]', str_replace('\"', '"', $content));

	$content = '';
	foreach ($items as $item)
	{
		if (pun_trim($item) != '')
			$content .= '[*'."\0".']'.pun_trim($item)."\n";
	}

	return '[list='.$type.']'."\n".$content.'[/list]';
}


//
// Truncate URL if longer than 55 characters (add http:// or ftp:// if missing)
//
function handle_url_tag($url, $link = '', $bbcode = false)
{
	global $pun_config, $pun_user;

	$url = pun_trim($url);

	// Deal with [url][img]http://example.com/test.png[/img][/url]
	if (preg_match('%<img src=\"(.*?)\"%', $url, $matches))
		return handle_url_tag($matches[1], $url, $bbcode);

	$full_url = str_replace(array(' ', '\'', '`', '"'), array('%20', '', '', ''), $url);
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
		if (!empty($pun_config['o_board_redirect']) && ($pun_user['is_guest'] || $pun_config['o_board_redirectg'] != '1') && !preg_match('/'.$pun_config['o_board_redirect'].'/i',$full_url))
		{
			$full_url = 're.php?u='.urlencode(str_replace(array('http://', 'https://', 'ftp://'), array('http___', 'https___', 'ftp___'), $full_url));
			$url = str_replace(array('http://', 'https://', 'ftp://'), '', $url);
		}

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
		$content = preg_replace_callback($re_list, function($matches) { return handle_list_tag($matches[2], $matches[1]); }, $content);
	}

	$content = preg_replace('%\[\*\]%s', '<li class="list"/>', pun_trim($content));

	if ($type == '*')
		$content = '<ul type="disc" class="list">'.$content.'</ul>';
	if ($type == 's')
		$content = '<ul type="circle" class="list">'.$content.'</ul>';
	if ($type == 'a')
		$content = '<ol type="A" class="list">'.$content.'</ol>';
	if ($type == '1')
		$content = '<ol type="1" class="list">'.$content.'</ol>';

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
		$text = preg_replace_callback('%\[quote=(&quot;|&\#039;|"|\'|)([^\r\n]*?)\\1\]%s', function($matches) use ($lang_common) { return '</p><div class="quotebox"><cite>'.str_replace(array('[', '\\"'), array('&#91;', '"'), $matches[2])." {$lang_common['wrote']}</cite><blockquote><div><p>"; }, $text);
		$text = preg_replace('%\s*\[\/quote\]%S', '</p></div></blockquote></div><p>', $text);
	}
	if (!$is_signature)
	{
		$pattern_callback[] = $re_list;
		$replace_callback[] = function($matches) { return handle_list_tag($matches[2], $matches[1]); };
	}

	$pattern[] = '%\[b\](.*?)\[/b\]%s';
	$pattern[] = '%\[i\](.*?)\[/i\]%s';
	$pattern[] = '%\[u\](.*?)\[/u\]%s';
	$pattern[] = '%\[s\](.*?)\[/s\]%s';
	$pattern[] = '%\[del\](.*?)\[/del\]%s';
	$pattern[] = '%\[ins\](.*?)\[/ins\]%s';
	$pattern[] = '%\[em\](.*?)\[/em\]%s';
	$pattern[] = '%\[colou?r=([a-zA-Z]{3,20}|\#[0-9a-fA-F]{6}|\#[0-9a-fA-F]{3})](.*?)\[/colou?r\]%s';
	$pattern[] = '%\[h\](.*?)\[/h\]%s';
	$pattern[] = '%\[font=(.*?)\](.*?)\[/font\]%s';
	$pattern[] = '%\[align=(.*?)\](.*?)\[/align\]%s';
	$pattern[] = '%\[hr ?/?\][\r\n]?%';
	$pattern[] = '%[\r\n]?\[table\][\r\n]*(.*?)[\r\n]*\[/table\]%s';
	$pattern[] = '%[\r\n]?\[caption\][\r\n]*(.*?)[\r\n]*\[/caption\]%s';
	$pattern[] = '%[\r\n]?\[tr\][\r\n]*(.*?)[\r\n]*\[/tr\]%s';
	$pattern[] = '%[\r\n]?\[td\][\r\n]*(.*?)[\r\n]*\[/td\]%s';
	$pattern[] = '%\[s\](.*?)\[/s\]%s';
	$pattern[] = '%\[pre\](.*?)\[/pre\]%s';
	$pattern[] = '%\[sup\](.*?)\[/sup\]%s';
	$pattern[] = '%\[sub\](.*?)\[/sub\]%s';
	$pattern[] = '%\[h\](.*?)\[/h\]%s';
	$pattern[] = '%\[spoiler\](.*?)\[/spoiler\]%s';
	$pattern[] = '%\[spoiler=(.*?)\](.*?)\[/spoiler\]%s';

	$replace[] = '<strong>$1</strong>';
	$replace[] = '<em>$1</em>';
	$replace[] = '<span class="bbu">$1</span>';
	$replace[] = '<del>$1</del>';
	$replace[] = '<del>$1</del>';
	$replace[] = '<ins>$1</ins>';
	$replace[] = '<em>$1</em>';
	$replace[] = '<span style="color: $1">$2</span>';
	$replace[] = '</p><h5>$1</h5><p>';
    $replace[] = '<span style="font-family: $1">$2</span>';
	$replace[] = '<div align="$1">$2</div>';
	$replace[] = '</p><hr /><p>';
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
		$pattern_callback[] = '%\[img\]((ht|f)tps?://)([^\s<"\[\x00-\x1f]*?)\[/img\]%';
		$pattern_callback[] = '%\[img=([^\[\x00-\x1f]*?)\]((ht|f)tps?://)([^\s<"\[\x00-\x1f]*?)\[/img\]%';
		if ($is_signature)
		{
			$replace_callback[] = function($matches) { return handle_img_tag($matches[1].$matches[3], true); };
			$replace_callback[] = function($matches) { return handle_img_tag($matches[2].$matches[4], true, $matches[1]); };
		}
		else
		{
			$replace_callback[] = function($matches) { return handle_img_tag($matches[1].$matches[3], false); };
			$replace_callback[] = function($matches) { return handle_img_tag($matches[2].$matches[4], false, $matches[1]); };

			$pattern_callback[] = '%\[imgr\]((ht|f)tps?://)([^\s<"\[\x00-\x1f]*?)\[/imgr\]%';
			$pattern_callback[] = '%\[imgr=([^\[\x00-\x1f]*?)\]((ht|f)tps?://)([^\s<"\[\x00-\x1f]*?)\[/imgr\]%';
			$pattern_callback[] = '%\[imgl\]((ht|f)tps?://)([^\s<"\[\x00-\x1f]*?)\[/imgl\]%';
			$pattern_callback[] = '%\[imgl=([^\[\x00-\x1f]*?)\]((ht|f)tps?://)([^\s<"\[\x00-\x1f]*?)\[/imgl\]%';

			$replace_callback[] = function($matches) { return handle_img_tag($matches[1].$matches[3], false, null, 'right'); };
			$replace_callback[] = function($matches) { return handle_img_tag($matches[2].$matches[4], false, $matches[1], 'right'); };
			$replace_callback[] = function($matches) { return handle_img_tag($matches[1].$matches[3], false, null, 'left'); };
			$replace_callback[] = function($matches) { return handle_img_tag($matches[2].$matches[4], false, $matches[1], 'left'); };
		}
	}

	$pattern_callback[] = '%\[noindex\](.*?)\[/noindex\]%s';
	$pattern_callback[] = '%\[url\]([^\[\x00-\x1f]*?)\[/url\]%';
	$pattern_callback[] = '%\[url=([^\[\x00-\x1f]+?)\]([^\x00\x01]*?)\[/url\]%';
	$pattern[] = '%\[email\]([^\[\x00-\x1f]+?@[^\[\x00-\x1f]+?)\[/email\]%';
	$pattern[] = '%\[email=([^\[\x00-\x1f]+?@[^\[\x00-\x1f]+?)\]([^\x00\x01]*?)\[/email\]%';

	$replace_callback[] = function($matches) { return handle_noindex_tag($matches[1]); };
	$replace_callback[] = function($matches) { return handle_url_tag($matches[1]); };
	$replace_callback[] = function($matches) { return handle_url_tag($matches[1], $matches[2]); };
	$replace[] = '<a href="mailto:$1">$1</a>';
	$replace[] = '<a href="mailto:$1">$2</a>';

	// This thing takes a while! :)
	$text = preg_replace($pattern, $replace, $text);
	$count = count($pattern_callback);
	for($i = 0 ; $i < $count ; $i++)
	{
		$text = preg_replace_callback($pattern_callback[$i], $replace_callback[$i], $text);
	}

	if (strpos((string)$text, 'quote') !== false)
	{
		$text = str_replace('[quote]', '</p><blockquote><div class="incqbox"><p>', $text);
		//$text = preg_replace('#\[quote=(&quot;|"|\'|)(.*)\\1\]#seU', '"</p><blockquote><div class=\"incqbox\"><h4>".str_replace(\'[\', \'&#91;\', \'$2\')." ".$lang_common[\'wrote\'].": </h4><p>"', $text);
		$text = preg_replace_callback('%\[quote=(&quot;|&\#039;|"|\'|)([^\r\n]*?)\\1\]%s', function($matches) use ($lang_common) { return '"</p><blockquote><div class=\"incqbox\"><h4>"'.str_replace(array('[', '\\"'), array('&#91;', '"'), $matches[2])." {$lang_common['wrote']}: </h4><p>"; }, $text);

		$text = preg_replace('%\[\/quote\]%S', '</p></div></blockquote><p>', $text);
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
	$text = preg_replace_callback('%(?<=[\s\]\)])(<)?(\[)?(\()?([\'"]?)(https?|ftp|news){1}://([\p{L}\p{N}\-]+\.([\p{L}\p{N}\-]+\.)*[\p{L}\p{N}]+(:[0-9]+)?(/(?:[^\s\[]*[^\s.,?!\[;:-])?)?)\4(?(3)(\)))(?(2)(\]))(?(1)(>))(?![^\s]*\[/(?:url|img|imgr|imgl)\])%ui', function ($matches) { return stripslashes($matches[1].$matches[2].$matches[3].$matches[4]).handle_url_tag($matches[5]."://".$matches[6], $matches[5]."://".$matches[6], true).stripslashes($matches[4].forum_array_key($matches, 10).forum_array_key($matches, 11).forum_array_key($matches, 12)); }, $text);
	$text = preg_replace_callback('%(?<=[\s\]\)])(<)?(\[)?(\()?([\'"]?)(www|ftp)\.(([\p{L}\p{N}\-]+\.)+[\p{L}\p{N}]+(:[0-9]+)?(/(?:[^\s\[]*[^\s.,?!\[;:-])?)?)\4(?(3)(\)))(?(2)(\]))(?(1)(>))(?![^\s]*\[/(?:url|img|imgr|imgl)\])%ui', function ($matches) { return stripslashes($matches[1].$matches[2].$matches[3].$matches[4]).handle_url_tag($matches[5].".".$matches[6], $matches[5].".".$matches[6], true).stripslashes($matches[4].forum_array_key($matches, 10).forum_array_key($matches, 11).forum_array_key($matches, 12)); }, $text);

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
		$parts = explode("\1", $text);
		$text = '';
		foreach ($parts as $i => $part)
		{
			$text .= $part;
			if (isset($inside[$i]))
			{
				$num_lines = ((substr_count($inside[$i], "\n")) + 3) * 1.5;
				$height_str = $num_lines > 35 ? '35em' : $num_lines.'em';
				$text .= '</p><div class="codebox"><div class="incqbox"><a href="#" style="float:right" onclick="return codeSelect(this)">'.$lang_common['Code select'].'</a><h4>'.$lang_common['Code'].':</h4><div class="scrollbox" style="height: '.$height_str.'"><pre>'.pun_trim($inside[$i], "\n\r").'</pre></div></div></div><p>';
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
