<?php
/**
 * UTF-8 aware substr_replace.
 *
 * @package php-utf8
 * @subpackage functions
 * @see http://www.php.net/substr_replace
 * @uses utf8_strlen
 * @uses utf8_substr
 * @param string $str
 * @param string $repl
 * @param int $start
 * @param int $length
 * @return string
 */
function mb_substr_replace($str, $repl, $start, $length = null)
{
	preg_match_all('/./us', $str, $ar);
	preg_match_all('/./us', $repl, $rar);
	$length = is_int($length) ? $length : utf8_strlen($str);
	array_splice($ar[0], $start, $length, $rar[0]);
	return implode($ar[0]);
}

function mb_str_replace_once($search, $replace, $text)
{
   $pos = mb_strpos($text, $search);
   return $pos!==false ? mb_substr_replace($text, $replace, $pos, mb_strlen($search)) : $text;
}
//Такая запись моет быть доступна только с верси php >= 7.0.
//~ use function mb_str_replace_once as str_replace_once;


function is_json($string, bool $associative = null)
{
   $result = json_decode($string, $associative);
   return (json_last_error() === JSON_ERROR_NONE) ? $result : null;
}
