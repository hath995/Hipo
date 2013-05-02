<?php
/**
	@author Aaron Elligsen
	@version 2.0
	@created 8/30/2011
	@modified 11/14/2011
	Adds awareness of the Scoreable field in pals_questions in some projects
**/
class ScoreableDecorator extends Decorator {

	public function parse($functionname, array $requestdata)
	{
		switch($functionname)
		{
			case "scoreableDbExist":
				$fields = $this->getQuestionFields();
				$scoreable_found = false;
				if(is_array($fields))
				{
					foreach($fields as $field)
					{
						if($field == "scoreable")
						{
							$scoreable_found = true;	
						}
					}
					if($scoreable_found)
					{
						return json_encode(array('err'=>'false'));
					}else{
						return $this->errorReport(true,'N/A','N/A',__METHOD__,__LINE__,'The database is not properly configured for the scoreable field.');	
					}
				}else{
					return $fields;	
				}
				break;
			case "editQuestion":
				$output = '';
				$db = $this->path_session->get_db();
				$p_data = array();
				foreach($requestdata['pdata'] as $datum)
				{
					$p_data[$datum['name']] = $datum['value'];	
				}
				$q_scoreable=0;
				if($p_data['q_scoreable'] == 'on')
				{
					$q_scoreable = 1;
				}
				$query = "UPDATE {$this->pathway_name}_questions{$this->path_qualifier} SET `scoreable`='$q_scoreable' WHERE `IDX`={$requestdata['qid']}";
				$db->execute($query);
				if($db->success())
				{
					
					return $this->level->parse('editQuestion',$requestdata);	
				}else{
					//return '{"edit":"fail","query":"'.$this->jsonstringescape($query).'"}';
					return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"Question retrieval failed");
				}
				break;
			case "getQuestion":
				$output = $this->level->parse($functionname, $requestdata);
				
				$db = $this->path_session->get_db();
				$query = "SELECT scoreable FROM {$this->pathway_name}_questions{$this->path_qualifier} WHERE `IDX`={$requestdata['qid']}";
				$db->execute($query);
				if($db->success())
				{
					$row = $db->fetch_assoc2();
					$ret_value = json_decode($output);
					$ret_value->question->qscoreable =$row['scoreable']; 
					//$ret_string .=var_dump($ret_value);
					$ret_string .=json_encode($ret_value);
					//'"qscoreable":"'.$row['scoreable'].'",';

					return $ret_string;
					
				}else{
					return $this->appendErrorReport($output,$query,$db->get_error_text(),__METHOD__,__LINE__,"Question retrieval failed");	
				}
				break;
			default:
				$output = $this->level->parse($functionname, $requestdata);
				return $output;
		}
	}
	
	public function includeJS()
	{
		 return '<script type="text/javascript" src="../POAPI/ScoreableDecorator.js"></script>'."\n".$this->level->includeJS();
	}
	
	public static function install()
	{
		$query = "ALTER TABLE ";	
	}
	
	public static function uninstall()
	{
		$query="";
	}
	
}

?>
