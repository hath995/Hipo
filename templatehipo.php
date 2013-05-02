<?php
/**
	@version 1.6
	@modified 4/27/2011
*/
include_once('includes/session.class.php'); 			
include_once('../POAPI/BabynetPathway.php');
require_once('pat/patErrorManager.php'); 	
require_once('pat/patTemplate.php');
/*TODO
mode and track checker
SHOW TABLES FROM  `babynet` LIKE  'pals_levels%' //% is wildcard
(pals|hv)_pages(_es)?$
*/

if($session->is_logged_in())
{
	$track = "pals";
	if($_GET['track'] != null)
	{
		$track = $_GET['track'];
	}
	
	$mode = "";
	$language = "";
	if($_GET['mode'] != null)
	{
		$mode = '_'.$_GET['mode'];
		if($_GET['mode'] == 'es')
		{
			$language = "Spanish ";
		}
	}
	
	$pals_pathway = new BabynetPathway($track,$mode, $session);
	$_SESSION['pals_pathway'] = serialize($pals_pathway);
	
	
	
	$tmpl;
	$lo = "";
	if(($levels = $pals_pathway->getLevelList()) != false){
		ksort($levels);
		foreach($levels as $key => $value)
		{
			$lo .= "<option value=\"$key\">$key. $value</option>";	
		}
	}else{
		$lo .= '<option value="">Empty</option>';	
	}
	
	$pathwaylist = $pals_pathway->getPathways();
	$pathwaylinks = "";
	foreach($pathwaylist as $pathway)
	{
		if($pathway != $track.'_pages'.$mode)
		{
			$parts = explode('_',$pathway);
			$pmode = (count($parts) > 2 ? $parts[2] : "");
			$pathwaylinks .= " <a href=\"./templatehipo.php?track={$parts[0]}&mode=$pmode\">{$parts[0]} $pmode Pages </a> ";	
		}
	}
	
	$tmpl = new patTemplate();
	//$tmpl->setRoot('/sandisk1/projects/farmweb/research.ori.org/htdocs/POAPI');
	$tmpl->setRoot('../POAPI');
	$tmpl->setType('html');
	$tmpl->readTemplatesFromInput('page_editor_dev.tmpl','File',array('relative'=>true));
	$tmpl->addVar('page_editor','PATHWAY_LINKS',$pathwaylinks);
	$tmpl->addVar('page_editor','LEVELS',$lo);
	$tmpl->addVar('page_editor','PROJECT_NAME','Babynet ');
	$tmpl->addVar('page_editor','PATHWAY',$track);
	$tmpl->addVar('page_editor','LANGUAGE',$language);
	$tmpl->addVar('page_editor','DECORATOR_SCRIPTS',$pals_pathway->includeDecoratorJS());
	/*$tmpl->addVar('peScoreable','HAS_SCOREABLE','yes');
	$tmpl->addVar('peJsScoreable','HAS_SCOREABLE','yes');
	$tmpl->addVar('peJsScoreable2','HAS_SCOREABLE','yes');
	$tmpl->addVar('peJsScoreable3','HAS_SCOREABLE','yes');
	$tmpl->addVar('peJsScoreable4','HAS_SCOREABLE','yes');*/
	$tmpl->displayParsedTemplate();
}else{
	echo ">:-p";	
}
				
?>                                          

