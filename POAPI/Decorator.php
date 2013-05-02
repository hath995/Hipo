<?php

abstract class Decorator {
	
	protected $level=null;
	protected $level_name="";
	protected $level_order=null;
	protected $path_qualifier = "";
	protected $pathway_name="";
	protected $path_session=null;	
	
	
	/**	
	 *Construct fills the fields needed for functions operating on the level
	 *
	 * @param $level Takes a level object from Level.php or a decorator
	 */
	
	public function __construct($level)
	{       
		$this->level = $level;
		$this->path_session	=$level->path_session;
		$this->level_order 	=$level->level_order; 
		$this->level_name 	=$level->level_name;
		$this->pathway_name 	=$level->pathway_name;
		$this->path_qualifier 	=$level->path_qualifier;
	}
	
	/**
		These two functions are abstract 
	**/
	abstract public function parse($functionname,array $requestdata);
	abstract public function includeJS();
	
	/* DB Error Reporting standard
	{
		"err": true|false, 
		"query": "$query",
		"errortext": "$db->get_error_text()",
		"function":"__METHOD__",
		"line":"__LINE__",
		"error": "function and situation specific error description"
	} */
	
	/**
		In the first version of the program error reporting was sporadic and frequently 
		unique to each function. This is my first attempt to solve that problem. 
		After writing it I realized it was very similiar to a stack trace and
		an Exception. I think if I did a version 3 I would refactor the error reporting
		to use Exceptions. 
	*/
	
	protected function errorReport($err, $query,$errortext, $functionname,$linenum ,$error)
	{
		$report = array('err'=>$err,'errorlist'=>array(array('query'=>$query,'errortext'=>$errortext,'function'=>$functionname, 'line'=>$linenum,'error'=>$error)));
		return json_encode($report);
		//return '{"err":"'.$err.'","query":"'.$query.'","errortext":"'.$errortext.'","function":"'.$functioname.'","line":"'.$linenum.'","error":"'.$error.'"}';
	}
	
	protected function appendErrorReport($currentoutput, $query,$errortext, $functionname,$linenum ,$error)
	{
		$currentoutput_obj = json_decode($currentoutput);
		$report = array('query'=>$query,'errortext'=>$errortext,'function'=>$functionname, 'line'=>$linenum,'error'=>$error);
		$currentoutput_obj->errorlist[] = $report;
		$currentoutput_obj->err = true;
		return json_encode($currentoutput_obj);
		//return '{"err":"'.$err.'","query":"'.$query.'","errortext":"'.$errortext.'","function":"'.$functioname.'","line":"'.$linenum.'","error":"'.$error.'"}';
	}
	
	/**
		A helper function for descendant decorators to gain knowledge of
		the fields existing in the pages table.
		
		@return array or json Depending on success.
	**/
	
	protected function getPageFields()
	{
		$query = "DESCRIBE {$this->pathway_name}_pages{$this->path_qualifier};";
		$db = $this->path_session->get_db();
		$db->execute($query);
		if($db->success())
		{
			$fields = array();
			while($row = $db->fetch_assoc2())
			{
					$fields[] = $row['Field'];
			}
			return $fields;
		}else{
			return $this->errorReport('true',$query,$db->get_error_text(),__METHOD__,__LINE__,'There was an error describing the pages databases.');	
		}
	}
	
	/**
		A helper function for descendant decorators to gain knowledge of
		the fields existing in the questions table.
		
		@return array or json Depending on success.
	**/
	protected function getQuestionFields()
	{
		$query = "DESCRIBE {$this->pathway_name}_questions{$this->path_qualifier};";
		$db = $this->path_session->get_db();
		$db->execute($query);
		if($db->success())
		{
			$fields = array();
			while($row = $db->fetch_assoc2())
			{
					$fields[] = $row['Field'];
			}
			return $fields;
		}else{
			return $this->errorReport('true',$query,$db->get_error_text(),__METHOD__,__LINE__,'There was an error describing the pages databases.');	
		}
	}
	
	public function getLevelName()
	{
		return $this->level_name;	
	}
	
	/**
		Foreign language characters can cause trouble if not handled properly
	**/
	public function jsonstringescape($pitastring)
	{	
		$retval = htmlentities($pitastring,ENT_QUOTES,"UTF-8");
		$retval = preg_replace('/(\n|\t|\r)/','',$retval);
		
		return $retval;
	}
	
}

?>
