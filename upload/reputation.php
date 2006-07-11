<?php
/***********************************************************************

  Copyright (C) 2002, 2003, 2004, 2005  Rickard Andersson (rickard@punbb.org)
  Copyright (C) 2005-2006  LAKostis (lakostis@mozilla-russian.org)

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
define('PUN_ROOT', './');

require PUN_ROOT.'include/common.php';

if ($pun_user['is_guest'])
	message($lang_common['No permission']);
	
if (empty($_GET['id']))
	message($lang_common['Bad request']);

$id = intval($_GET['id']); //User ID

require PUN_ROOT.'lang/'.$pun_user['language'].'/reputation.php';

//Is rep. system  enabled ?
if($pun_config['o_reputation_enabled'] != '1')
  message($lang_reputation['Disabled']);
  
//Is ID valid?
$query = $db->query("select id from ".$db->prefix."users where id='".$id."';");
$target_user = $db->fetch_assoc($query);

//Check is user exists
if(empty($target_user["id"]))
  message($lang_reputation['No user']);
  
//Compare ID's
if($target_user["id"] == $pun_user['id'])
  message($lang_reputation['Silly user']);
  
//Check last reputation point given timestamp
if($pun_config['o_reputation_timeout'] > (time()-$pun_user['last_reputation_voice']))
  message($lang_reputation['Timeout 1'].$pun_config['o_reputation_timeout'].$lang_reputation['Timeout 2']);

//Plus or minus voice?
$plus = $minus = false;
if(isset($_GET["plus"]) && isset($_GET["minus"]))  {
  message($lang_reputation['Invalid voice value']);
} else {
  if(isset($_GET["plus"])) {
      $plus = true;
  }
  if(isset($_GET["minus"])) {
      $minus = true;
  }
}

//Add voice
if($plus) //Plus voice
  $db->query("UPDATE ".$db->prefix."users SET reputation_plus=reputation_plus+1 where id='".$id."';");
if($minus) //Minus voice
  $db->query("UPDATE ".$db->prefix."users SET reputation_minus=reputation_minus+1 where id='".$id."';");

//Update logged user last voice time
$db->query("UPDATE ".$db->prefix."users SET last_reputation_voice='".mktime()."' where id='".$pun_user["id"]."';");

//Redirect client back
header("Location: ".$_SERVER["HTTP_REFERER"]);
?>