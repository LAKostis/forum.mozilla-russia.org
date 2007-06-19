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


//
// This does exactly what preg_quote() does in PHP 4-ish
//
function phpbb_preg_quote($str, $delimiter)
{
	$text = preg_quote($str);
	$text = str_replace($delimiter, '\\' . $delimiter, $text);
	return $text;
}

//
// Encodes the given string for proper display for this encoding ... nabbed
// from php.net and modified. There is an alternative encoding method which
// may produce lesd output but it's questionable as to its worth in this
// scenario IMO
function encode($str)
{
	global $lang_common;

	if (($lang_common['lang_encoding'] == 'iso-8859-1') || ($lang_common['lang_encoding'] == ''))
	{
		return $str;
	}

	// define start delimimter, end delimiter and spacer
	$end = "?=";
	$charset = $lang_common['lang_encoding'];
	$start = "=?$charset?B?";
	$spacer = "$end\r\n $start";

	// determine length of encoded text within chunks and ensure length is even
	$length = 75 - strlen($start) - strlen($end);
	$length = floor($length / 2) * 2;

	// encode the string and split it into chunks with spacers after each chunk
	if ($lang_common['lang_encoding'] == 'utf-8')
		$str = base64_encode($str);
	else 	$str = chunk_split(base64_encode($str), $length, $spacer);
	
	// remove trailing spacer and add start and end delimiters
	$str = preg_replace('#' . phpbb_preg_quote($spacer, '#') . '$#', '', $str);

	return $start . $str . $end;
}

//
// Validate an e-mail address
//
function is_valid_email($email)
{
	if (strlen($email) > 50)
		return false;

	return preg_match('/^(([^<>()[\]\\.,;:\s@"\']+(\.[^<>()[\]\\.,;:\s@"\']+)*)|("[^"\']+"))@((\[\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\])|(([a-zA-Z\d\-]+\.)+[a-zA-Z]{2,}))$/', $email);
}


//
// Check if $email is banned
//
function is_banned_email($email)
{
	global $db, $pun_bans;

	foreach ($pun_bans as $cur_ban)
	{
		if ($cur_ban['email'] != '' &&
			($email == $cur_ban['email'] ||
			(strpos($cur_ban['email'], '@') === false && stristr($email, '@'.$cur_ban['email']))))
			return true;
	}

	return false;
}


//
// Wrapper for PHP's mail()
//
function pun_mail($to, $subject, $message, $from = '')
{
	global $pun_config, $lang_common;

	$mailer_name = $lang_common['Mailer'];
	// Default sender/return address
	if (!$from)
		$from = '"'.str_replace('"', '', encode($mailer_name).' '.encode($pun_config['o_board_title'])).'" <'.$pun_config['o_webmaster_email'].'>';

	// Do a little spring cleaning
	$to = trim(preg_replace('#[\n\r]+#s', '', $to));
	$subject = trim(preg_replace('#[\n\r]+#s', '', $subject));
	$from = trim(preg_replace('#[\n\r:]+#s', '', $from));

	$headers = 'From: '.$from."\r\n".'Date: '.date('r')."\r\n".'MIME-Version: 1.0'."\r\n".'Content-transfer-encoding: 8bit'."\r\n".'Content-type: text/plain; charset='.$lang_common['lang_encoding']."\r\n".'X-Mailer: PunBB Mailer';

	$subject = encode($subject);

	// Make sure all linebreaks are CRLF in message
	$message = str_replace("\n", "\r\n", pun_linebreaks($message));

	if ($pun_config['o_smtp_host'] != '')
		smtp_mail($to, $subject, $message, $headers);
	else
	{
		// Change the linebreaks used in the headers according to OS
		if (strtoupper(substr(PHP_OS, 0, 3)) == 'MAC')
			$headers = str_replace("\r\n", "\r", $headers);
		else if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN')
			$headers = str_replace("\r\n", "\n", $headers);

		mail($to, $subject, $message, $headers);
	}
}


//
// This function was originally a part of the phpBB Group forum software phpBB2 (http://www.phpbb.com).
// They deserve all the credit for writing it. I made small modifications for it to suit PunBB and it's coding standards.
//
function server_parse($socket, $expected_response)
{
	$server_response = '';
	while (substr($server_response, 3, 1) != ' ')
	{
		if (!($server_response = fgets($socket, 256)))
			error('Couldn\'t get mail server response codes. Please contact the forum administrator.', __FILE__, __LINE__);
	}

	if (!(substr($server_response, 0, 3) == $expected_response))
		error('Unable to send e-mail. Please contact the forum administrator with the following error message reported by the SMTP server: "'.$server_response.'"', __FILE__, __LINE__);
}


//
// This function was originally a part of the phpBB Group forum software phpBB2 (http://www.phpbb.com).
// They deserve all the credit for writing it. I made small modifications for it to suit PunBB and it's coding standards.
//
function smtp_mail($to, $subject, $message, $headers = '')
{
	global $pun_config;

	$recipients = explode(',', $to);

	// Are we using port 25 or a custom port?
	if (strpos($pun_config['o_smtp_host'], ':') !== false)
		list($smtp_host, $smtp_port) = explode(':', $pun_config['o_smtp_host']);
	else
	{
		$smtp_host = $pun_config['o_smtp_host'];
		$smtp_port = 25;
	}

	if (!($socket = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 15)))
		error('Could not connect to smtp host "'.$pun_config['o_smtp_host'].'" ('.$errno.') ('.$errstr.')', __FILE__, __LINE__);

	server_parse($socket, '220');

	if ($pun_config['o_smtp_user'] != '' && $pun_config['o_smtp_pass'] != '')
	{
		fwrite($socket, 'EHLO '.$smtp_host."\r\n");
		server_parse($socket, '250');

		fwrite($socket, 'AUTH LOGIN'."\r\n");
		server_parse($socket, '334');

		fwrite($socket, base64_encode($pun_config['o_smtp_user'])."\r\n");
		server_parse($socket, '334');

		fwrite($socket, base64_encode($pun_config['o_smtp_pass'])."\r\n");
		server_parse($socket, '235');
	}
	else
	{
		fwrite($socket, 'HELO '.$smtp_host."\r\n");
		server_parse($socket, '250');
	}

	fwrite($socket, 'MAIL FROM: <'.$pun_config['o_webmaster_email'].'>'."\r\n");
	server_parse($socket, '250');

	$to_header = 'To: ';

	@reset($recipients);
	while (list(, $email) = @each($recipients))
	{
		fwrite($socket, 'RCPT TO: <'.$email.'>'."\r\n");
		server_parse($socket, '250');

		$to_header .= '<'.$email.'>, ';
	}

	fwrite($socket, 'DATA'."\r\n");
	server_parse($socket, '354');

	fwrite($socket, 'Subject: '.$subject."\r\n".$to_header."\r\n".$headers."\r\n\r\n".$message."\r\n");

	fwrite($socket, '.'."\r\n");
	server_parse($socket, '250');

	fwrite($socket, 'QUIT'."\r\n");
	fclose($socket);

	return true;
}
