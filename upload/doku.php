<?php
/**
 * DokuWiki mainscript
 *
 * @license\t\tGPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author\t\t Andreas Gohr <andi@splitbrain.org>
 */
	define('PUN_ROOT', './');
	define('PUN_QUIET_VISIT', 1);
	require PUN_ROOT.'include/common.php';
	define('PUN_ALLOW_INDEX', 1);
	define('PUN_WIKI', 1);
	//DokuWiki is not as 1337 as PunBB
	error_reporting(E_ALL ^ E_NOTICE);
	
	//We need this to choose to load the punbb header or not
	$IDX = $_REQUEST['idx'];
	$ACT = $_REQUEST['do'];
	//we accept the do param as HTTP header, too:
	if(!empty($_SERVER['HTTP_X_DOKUWIKI_DO'])){
		$ACT = trim(strtolower($_SERVER['HTTP_X_DOKUWIKI_DO']));
	}
		
	if(!empty($IDX)) $ACT='index';
	//set defaults
	if(empty($ID))	$ID	= $conf['start'];
	if(empty($ACT)) $ACT = 'show';
	
	//are we doing a raw output?
	if(substr($ACT,0,6) != 'export')
		require PUN_ROOT.'header.php';
	else
		require PUN_ROOT.'wiki/common.php';
	
	
	//start output
	if(substr($ACT,0,6) != 'export') html_header();
	if(html_acl($permneed)){
		if($ACT == 'edit'){
			html_edit();
		}elseif($ACT == $lang['btn_preview']){
			html_edit($TEXT);
			html_show($TEXT);
		}elseif($ACT == 'wordblock'){
			html_edit($TEXT,'wordblock');
		}elseif($ACT == 'search'){
			html_search();
		}elseif($ACT == 'revisions'){
			html_revisions();
		}elseif($ACT == 'diff'){
			html_diff();
		}elseif($ACT == 'recent'){
			html_recent();
		}elseif($ACT == 'index'){
			html_index($IDX);
		}elseif($ACT == 'backlink'){
			html_backlinks();
		}elseif($ACT == 'conflict'){
			html_conflict(con($PRE,$TEXT,$SUF),$SUM);
			html_diff(con($PRE,$TEXT,$SUF),false);
		}elseif($ACT == 'locked'){
			html_locked($lockedby);
		}elseif($ACT == 'login'){
			html_login();
		}elseif($ACT == 'register' && $conf['openregister']){
			html_register();
		}elseif($ACT == 'export_html'){
			print parsedWiki($ID,$REV,false);
		}elseif($ACT == 'export_raw'){
			header("Content-Type: text/plain");
			print rawWiki($ID,$REV);
		}else{
			$ACT='show';
			html_show();
		}
	}
	if(substr($ACT,0,6) != 'export') html_footer();

	//restore old umask
	umask($conf['oldumask']);
	if(substr($ACT,0,6) != 'export') require PUN_ROOT.'footer.php';
?>
