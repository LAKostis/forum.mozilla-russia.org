<?php

/**
* Locate a byte index given a UTF-8 character index
* @version $Id: position.php,v 1.1 2006/10/01 00:01:31 harryf Exp $
* @package utf8
* @subpackage position
*/

/**
* Given a string and a character index in the string, in
* terms of the UTF-8 character position, returns the byte
* index of that character. Can be useful when you want to
* PHP's native string functions but we warned, locating
* the byte can be expensive
* Takes variable number of parameters - first must be
* the search string then 1 to n UTF-8 character positions
* to obtain byte indexes for - it is more efficient to search
* the string for multiple characters at once, than make
* repeated calls to this function
*
* @author Chris Smith<chris@jalakai.co.uk>
* @param string string to locate index in
* @param int (n times)
* @return mixed - int if only one input int, array if more
* @return boolean TRUE if it's all ASCII
* @package utf8
* @subpackage position
*/
function utf8_byte_position()
{
	$args = func_get_args();
	$str =& array_shift($args);

	if (!is_string($str))
		return false;

	$result = [];
	$prev = [0, 0]; // Trivial byte index, character offset pair
	$i = utf8_locate_next_chr($str, 300); // Use a short piece of str to estimate bytes per character. $i (& $j) -> byte indexes into $str
	$c = strlen(utf8_decode(substr($str, 0, $i))); // $c -> character offset into $str

	// Deal with arguments from lowest to highest
	sort($args);

	foreach ($args as $offset)
	{
		// Sanity checks FIXME

		// 0 is an easy check
		if ($offset == 0)
		{
			$result[] = 0; continue;
		}

		// Ensure no endless looping
		$safety_valve = 50;

		do
		{
			if (($c - $prev[1]) == 0)
			{
				// Hack: gone past end of string
				$error = 0;
				$i = strlen($str);
				break;
			}

			$j = $i + (int)(($offset-$c) * ($i - $prev[0]) / ($c - $prev[1]));
			$j = utf8_locate_next_chr($str, $j); // Correct to utf8 character boundary
			$prev = [$i, $c]; // Save the index, offset for use next iteration

			if ($j > $i)
				$c += strlen(utf8_decode(substr($str, $i, $j-$i))); // Determine new character offset
			else
				$c -= strlen(utf8_decode(substr($str, $j, $i-$j))); // Ditto

			$error = abs($c-$offset);
			$i = $j; // Ready for next time around
		}
		while (($error > 7) && --$safety_valve); // From 7 it is faster to iterate over the string

		if ($error && $error <= 7)
		{
			if ($c < $offset)
			{
				// Move up
				while ($error--)
					$i = utf8_locate_next_chr($str, ++$i);
			}
			else
			{
				// Move down
				while ($error--)
					$i = utf8_locate_current_chr($str, --$i);
			}

			// Ready for next arg
			$c = $offset;
		}

		$result[] = $i;
	}

	if (count($result) == 1)
		return $result[0];

	return $result;
}

/**
* Given a string and any byte index, returns the byte index
* of the start of the current UTF-8 character, relative to supplied
* position. If the current character begins at the same place as the
* supplied byte index, that byte index will be returned. Otherwise
* this function will step backwards, looking for the index where
* curent UTF-8 character begins
* @author Chris Smith<chris@jalakai.co.uk>
* @param string
* @param int byte index in the string
* @return int byte index of start of next UTF-8 character
* @package utf8
* @subpackage position
*/
function utf8_locate_current_chr( &$str, $idx )
{
	if ($idx <= 0)
		return 0;

	$limit = strlen($str);
	if ($idx >= $limit)
		return $limit;

	// Binary value for any byte after the first in a multi-byte UTF-8 character
	// will be like 10xxxxxx so & 0xC0 can be used to detect this kind
	// of byte - assuming well formed UTF-8
	while ($idx && ((ord($str[$idx]) & 0xC0) == 0x80))
		$idx--;

	return $idx;
}

/**
* Given a string and any byte index, returns the byte index
* of the start of the next UTF-8 character, relative to supplied
* position. If the next character begins at the same place as the
* supplied byte index, that byte index will be returned.
* @author Chris Smith<chris@jalakai.co.uk>
* @param string
* @param int byte index in the string
* @return int byte index of start of next UTF-8 character
* @package utf8
* @subpackage position
*/
function utf8_locate_next_chr(&$str, $idx)
{
	if ($idx <= 0)
		return 0;

	$limit = strlen($str);
	if ($idx >= $limit)
		return $limit;

	// Binary value for any byte after the first in a multi-byte UTF-8 character
	// will be like 10xxxxxx so & 0xC0 can be used to detect this kind
	// of byte - assuming well formed UTF-8
	while (($idx < $limit) && ((ord($str[$idx]) & 0xC0) == 0x80))
		$idx++;

	return $idx;
}
