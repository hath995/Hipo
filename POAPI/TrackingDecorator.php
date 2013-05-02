<?php
/**
	@author Aaron Elligsen
	@version 1.0
	@created 11/14/2011
	@modified 11/14/2011
	Adds change tracking capability to questions and pages
**/
/* DB Error Reporting standard
	{
		"err": true|false,
		"query": "$query",
		"errortext": "$db->get_error_text()",
		"error": "function and situation specific error description"
	} */
class TrackingDecorator extends Decorator {
	
	public function parse($functionname, array $requestdata)
	{
		switch($functionname)
		{
			case "trackingDbExist":
				$db = $this->path_session->get_db();
				$pagequery = "show tables like '{$this->pathway_name}_pages{$this->path_qualifier}_changes'";
				$pagesdb = false;
				$db->execute($pagequery);
				if($db->success())
				{
					if($db->num_rows() >0)
					{
						$pagesdb = true;	
					}
				}else{
					return $this->errorReport('true',$pagequery,$db->get_error_text(),__METHOD__,__LINE__,'There was a problem finding the page edit tracking database.');
				}
				
				$questionquery = "show tables like '{$this->pathway_name}_questions{$this->path_qualifier}_changes'";
				$questionsdb = false;
				$db->execute($pagequery);
				if($db->success())
				{
					if($db->num_rows() >0)
					{
						$questionsdb = true;	
					}
				}else{
					return $this->errorReport('true',$pagequery,$db->get_error_text(),__METHOD__,__LINE__,'There was a problem finding the question edit tracking database.');
				}
				$message = "The edit tracking tables :";
				if(!$pagesdb && !$questionsdb)
				{
					if(!$pagesdb)
					{
						$message .="<br>{$this->pathway_name}_pages{$this->path_qualifier}_changes <br>";	
					}
					if(!$questionsdb)
					{
						$message .="{$this->pathway_name}_questions{$this->path_qualifier}_changes <br>";	
					}
					$message .= "are missing. Changes will not be tracked without them/it.";
					return $this->errorReport(true,'N/A','N/A',__METHOD__,__LINE__,$message);
				}else{
					return json_encode(array('err'=>'false'));	
				}
				break;
				
			case "aggregateChanges":
				return $this->aggregateChanges();
				break;
			
			case "addPage":
			case "addPageAfter":
				$pageadded = $this->level->parse($functionname,$requestdata);
				$results = json_decode($pageadded);
				if($results->err ==false)
				{
					$tracked= $this->pageEdit($results->pages->id,$functionname,$pageadded);
					if(!is_array($tracked))
					{
						return $tracked;
						//return $pageadded;
					}else{
						return $pageadded;
					}
				}else{
					return $pageadded;	
				}
				break;
					
			case "editPage":
			case "deletePage":
				$res = $this->pageEdit(mysql_escape_string($requestdata['pageid']),mysql_escape_string($functionname));
				if(!is_array($res))
				{
					return $res;
				}
				break;
			
				
			case "addQuestion":
				$questionadded=$this->level->parse($functionname,$requestdata);
				$result = json_decode($questionadded);
				if($result->err ==false)
				{
					$tracked = $this->questionEdit($result->question->id,mysql_escape_string($functionname),$questionadded);
					if(!is_array($tracked))
					{
						return $tracked;
					}else{
						return $questionadded;	
					}
				}else{
					return $questionadded;	
				}
				break;
				
			case "deleteQuestion":
			case "editQuestion":
				$res = $this->questionEdit(mysql_escape_string($requestdata['qid']),mysql_escape_string($functionname));
				if(!is_array($res))
				{
					return $res;	
				}
				break;

			case "pageDown":
				break;
			
			case "addSection":
			case "renameSection":
			case "deleteSection":
			case "moveSection":
			case "renameLevel":/*Not much point in implementing*/
				break;
			case "reorderLevel":
				$reorderededit = $this->reorderEdit();
				$result = json_decode($$reorderededit);
				if($result->err ==false)
				{
					$output = $this->level->parse($functionname, $requestdata);
				}else{
					$output = $reorderededit;	
				}
				return $output;
				break;
			default:
				$output = $this->level->parse($functionname, $requestdata);
				return $output;
		}
		$output = $this->level->parse($functionname, $requestdata);
		return $output;
	}
	
	private function pageEdit($pageid,$editfunction,$originaljson=null) {
		$fields = $this->getPageFields();
		if(is_array($fields))
		{
			$fieldlist = implode(',',$fields);
			$editor = $this->path_session->get_user_object()->get_username();
			$query = "INSERT INTO {$this->pathway_name}_pages{$this->path_qualifier}_changes ($fieldlist,`editor`,`changefunction`) SELECT $fieldlist,'$editor' as `editor`,'$editfunction' as `changefunction` FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `IDX`=$pageid";
			$db = $this->path_session->get_db();
			$db->execute($query);
			if($db->success())
			{
				return array();	
			}else{
				if($originaljson ==null)
				{
					return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,'There was an error inserting a record into the page tracking database.');	
				}else{
					return $this->AppendErrorReport($originaljson,$query,$db->get_error_text(),__METHOD__,__LINE__,'There was an error inserting a record into the page tracking database.');
				}
			}
		}else{
			return $fields;	
		}
	}
	
	private function questionEdit($questionid,$editfunction,$originaljson=null) {
		$fields = $this->getQuestionFields();
		if(is_array($fields))
		{
			$fieldlist = implode(',',$fields);
			$editor = $this->path_session->get_user_object()->get_username();
			$query = "INSERT INTO {$this->pathway_name}_questions{$this->path_qualifier}_changes ($fieldlist,`editor`,`changefunction`) SELECT $fieldlist,'$editor' as `editor`,'$editfunction' as `changefunction` FROM {$this->pathway_name}_questions{$this->path_qualifier} WHERE `IDX`=$questionid";
			$db = $this->path_session->get_db();
			$db->execute($query);
			if($db->success())
			{
				return array();	
			}else{
				if($originaljson ==null)
				{
					return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,'There was an error inserting a record into the question tracking database.');
				}else{
					return $this->AppendErrorReport($originaljson,$query,$db->get_error_text(),__METHOD__,__LINE__,'There was an error inserting a record into the question tracking database.');
				}
			}
		}else{
			return $fields;	
		}
	}
	
	private function reorderEdit()
	{	$editor = $this->path_session->get_user_object()->get_username();
		$fields = $this->getPageFields();
		if(is_array($fields))
		{
			$fieldlist = implode(',',$fields);
			
			$query = "INSERT INTO {$this->pathway_name}_pages{$this->path_qualifier}_changes ($fieldlist,`editor`,`changefunction`) SELECT $fieldlist,'$editor' as `editor`,'reorderLevel' as `changefunction` FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `level_num`={$this->level_order}";
			$db = $this->path_session->get_db();
			$db->execute($query);
			if(!$db->success())
			{
				if($originaljson ==null)
				{
					return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,'There was an error inserting a record into the page tracking database.');	
				}
			}
		}else{
			return $fields;	
		}
		
		$fields = $this->getQuestionFields();
		if(is_array($fields))
		{
			$fieldlist = implode(',',$fields);
			$query = "INSERT INTO {$this->pathway_name}_questions{$this->path_qualifier}_changes ($fieldlist,`editor`,`changefunction`) SELECT $fieldlist,'$editor' as `editor`,'reorderLevel' as `changefunction` FROM {$this->pathway_name}_questions{$this->path_qualifier} WHERE `level_num`={$this->level_order}";
			$db = $this->path_session->get_db();
			$db->execute($query);
			if(!$db->success())
			{
				if($originaljson ==null)
				{
					return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,'There was an error inserting a record into the question tracking database.');
				}
			}
		}else{
			return $fields;	
		}
		return '{"err":false}';
	}
	
	protected function tcmp($a,$b){
		if($a['time'] == $b['time'])
		{
			return 0;	
		}
		if($a['time'] < $b['time'])
		{
			return 1;
		}else{
			return -1;
		}
	}
	
	public function aggregateChanges() {
		$changes = $this->getPageChanges();
		$changes = $this->getQuestionChanges($changes);
		usort($changes,array($this,"tcmp"));
		return json_encode(array("changes"=>$changes));
	}
	
	
	
	private function getPageChanges()
	{
		$query = "SELECT `level_num`,`section_num`,`page_num`,`editor`,`changefunction`,`edit_time` FROM {$this->pathway_name}_pages{$this->path_qualifier}_changes GROUP BY `edit_time`,`changefunction` ORDER BY `edit_time` DESC LIMIT 10";
		$db = $this->path_session->get_db();
		$db->execute($query);
		if($db->success())
		{
			$changes = array();
			while($row = $db->fetch_assoc2())
			{
				$action = $this->actionVerb($row['changefunction']);
				if($row['changefunction'] == 'reorderLevel')
				{
					$changes[] = array("time"=>strtotime($row['edit_time']),"message"=>"{$row['edit_time']}: <a href=\"\">{$row['editor']}</a> $action level {$row['level_num']}.");
				}else{
					$changes[] = array("time"=>strtotime($row['edit_time']),"message"=>"{$row['edit_time']}: <a href=\"\">{$row['editor']}</a> $action page {$row['level_num']}.{$row['section_num']}.{$row['page_num']} ");
				}
			}
			return $changes;
		}else{
			return array();
		}
	
	}
	
	private function actionVerb($action)
	{
		$verb ='edited';
		switch($action)
		{
			case "addQuestion":
			case "addPage":
			case "addPageAfter":
				$verb = 'added';
				break;
			case "editQuestion":
			case "editPage":
				//$verb = 'edited';
				break;
			case "deleteQuestion":
			case "deletePage":
				$verb = 'deleted';
				break;
			case "reorderLevel":
				$verb = 'reordered';
		}
		return $verb;
	}
	
	private function getQuestionChanges($existingchanges)
	{
		$query = "SELECT `question_number`,`level_num`,`section_num`,`page_num`,`editor`,`changefunction`,`record_touch` FROM {$this->pathway_name}_questions{$this->path_qualifier}_changes WHERE `changefunction`!='reorderLevel' GROUP BY `record_touch`,`changefunction` ORDER BY `record_touch` DESC LIMIT 10";
		$db = $this->path_session->get_db();
		$db->execute($query);
		if($db->success())
		{
			$changes = array();
			while($row = $db->fetch_assoc2())
			{
				$action = $this->actionVerb($row['changefunction']);
				{
					$existingchanges[] = array("time"=>strtotime($row['record_touch']),"message"=>"{$row['record_touch']}: <a href=\"\">{$row['editor']}</a> $action question {$row['question_number']} on page {$row['level_num']}.{$row['section_num']}.{$row['page_num']} ");
				}
			}
			return $existingchanges;
		}else{
			return array();
		}
	}
	
	private function sectionEdit() {
		
	}
	
	private function levelEdit() {
		
	}
	
	public function includeJS()
	{
		return $this->level->includeJS().'<script type="text/javascript" src="../POAPI/TrackingDecorator.js"></script><script type="text/javascript">var $username="'.$this->path_session->get_user_object()->get_username().'";</script>';	
	}
	
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
	
	public function install()
	{//ALTER TABLE  `hv_pages_changes` CHANGE  `IDX`  `IDX` SMALLINT( 5 ) UNSIGNED NOT NULL
		//also indexes
		$db = $this->path_session->get_db();
		$paths = $this->getPathways(); //get pathways
		foreach($paths as $path) //foreach pathway
		{
			//create page tracking table
			$query = "CREATE TABLE IF NOT EXISTS {$path}_changes LIKE {$path}";
			$db->execute($query);
			if(!$db->succesS())
			{
				return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,'Creating the tracking table failed.');
			}
			
			$query = "ALTER TABLE {$path}_changes DROP `touchme`";
			$db->execute($query);
			if(!$db->succesS())
			{
				return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,'Dropping the touch field failed.');
			}
			
			$query = "ALTER TABLE {$path}_changes ADD `editor` varchar(20) NOT NULL, ADD `changefunction` varchar(50) NOT NULL, ADD `edit_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, ADD `reverted` tinyint(4) NOT NULL, ADD `touchme` timestamp NOT NULL, CHANGE  `IDX`  `IDX` SMALLINT( 5 ) UNSIGNED NOT NULL,DROP INDEX `PRIMARY`,  ADD  `edit_id` INT NOT NULL AUTO_INCREMENT FIRST , ADD PRIMARY KEY (  `edit_id` )";
			$db->execute($query);
			if(!$db->succesS())
			{
				return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,'Adding the tracking fields to the page tracking database failed.');
			}
			
			//create question tracking table
			$path_parts = explode('_',$path);
			$pwname = $path_parts[0];
			$pwqualifier = "";
			if(count($path_parts)>2)
			{
				$pwqualifier = "_".$path_parts[2];
			}
			$query = "CREATE TABLE IF NOT EXISTS {$pwname}_questions{$pwqualifier}_changes LIKE {$pwname}_questions{$pwqualifier}";
			$db->execute($query);
			if(!$db->succesS())
			{
				return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,'Creating the tracking table failed.');
			}
			
			$query = "ALTER TABLE {$pwname}_questions{$pwqualifier}_changes DROP `touchme`";
			$db->execute($query);
			if(!$db->succesS())
			{
				return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,'Dropping the touch field failed.');
			}
			$query = "ALTER TABLE {$pwname}_questions{$pwqualifier}_changes ADD `editor` varchar(20) NOT NULL, ADD `changefunction` varchar(50) NOT NULL, ADD `record_touch` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, ADD `reverted` tinyint(4) NOT NULL, ADD `touchme` timestamp NOT NULL, CHANGE  `IDX`  `IDX` SMALLINT( 5 ) UNSIGNED NOT NULL, DROP INDEX `PRIMARY`,  ADD  `edit_id` INT NOT NULL AUTO_INCREMENT FIRST , ADD PRIMARY KEY (  `edit_id` )";
			$db->execute($query);
			if(!$db->succesS())
			{
				return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,'Adding the tracking fields to the question tracking database failed.');
			}
		}
		return true;
	}
	
	public function uninstall()
	{
		$db = $this->path_session->get_db();
		$paths = $this->getPathways(); //get pathways
		foreach($paths as $path) //foreach pathway
		{
			//create page tracking table
			$query = "CREATE TABLE IF NOT EXISTS {$path}_changes LIKE {$path}";
			$db->execute($query);
			if(!$db->succesS())
			{
				return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,'Creating the tracking table failed.');
			}
			
			$query = "ALTER TABLE {$path}_changes DROP `editor`, DROP `changefunction` , DROP `edit_time`, DROP `reverted`";
			$db->execute($query);
			if(!$db->succesS())
			{
				return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,'Adding the tracking fields to the page tracking database failed.');
			}
			
			//create question tracking table
			$path_parts = explode('_',$path);
			$pwname = $path_parts[0];
			$pwqualifier = "";
			if(count($path_parts)>2)
			{
				$pwqualifier = "_".$path_parts[2];
			}
			$query = "CREATE TABLE IF NOT EXISTS {$pwname}_questions{$pwqualifier}_changes LIKE {$pwname}_questions{$pwqualifier}";
			$db->execute($query);
			if(!$db->succesS())
			{
				return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,'Creating the tracking table failed.');
			}
			$query = "ALTER TABLE {$pwname}_questions{$pwqualifier}_changes DROP `editor`, DROP `changefunction`, DROP `record_touch`, DROP `reverted`";
			$db->execute($query);
			if(!$db->succesS())
			{
				return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,'Adding the tracking fields to the question tracking database failed.');
			}
		}
		return true;
	}
	
	
}

?>
