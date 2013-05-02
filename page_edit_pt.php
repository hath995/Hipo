<?php
header("Cache-Control: no-cache");
header('Content-type: application/json');
//include_once('api/Section.php');
include_once('../POAPI/Level.php');
include_once('../POAPI/BabynetPathway.php');

include_once('includes/session.class.php'); 			
if($session->is_logged_in())
{
	if(!isset($_SESSION['pals_pathway']))
	{
		echo '{"err":true,"errorlist":[{"error":"You are not logged in. Please relog and try again."}]}';	
	}else{
		$pathway = unserialize($_SESSION['pals_pathway']);
		$pathway_level = $pathway->getLevel(mysql_real_escape_string($_POST['level']));
		switch(mysql_real_escape_string($_POST['function']))
		{
			case "addLevel":
				echo $pw->addLevel();
				break;
			case "renameLevel":
				echo $pw->renameLevel(mysql_real_escape_string($_POST['newname']),mysql_real_escape_string($_POST['level']));
				break;
			default:
			echo $p_level->parse(mysql_real_escape_string($_POST['function']),$_POST);
		}
	}
	
}else{	
	echo '{"err":true,"errorlist":[{"error":"You are not logged in. Please relog and try again."}]}';
}
?>
