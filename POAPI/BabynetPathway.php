<?php
include_once("Pathway.php");
include_once("LevelDecorator.php");
include_once("ScoreableDecorator.php");
include_once("TrackingDecorator.php");
include_once("FeedbackDecorator.php");
class BabynetPathway extends Pathway {

	public function __construct($name,$qualifier,$session) 
	{
		$this->path_session = $session;
		$db = $this->path_session->get_db();
		$this->pathway_name = $name;
		$this->pathway_levels = array();
		$this->path_qualifier = $qualifier;
		$query = "SELECT * FROM {$this->pathway_name}_levels{$this->path_qualifier};";
		$db->execute($query);
		$level_rows = array();
		if($db->success())
		{
			while($row = $db->fetch_assoc2())
			{
				$level_rows[] = $row;
			}
			foreach($level_rows as $level)
			{
				$this->pathway_levels[$level['level_num']]=new TrackingDecorator(new FeedbackDecorator(new ScoreableDecorator(new LevelDecorator(new Level($level['level_num'],$level['name'],$this->pathway_name,$this->path_qualifier,$this->path_session)))));
				//echo "blah? {$level['level_num']}";
			}
		}else{
			echo $db->get_error_text();	
		}
	}
	
	public function addLevel()
	{
		$newlevel = '';
		$db = $this->path_session->get_db();
		$query = "SELECT MAX(`level_num`) FROM {$this->pathway_name}_levels{$this->path_qualifier};";
		$db->execute($query);
		if($db->success())
		{
			$res = $db->fetch_assoc2();
			if($db->affected_rows() > 0)
			{
				$newlevel = $res['MAX(`level_num`)']+1;
			}else{
				$newlevel = 1;	
			}
			$query = "INSERT INTO {$this->pathway_name}_levels{$this->path_qualifier} (`level_num`,`name`) VALUES ('$newlevel','new level $newlevel');";
			$db->execute($query);
			if($db->success())
			{
				$this->pathway_levels[$newlevel]=new TrackingDecorator(new FeedbackDecorator(new ScoreableDecorator(new LevelDecorator(new Level($newlevel,"new level '.$newlevel.'",$this->pathway_name,$this->path_qualifier,$this->path_session)))));
				$_SESSION['pals_pathway'] = serialize($this);
				return '{"level_num":'.$newlevel.',"name":"new level '.$newlevel.'"}';	
			}else{
				return '{"err":"true"}';	
			}
		}
		
	}
	
	public function includeDecoratorJS()
	{ //add error handling here for pathways with no levels defined
		return $this->pathway_levels[1]->includeJS();
	}
	
	public function install() { //one time use function
		return $this->pathway_levels[1]->install();
	}
	
	public function uninstall() { //one time use function
		return $this->pathway_levels[1]->uninstall();
	}

}
