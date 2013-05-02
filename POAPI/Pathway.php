<?php
/**
	@version 1.5
	Essentially each program content series (levels,sections,pages) gets its
	own set of tables which vary based on name and langauge.
	I call these series pathways and this class represents them. 
**/
//include_once('../babynet/includes/session.class.php'); 			

include_once('Level.php');
class Pathway {

	//pathway_name:string
	protected $pathway_name = "";
	//pathway_levels:Level
	protected $pathway_levels = "";
	//path_session: Session
	protected $path_session = null;
	//path_qualifier: string (usually represents language selection)
	protected $path_qualifier = "";

	
	public function __construct($name,$qualifier,$session) 
	{
		$this->path_session = $session;
		$this->pathway_name = $name;
		$this->pathway_levels = array();
		$this->path_qualifier = $qualifier;
		$db = $this->path_session->get_db();
		$query = "SELECT * FROM {$this->pathway_name}_levels{$this->path_qualifier} ORDER BY `level_num`;";
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
				$this->pathway_levels[$level['level_num']]=new Level($level['level_num'],$level['name'],$this->pathway_name,$this->path_qualifier,$this->path_session);
				//echo "blah? {$level['level_num']}";
			}
		}else{
			echo $db->get_error_text();	
		}
	}
	
	/**
		Essentially each program content series gets its own set of tables which vary
		based on name and langauge. This method provides a list of all available series.
		
		@return array Contains the names of the series.
	**/
	public function getPathways()
	{
		$query = "SHOW TABLES LIKE '%pages%'";
		$db = $this->path_session->get_db();
		$db->execute($query);
		if($db->success())
		{
			$pathways = $db->fetch_assoc();
			$legitpaths = array();
			 foreach($pathways as $pathway)
			{
				foreach($pathway as $key => $val)
				{
					if(preg_match('/(pals|hv)_pages(_es)?$/',$val) > 0)
					{
						$legitpaths[] = $val;	
					}
				}
			} 
			return $legitpaths;
			
		}
	}
	
	/**
		Adds a new record to the level table
		
		@return json Contains new level info or failure code
	**/
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
				return '{"level_num":'.$newlevel.',"name":"new level '.$newlevel.'"}';	
			}else{
				return '{"err":"true"}';	
			}
		}
		
	}
	
	/**
		Provide all the levels for a pathway
		
		@return array containing level names
	**/
	public function getLevelList()
	{
		$assoc_array = array();
		foreach($this->pathway_levels as $key => $val)
		{
			$assoc_array[$key] = $val->getLevelName();
		}	
		return $assoc_array;
		
	}
	
	/**
		@param integer representing the level's position in the ordering 
		@return Level returns the requested Level object
	**/
	public function getLevel($level) {
		return $this->pathway_levels[$level];
	}
	
	/**
		@param name the new name for the level
		@param integer representing the level's position in the ordering
		@return json containing success or failure message
	**/
	public function renameLevel($name,$level)
	{
		$db = $this->path_session->get_db();
		$query = "UPDATE {$this->pathway_name}_levels{$this->path_qualifier} SET `name`='$name' WHERE `level_num`='$level'";
		$db->execute($query);
		if($db->success())
		{
			//$this->pathway_levels[$level]->setLevelName($name);
			return '{"edit":"success"}';	
		}else{
			return '{"edit":"fail"}';
			
		}
	}
	
	/**
		These were left unimplemented by design. Now that edit tracking
		is in place it might be okay to add them now.
	**/
	
	public function moveLevel($level,$new_position)
	{
		/*
			NOT YET IMPLEMENTED
			
		*/
	}
	
	public function deleteLevel()
	{
		/*
			NOT YET IMPLEMENTED
		*/
	}
	
	
	
?>
