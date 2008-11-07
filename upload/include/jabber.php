<?php
/***********************************************************************

  Copyright (C) 2008  Nikolay Shutnik (shutnik@mozilla-russia.org)

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
// Jabber messages sender
//
function pun_jabber($to, $message)
{
	global $pun_config;

	// Do a little spring cleaning
	$to = split(',', trim(preg_replace('#[\n\r]+#s', '', $to)));

	// Make sure all linebreaks are CRLF in message (and strip out any NULL bytes)
	$message = str_replace(array("\n", "\0"), array("\r\n", ''), pun_linebreaks($message));

	require PUN_ROOT.'include/XMPPHP/XMPP.php';
	require PUN_ROOT.'config_jabber.php';

	$jabber = new XMPPHP_XMPP($jabbot_connection_host, $jabbot_connection_port,
			$jabbot_user_name, $jabbot_user_pass, $jabbot_user_resource, $jabbot_user_host,
			$printlog=false, $loglevel=XMPPHP_Log::LEVEL_ERROR
	);

	$jabber->autoSubscribe();

	try {
		$jabber->connect();
		$jabber->processUntil('session_start');
		foreach ($to as $value)
			$jabber->message($value, $message);
		$jabber->disconnect();
	} catch(XMPPHP_Exception $e) {
		// die($e->getMessage());
	}

}
