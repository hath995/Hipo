<?php

class FeedbackDecorator extends Decorator {

	public function parse($functionname, array $requestdata)
	{
		switch($functionname)
		{
			case "editQuestion":
				$db = $this->path_session->get_db();
				$p_data = array();
				foreach($requestdata['pdata'] as $datum)
				{
					$p_data[$datum['name']] = $datum['value'];	
				}
				$query = "UPDATE {$this->pathway_name}_questions{$this->path_qualifier} SET `incorrect_feedback`='".mysql_escape_string($p_data['q_feedback'])."' WHERE `IDX`={$requestdata['qid']}";
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
				$query = "SELECT `incorrect_feedback` FROM {$this->pathway_name}_questions{$this->path_qualifier} WHERE `IDX`={$requestdata['qid']}";
				$db->execute($query);
				if($db->success())
				{
					$row = $db->fetch_assoc2();
					$ret_value = json_decode($output);
					$ret_value->question->incorrect_feedback =$row['incorrect_feedback']; 
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
		return '<script type="text/javascript" src="../POAPI/FeedbackDecorator.js"></script>'."\n".$this->level->includeJS();
	}
}

?>
