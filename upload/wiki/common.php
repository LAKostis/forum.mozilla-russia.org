<?php

	//gets all the wiki stuff to use in header.php :D
	
	require_once("wiki/conf/dokuwiki.php");
	require_once("wiki/inc/common.php");
	require_once("wiki/inc/html.php");
	require_once("wiki/inc/parser.php");
	require_once("lang/English/lang.php");
	@include("lang/".$pun_user['language']."/lang.php");
	require_once("wiki/inc/auth.php");

	//import variables
	$QUERY = trim($_REQUEST['id']);
	$ID		= cleanID($_REQUEST['id']);
	$REV	 = $_REQUEST['rev'];
	$ACT	 = $_REQUEST['do'];
	$IDX	 = $_REQUEST['idx'];
	$DATE	= $_REQUEST['date'];
	$RANGE = $_REQUEST['lines'];
	$HIGH	= $_REQUEST['s'];
	if(empty($HIGH)) $HIGH = getGoogleQuery();

	$TEXT	= cleanText($_POST['wikitext']);
	$PRE	 = cleanText($_POST['prefix']);
	$SUF	 = cleanText($_POST['suffix']);
	$SUM	 = $_REQUEST['summary'];

	//we accept the do param as HTTP header, too:
	if(!empty($_SERVER['HTTP_X_DOKUWIKI_DO'])){
		$ACT = trim(strtolower($_SERVER['HTTP_X_DOKUWIKI_DO']));
	}

	if(!empty($IDX)) $ACT='index';
	//set defaults
	if(empty($ID))	$ID	= $conf['start'];
	if(empty($ACT)) $ACT = 'show';


	if($ACT == 'debug'){
		html_debug();
		exit;
	}

	//already logged in?
	if($_SERVER['REMOTE_USER'] && $ACT=='login') $ACT='show';
	//handle logout
	if($ACT=='logout'){
		auth_logoff();
		$ACT='login';
	}

	//handle register
	if($ACT=='register' && register()){
		$ACT='login';
	}

	//do saving after spam- and conflictcheck
	if($ACT == $lang['btn_save'] && auth_quickaclcheck($ID)){
		if(checkwordblock()){
			//spam detected
			$ACT = 'wordblock';
		}elseif($DATE != 0 && @filemtime(wikiFN($ID)) > $DATE ){
			//newer version available -> ask what to do
			$ACT = 'conflict';
		}else{
			//save it
			saveWikiText($ID,con($PRE,$TEXT,$SUF,1),$SUM); //use pretty mode for con
			//unlock it
			unlock($id);
			//show it
			header("Location: ".wl($ID, '','doku.php',true));
			exit();
		}
	}

	//make infos about current page available
	$INFO = pageinfo();

	//Editing: check if locked by anyone - if not lock for my self
	if(($ACT == 'edit' || $ACT == $lang['btn_preview']) && $INFO['editable']){
		$lockedby = checklock($ID);
		if($lockedby){
			$ACT = 'locked';
		}else{
			lock($ID);
		}
	}else{
		//try to unlock
		unlock($ID);
	}


	//display some infos
	if($ACT == 'check'){
		check();
		$ACT = 'show';
	}

	//check if searchword was given - else just show
	if($ACT == 'search' && empty($QUERY)){
		$ACT = 'show';
	}

	//check which permission is needed
	if(in_array($ACT,array('preview','wordblock','conflict','lockedby'))){
		if($INFO['exists']){
			$permneed = AUTH_EDIT;
		}else{
			$permneed = AUTH_CREATE;
		}
	}elseif(in_array($ACT,array('login','register','search','recent'))){
		$permneed = AUTH_NONE;
	}else{
		$permneed = AUTH_READ;
	}
