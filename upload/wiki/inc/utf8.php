<?php
/**
 * UTF8 helper functions
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */

/**
 * URL-Encode a filename to allow unicodecharacters
 *
 * Slashes are not encoded
 *
 * When the second parameter is true the string will
 * be encoded only if non ASCII characters are detected -
 * This makes it safe to run it multiple times on the
 * same string (default is true)
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 * @see    urlencode
 */
function utf8_encodeFN($file,$safe=true){
  if($safe && preg_match('#^[a-zA-Z0-9/_\-.%]+$#',$file)){
    return $file;
  }
  $file = urlencode($file);
  return str_replace('%2F','/',$file);
}

/**
 * URL-Decode a filename
 *
 * This is just a wrapper around urldecode
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 * @see    urldecode
 */
function utf8_decodeFN($file){
  return urldecode($file);
}

/**
 * Checks if a string contains 7bit ASCII only
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function utf8_isASCII($str){
  for($i=0; $i<strlen($str); $i++){
    if(ord($str{$i}) >127) return false;
  }
  return true;
}

/**
 * Replace accented UTF-8 characters by unaccented ASCII-7 equivalents
 *
 * Use the optional parameter to just deaccent lower ($case = -1) or upper ($case = 1)
 * letters. Default is to deaccent both cases ($case = 0)
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 */
function utf8_deaccent($string,$case=0){
  if($case <= 0){
    global $UTF8_LOWER_ACCENTS;
    $string = str_replace(array_keys($UTF8_LOWER_ACCENTS),array_values($UTF8_LOWER_ACCENTS),$string);
  }
  if($case >= 0){
    global $UTF8_UPPER_ACCENTS;
    $string = str_replace(array_keys($UTF8_UPPER_ACCENTS),array_values($UTF8_UPPER_ACCENTS),$string);
  }
  return $string;
}

/**
 * UTF-8 lookup table for lower case accented letters
 *
 * This lookuptable defines replacements for accented characters from the ASCII-7
 * range. This are lower case letters only.
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 * @see    utf8_deaccent()
 */
$UTF8_LOWER_ACCENTS = [
  'à' => 'a', 'ô' => 'o', 'ď' => 'd', 'ḟ' => 'f', 'ë' => 'e', 'š' => 's', 'ơ' => 'o',
  'ß' => 'ss', 'ă' => 'a', 'ř' => 'r', 'ț' => 't', 'ň' => 'n', 'ā' => 'a', 'ķ' => 'k',
  'ŝ' => 's', 'ỳ' => 'y', 'ņ' => 'n', 'ĺ' => 'l', 'ħ' => 'h', 'ṗ' => 'p', 'ó' => 'o',
  'ú' => 'u', 'ě' => 'e', 'é' => 'e', 'ç' => 'c', 'ẁ' => 'w', 'ċ' => 'c', 'õ' => 'o',
  'ṡ' => 's', 'ø' => 'o', 'ģ' => 'g', 'ŧ' => 't', 'ș' => 's', 'ė' => 'e', 'ĉ' => 'c',
  'ś' => 's', 'î' => 'i', 'ű' => 'u', 'ć' => 'c', 'ę' => 'e', 'ŵ' => 'w', 'ṫ' => 't',
  'ū' => 'u', 'č' => 'c', 'ö' => 'oe', 'è' => 'e', 'ŷ' => 'y', 'ą' => 'a', 'ł' => 'l',
  'ų' => 'u', 'ů' => 'u', 'ş' => 's', 'ğ' => 'g', 'ļ' => 'l', 'ƒ' => 'f', 'ž' => 'z',
  'ẃ' => 'w', 'ḃ' => 'b', 'å' => 'a', 'ì' => 'i', 'ï' => 'i', 'ḋ' => 'd', 'ť' => 't',
  'ŗ' => 'r', 'ä' => 'ae', 'í' => 'i', 'ŕ' => 'r', 'ê' => 'e', 'ü' => 'ue', 'ò' => 'o',
  'ē' => 'e', 'ñ' => 'n', 'ń' => 'n', 'ĥ' => 'h', 'ĝ' => 'g', 'đ' => 'd', 'ĵ' => 'j',
  'ÿ' => 'y', 'ũ' => 'u', 'ŭ' => 'u', 'ư' => 'u', 'ţ' => 't', 'ý' => 'y', 'ő' => 'o',
  'â' => 'a', 'ľ' => 'l', 'ẅ' => 'w', 'ż' => 'z', 'ī' => 'i', 'ã' => 'a', 'ġ' => 'g',
  'ṁ' => 'm', 'ō' => 'o', 'ĩ' => 'i', 'ù' => 'u', 'į' => 'i', 'ź' => 'z', 'á' => 'a',
  'û' => 'u', 'þ' => 'th', 'ð' => 'dh', 'æ' => 'ae', 'µ' => 'u',
];

/**
 * UTF-8 lookup table for upper case accented letters
 *
 * This lookuptable defines replacements for accented characters from the ASCII-7
 * range. This are upper case letters only.
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 * @see    utf8_deaccent()
 */
$UTF8_UPPER_ACCENTS = [
  'à' => 'A', 'ô' => 'O', 'ď' => 'D', 'ḟ' => 'F', 'ë' => 'E', 'š' => 'S', 'ơ' => 'O',
  'ß' => 'Ss', 'ă' => 'A', 'ř' => 'R', 'ț' => 'T', 'ň' => 'N', 'ā' => 'A', 'ķ' => 'K',
  'ŝ' => 'S', 'ỳ' => 'Y', 'ņ' => 'N', 'ĺ' => 'L', 'ħ' => 'H', 'ṗ' => 'P', 'ó' => 'O',
  'ú' => 'U', 'ě' => 'E', 'é' => 'E', 'ç' => 'C', 'ẁ' => 'W', 'ċ' => 'C', 'õ' => 'O',
  'ṡ' => 'S', 'ø' => 'O', 'ģ' => 'G', 'ŧ' => 'T', 'ș' => 'S', 'ė' => 'E', 'ĉ' => 'C',
  'ś' => 'S', 'î' => 'I', 'ű' => 'U', 'ć' => 'C', 'ę' => 'E', 'ŵ' => 'W', 'ṫ' => 'T',
  'ū' => 'U', 'č' => 'C', 'ö' => 'Oe', 'è' => 'E', 'ŷ' => 'Y', 'ą' => 'A', 'ł' => 'L',
  'ų' => 'U', 'ů' => 'U', 'ş' => 'S', 'ğ' => 'G', 'ļ' => 'L', 'ƒ' => 'F', 'ž' => 'Z',
  'ẃ' => 'W', 'ḃ' => 'B', 'å' => 'A', 'ì' => 'I', 'ï' => 'I', 'ḋ' => 'D', 'ť' => 'T',
  'ŗ' => 'R', 'ä' => 'Ae', 'í' => 'I', 'ŕ' => 'R', 'ê' => 'E', 'ü' => 'Ue', 'ò' => 'O',
  'ē' => 'E', 'ñ' => 'N', 'ń' => 'N', 'ĥ' => 'H', 'ĝ' => 'G', 'đ' => 'D', 'ĵ' => 'J',
  'ÿ' => 'Y', 'ũ' => 'U', 'ŭ' => 'U', 'ư' => 'U', 'ţ' => 'T', 'ý' => 'Y', 'ő' => 'O',
  'â' => 'A', 'ľ' => 'L', 'ẅ' => 'W', 'ż' => 'Z', 'ī' => 'I', 'ã' => 'A', 'ġ' => 'G',
  'ṁ' => 'M', 'ō' => 'O', 'ĩ' => 'I', 'ù' => 'U', 'į' => 'I', 'ź' => 'Z', 'á' => 'A',
  'û' => 'U', 'Þ' => 'Th', 'Ð' => 'Dh', 'Æ' => 'Ae',
];

?>
