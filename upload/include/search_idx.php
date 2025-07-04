<?php

/**
 * Copyright (C) 2008-2012 FluxBB
 * based on code by Rickard Andersson copyright (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

// The contents of this file are very much inspired by the file functions_search.php
// from the phpBB Group forum software phpBB2 (http://www.phpbb.com)


// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;


// Make a regex that will match CJK or Hangul characters
define('PUN_CJK_HANGUL_REGEX', '['.
	'\x{1100}-\x{11FF}'.		// Hangul Jamo							1100-11FF		(http://www.fileformat.info/info/unicode/block/hangul_jamo/index.htm)
	'\x{3130}-\x{318F}'.		// Hangul Compatibility Jamo			3130-318F		(http://www.fileformat.info/info/unicode/block/hangul_compatibility_jamo/index.htm)
	'\x{AC00}-\x{D7AF}'.		// Hangul Syllables						AC00-D7AF		(http://www.fileformat.info/info/unicode/block/hangul_syllables/index.htm)

	// Hiragana
	'\x{3040}-\x{309F}'.		// Hiragana								3040-309F		(http://www.fileformat.info/info/unicode/block/hiragana/index.htm)

	// Katakana
	'\x{30A0}-\x{30FF}'.		// Katakana								30A0-30FF		(http://www.fileformat.info/info/unicode/block/katakana/index.htm)
	'\x{31F0}-\x{31FF}'.		// Katakana Phonetic Extensions			31F0-31FF		(http://www.fileformat.info/info/unicode/block/katakana_phonetic_extensions/index.htm)

	// CJK Unified Ideographs	(http://en.wikipedia.org/wiki/CJK_Unified_Ideographs)
	'\x{2E80}-\x{2EFF}'.		// CJK Radicals Supplement				2E80-2EFF		(http://www.fileformat.info/info/unicode/block/cjk_radicals_supplement/index.htm)
	'\x{2F00}-\x{2FDF}'.		// Kangxi Radicals						2F00-2FDF		(http://www.fileformat.info/info/unicode/block/kangxi_radicals/index.htm)
	'\x{2FF0}-\x{2FFF}'.		// Ideographic Description Characters	2FF0-2FFF		(http://www.fileformat.info/info/unicode/block/ideographic_description_characters/index.htm)
	'\x{3000}-\x{303F}'.		// CJK Symbols and Punctuation			3000-303F		(http://www.fileformat.info/info/unicode/block/cjk_symbols_and_punctuation/index.htm)
	'\x{31C0}-\x{31EF}'.		// CJK Strokes							31C0-31EF		(http://www.fileformat.info/info/unicode/block/cjk_strokes/index.htm)
	'\x{3200}-\x{32FF}'.		// Enclosed CJK Letters and Months		3200-32FF		(http://www.fileformat.info/info/unicode/block/enclosed_cjk_letters_and_months/index.htm)
	'\x{3400}-\x{4DBF}'.		// CJK Unified Ideographs Extension A	3400-4DBF		(http://www.fileformat.info/info/unicode/block/cjk_unified_ideographs_extension_a/index.htm)
	'\x{4E00}-\x{9FFF}'.		// CJK Unified Ideographs				4E00-9FFF		(http://www.fileformat.info/info/unicode/block/cjk_unified_ideographs/index.htm)
	'\x{20000}-\x{2A6DF}'.		// CJK Unified Ideographs Extension B	20000-2A6DF		(http://www.fileformat.info/info/unicode/block/cjk_unified_ideographs_extension_b/index.htm)
']');


function split_words_clear_link_minor($arr)
{
	$res = '';

	foreach ($arr as $text)
	{
		if (is_array($text))
		{
			$res.= split_words_clear_link_minor($text);
			continue;
		}
		$text = utf8_strtolower(rawurldecode($text));
		$text = preg_replace('%[^\p{L}\p{N}]+%u', ' ', $text);
		$text = preg_replace('%\b((\d+(?=[a-z])|[a-z]+(?=\d)){3,}\w*)\b%u', ' ', $text);
		$text = preg_replace('%\b\d+|\d+\b%u', '', $text);
		$res.= $text.' ';
	}

	return $res;
}


function split_words_clear_link($url)
{
	$text = '';
	$arr = parse_url($url);

	if (isset($arr['host']))
	{
		$harr = explode('.', $arr['host']);
		$k = count($harr) - 1;
		for ($i = 0; $i < $k; $i++)
		{
			if ($k - $i == 1)
				$text.= $harr[$i].' ';
			else
				$text.= preg_replace('%(^\d+|\d+$)%u', '', $harr[$i]).' ';
		}
	}

	if (isset($arr['path']))
		$text.= split_words_clear_link_minor(explode('/', $arr['path']));

	if (isset($arr['query']))
	{
		parse_str($arr['query'], $qarr);
		$text.= split_words_clear_link_minor($qarr);
	}

	if (isset($arr['fragment']))
		$text.= split_words_clear_link_minor([$arr['fragment']]);

	return $text;
}


//
// "Cleans up" a text string and returns an array of unique words
// This function depends on the current locale setting
//
function split_words($text, $idx)
{
	// Remove BBCode
	$text = preg_replace('%\[/?(b|u|s|ins|del|em|i|h|colou?r|quote|code|img|url|email|list|topic|post|forum|user|imgr|imgl|hr|size|after|spoiler|right|center|justify|mono)(?:\=[^\]]*)?\]%', ' ', $text);

	$text = str_replace(['`', '’', 'ё'], ['\'', '\'', 'е'], $text); // Visman - for russian language

	// Visman - for clear local url
	if (strpos($text, '/img/members/') !== false)
		$text = preg_replace_callback('%[:/\w\.\-]*/img/members/\d+(/[^\s]+)%u', function($matches) { return split_words_clear_link($matches[1]); }, $text);

	// Visman - for clear url
	if (strpos($text, 'http') !== false)
		$text = preg_replace_callback('%\bhttps?://((?:[\p{L}\p{N}\-]+\.)+(?:xn\-\-)?[\p{L}\p{N}]+[^\s]*)%u', function($matches) { return split_words_clear_link('http://'.$matches[1]); }, $text);

	// Visman - for clear url
	if (strpos($text, 'www.') !== false)
		$text = preg_replace_callback('%\bwww\.((?:[\p{L}\p{N}\-]+\.)+(?:xn\-\-)?[\p{L}\p{N}]+[^\s]*)%u', function($matches) { return split_words_clear_link('http://'.$matches[1]); }, $text);

	// Remove any apostrophes or dashes which aren't part of words
	$text = preg_replace('%((?<![\p{L}\p{N}])[\'\-]|[\'\-](?![\p{L}\p{N}]))%u', '', $text);

	// Visman - for russian language
	if (strpos($text, '-') !== false)
	{
		$text = preg_replace('%\b([\p{L}\p{N}\-\']+\-(?:либо|нибу[дт]ь|нить))(?![\p{L}\p{N}\'\-])%u', '', $text); // удаляем слова целиком с хвостом -либо|нибу[дт]ь|нить
		$text = preg_replace('%(?<=[\p{L}\p{N}])(-(?:таки|чуть|[а-яё]{1,2}))+(?![\p{L}\p{N}\'\-])%u', '', $text); // удаляет из слова все хвосты с 1 или 2 русскими буквами или -таки|чуть
		$text = preg_replace('%(?<=\p{L}{3})\-(?=\p{L}{3,}\b)%u', ' ', $text); // слова с дефисом разбиваются на части если с каждой стороны от дефиса минимум по 3 буквы
	}

	// Visman - for all language
	$text = preg_replace('%(\p{L})\1{3,}%u', '\1', $text); // 4 и больше одинаковых букв меняем на одну

	// Remove punctuation and symbols (actually anything that isn't a letter or number), allow apostrophes and dashes (and % * if we aren't indexing)
	$text = preg_replace('%[^\p{L}\p{N}\'\-'.($idx ? '' : '\%\*').']+%u', ' ', $text);

	// Replace multiple whitespace
	$text = preg_replace('%\s{2,}%u', ' ', $text);

	// Fill an array with all the words
	$words = array_unique(explode(' ', $text));

	// Remove any words that should not be indexed
	foreach ($words as $key => $value)
	{
		// If the word shouldn't be indexed, remove it
		if (!validate_search_word($value, $idx))
			unset($words[$key]);
	}

	return $words;
}


//
// Checks if a word is a valid searchable word
//
function validate_search_word($word, $idx)
{
	static $stopwords;

	// If the word is a keyword we don't want to index it, but we do want to be allowed to search it
	if (is_keyword($word))
		return !$idx;

	if (!isset($stopwords))
	{
		$stopwords = (array)@file(PUN_ROOT.'lang/'.$pun_user['language'].'/stopwords.txt');
		$stopwords = array_map('trim', $stopwords);
	}

	// If it is a stopword it isn't valid
	if (in_array($word, $stopwords))
		return false;

	// If the word is CJK we don't want to index it, but we do want to be allowed to search it
	if (is_cjk($word))
		return !$idx;

	// Exclude % and * when checking whether current word is valid
	$word = str_replace(['%', '*'], '', $word);

	// Check the word is within the min/max length
	$num_chars = pun_strlen($word);
	return $num_chars >= PUN_SEARCH_MIN_WORD && $num_chars <= PUN_SEARCH_MAX_WORD;
}


//
// Check a given word is a search keyword.
//
function is_keyword($word)
{
	return $word == 'and' || $word == 'or' || $word == 'not';
}


//
// Check if a given word is CJK or Hangul.
//
function is_cjk($word)
{
	return preg_match('%^'.PUN_CJK_HANGUL_REGEX.'+$%u', $word) ? true : false;
}


//
// Strip [img] [url] and [email] out of the message so we don't index their contents
//
function strip_bbcode($text)
{
	static $patterns;

	if (!isset($patterns))
	{
		$patterns = [
			'%\[img=([^\]]*+)\]([^[]*+)\[/img\]%'									=>	' $2 $1 ',	// Keep the url and description
			'%\[imgr=([^\]]*+)\]([^[]*+)\[/imgr\]%'									=>	' $2 $1 ',	// Keep the url and description
			'%\[imgl=([^\]]*+)\]([^[]*+)\[/imgl\]%'									=>	' $2 $1 ',	// Keep the url and description
			'%\[(url|email)=([^\]]*+)\]([^[]*+(?:(?!\[/\1\])\[[^[]*+)*)\[/\1\]%'	=>	' $2 $3 ',	// Keep the url and text
			'%\[(img|imgr|imgl|url|email)\]([^[]*+(?:(?!\[/\1\])\[[^[]*+)*)\[/\1\]%'			=>	' $2 ',		// Keep the url
			'%\[(topic|post|forum|user)\][1-9]\d*\[/\1\]%'							=>	' ',		// Do not index topic/post/forum/user ID
		];
	}

	return preg_replace(array_keys($patterns), array_values($patterns), $text);
}


//
// Updates the search index with the contents of $post_id (and $subject)
//
function update_search_index($mode, $post_id, $message, $subject = null)
{
	global $db_type, $db;

	$message = utf8_strtolower($message);
	$subject = is_null($subject) ? '' : utf8_strtolower($subject);

	// Remove any bbcode that we shouldn't index
	$message = strip_bbcode($message);

	// Split old and new post/subject to obtain array of 'words'
	$words_message = split_words($message, true);
	$words_subject = ($subject) ? split_words($subject, true) : [];

	if ($mode == 'edit')
	{
		$result = $db->query('SELECT w.id, w.word, m.subject_match FROM '.$db->prefix.'search_words AS w INNER JOIN '.$db->prefix.'search_matches AS m ON w.id=m.word_id WHERE m.post_id='.$post_id, true) or error('Unable to fetch search index words', __FILE__, __LINE__, $db->error());

		// Declare here to stop array_keys() and array_diff() from complaining if not set
		$cur_words['post'] = [];
		$cur_words['subject'] = [];

		while ($row = $db->fetch_row($result))
		{
			$match_in = ($row[2]) ? 'subject' : 'post';
			$cur_words[$match_in][$row[1]] = $row[0];
		}

		$db->free_result($result);

		$words['add']['post'] = array_diff($words_message, array_keys($cur_words['post']));
		$words['add']['subject'] = array_diff($words_subject, array_keys($cur_words['subject']));
		$words['del']['post'] = array_diff(array_keys($cur_words['post']), $words_message);
		$words['del']['subject'] = array_diff(array_keys($cur_words['subject']), $words_subject);
	}
	else
	{
		$words['add']['post'] = $words_message;
		$words['add']['subject'] = $words_subject;
		$words['del']['post'] = [];
		$words['del']['subject'] = [];
	}

	unset($words_message);
	unset($words_subject);

	// Get unique words from the above arrays
	$unique_words = array_unique(array_merge($words['add']['post'], $words['add']['subject']));

	if (!empty($unique_words))
	{
		$result = $db->query('SELECT id, word FROM '.$db->prefix.'search_words WHERE word IN(\''.implode('\',\'', array_map([$db, 'escape'], $unique_words)).'\')', true) or error('Unable to fetch search index words', __FILE__, __LINE__, $db->error());

		$word_ids = [];
		while ($row = $db->fetch_row($result))
			$word_ids[$row[1]] = $row[0];

		$db->free_result($result);

		$new_words = array_diff($unique_words, array_keys($word_ids));
		unset($unique_words);

		if (!empty($new_words))
		{
			switch ($db_type)
			{
				case 'mysql':
				case 'mysqli':
					$db->query('INSERT INTO '.$db->prefix.'search_words (word) VALUES(\''.implode('\'),(\'', array_map([$db, 'escape'], $new_words)).'\')');
					break;

				default:
					foreach ($new_words as $word)
						$db->query('INSERT INTO '.$db->prefix.'search_words (word) VALUES(\''.$db->escape($word).'\')');
					break;
			}
		}

		unset($new_words);
	}

	// Delete matches (only if editing a post)
	foreach ($words['del'] as $match_in => $wordlist)
	{
		$subject_match = ($match_in == 'subject') ? 1 : 0;

		if (!empty($wordlist))
		{
			$sql = '';
			foreach ($wordlist as $word)
				$sql .= (($sql != '') ? ',' : '').$cur_words[$match_in][$word];

			$db->query('DELETE FROM '.$db->prefix.'search_matches WHERE word_id IN('.$sql.') AND post_id='.$post_id.' AND subject_match='.$subject_match) or error('Unable to delete search index word matches', __FILE__, __LINE__, $db->error());
		}
	}

	// Add new matches
	foreach ($words['add'] as $match_in => $wordlist)
	{
		$subject_match = ($match_in == 'subject') ? 1 : 0;

		if (!empty($wordlist))
			$db->query('INSERT INTO '.$db->prefix.'search_matches (post_id, word_id, subject_match) SELECT '.$post_id.', id, '.$subject_match.' FROM '.$db->prefix.'search_words WHERE word IN(\''.implode('\',\'', array_map([$db, 'escape'], $wordlist)).'\')') or error('Unable to insert search index word matches', __FILE__, __LINE__, $db->error());
	}

	unset($words);
}


//
// Strip search index of indexed words in $post_ids
//
function strip_search_index($post_ids)
{
	global $db_type, $db;

	switch ($db_type)
	{
		case 'mysql':
		case 'mysqli':
		{
			$result = $db->query('SELECT word_id FROM '.$db->prefix.'search_matches WHERE post_id IN('.$post_ids.') GROUP BY word_id') or error('Unable to fetch search index word match', __FILE__, __LINE__, $db->error());

			$word_ids = '';
			while ($row = $db->fetch_row($result))
				$word_ids .= ($word_ids != '') ? ','.$row[0] : $row[0];

			if ($word_ids != '')
			{
				$result = $db->query('SELECT word_id FROM '.$db->prefix.'search_matches WHERE word_id IN('.$word_ids.') GROUP BY word_id HAVING COUNT(word_id)=1') or error('Unable to fetch search index word match', __FILE__, __LINE__, $db->error());

				$word_ids = '';
				while ($row = $db->fetch_row($result))
					$word_ids .= ($word_ids != '') ? ','.$row[0] : $row[0];

				if ($word_ids != '')
				{
					$db->query('DELETE FROM '.$db->prefix.'search_words WHERE id IN('.$word_ids.')') or error('Unable to delete search index word', __FILE__, __LINE__, $db->error());
				}
			}

			break;
		}

		default:
			$db->query('DELETE FROM '.$db->prefix.'search_words WHERE id IN(SELECT word_id FROM '.$db->prefix.'search_matches WHERE word_id IN(SELECT word_id FROM '.$db->prefix.'search_matches WHERE post_id IN('.$post_ids.') GROUP BY word_id) GROUP BY word_id HAVING COUNT(word_id)=1)') or error('Unable to delete from search index', __FILE__, __LINE__, $db->error());
			break;
	}

	$db->query('DELETE FROM '.$db->prefix.'search_matches WHERE post_id IN('.$post_ids.')') or error('Unable to delete search index word match', __FILE__, __LINE__, $db->error());
}
