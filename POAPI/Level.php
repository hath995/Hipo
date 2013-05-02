<?php
require_once('DoublyLinkedList.php');
/**
	@author Aaron Elligsen
	@version 2.0
	
**/
include_once('../hib/includes/jsonwrapper/jsonwrapper.php');

class Level {
	
	public $level_name="";
	public $level_order=null;
	public $level_sections=null;
	public $path_qualifier = "";
	public $pathway_name="";
	public $path_session=null;
	public function __construct($level, $name,$pathway,$path_qualifier,$session)
	{
		$this->path_session=$session;
		$this->level_order = $level;
		$this->level_name = $name;
		$this->pathway_name = $pathway;
		$this->path_qualifier = $path_qualifier;
	
	}
	
	/*Error Reporting standard
	{
		"err": true|false,
		"errorlist":[{
			if is a db query {
			"query": "$query",
			"errortext": "$db->get_error_text()",
			}
			"function":"__METHOD__",
			"line":"__LINE__",
			"error": "function and situation specific error description"
		}]
	} */
	protected function errorReport($err, $query, $errortext, $functionname,$linenum ,$error)
	{
		$report = array('err'=>$err,'errorlist'=>array(array('query'=>$query,'errortext'=>$errortext,'function'=>$functionname, 'line'=>$linenum,'error'=>$error)));
		return json_encode($report);
		//return '{"err":"'.$err.'","errorlist":[{"query":"'.$query.'","errortext":"'.$errortext.'","error":"'.$error.'"}]}';
	}
	
	public function setLevelName($name)
	{
		$this->level_name = $name;	
	}
	
	public function getLevelName()
	{
		return $this->level_name;	
	}
	
	/**
		There was a big revision in the framework which changed page ordering
		from a static ordering to being based on link lists. However,
		the other team members still wanted to retain a monotonically 
		increasing page number so periodically reordering page numbers is
		necesary as pages are inserted and deleted. It is a huge function 
		which does way too much and needs to be broken down. 
		
		It would really benefit from a transactional database. Alas, we don't use one... 
	**/
	public function reorderLevel()
	{
		$db = $this->path_session->get_db();
		$query = "SELECT * FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `level_num`='$this->level_order'";
		$db->execute($query);
		$pages = array();
		$reo_pages= array();
		if($db->success())
		{
			//Put all the pages in an array based on page number
			while($row = $db->fetch_assoc2())
			{
				$pages[$row['page_num']] = $row;
			}
			$head = new DoublyLinkedList();
			$head->insertLast($pages[101]); //insert the first page
			$next_page = $pages[101]['page_next']; //look at the next page
			while($next_page != '' || $next_page != null)
			{
				$head->insertLast($pages[$next_page]); //insert the next page in the list
				$next_page = $pages[$next_page]['page_next']; //set the pointer to the page after the next page
			}
			//echo "Walk Forward: ";
			//$head->walkForward(); //Prints the level info from back to forward.
			//echo "List valid = ".$head->levelIntegrityCheck(); // checks if the list is valid
			$validlist = $head->levelNodeIntegrity();
			if(!$validlist['brokenlist'])
			{
				//echo '<br><br>';
				$pnum_count = 1;
				$pnum = 1;
				$head->prepareWalk(); //sets the internal point to Head;
				while(($var = $head->walk()) != null) //loops through each node in the list
				{
					//echo $var['page_num'];
					$ssection = $pnum_count; //sets the subsection number
					$sspn = ''; //the subsection page number
					if($pnum < 10) //if the pnum is less than 10 add a 0 infront
					{
						$sspn .='0'.$pnum;
					}else{
						$sspn .=$pnum;	//else just print the number
					}
					$reo_pages[$var['page_num']] = $ssection.$sspn; //set the new page number for a page by concatenating the subection number to the page number
					if($var['final_section_page'] != 1) //If the page is not the section end add 1 to page num
					{
						$pnum +=1;	
					}else{ //if it is the final page in a sub/section then set page num to 1 and increment subsection
						$pnum = 1;
						$pnum_count +=1;
					}
				}
				
				$orphanedpages = array_diff(array_keys($pages),array_keys($reo_pages));
				if(!empty($orphanedpages))
				{
					return $this->errorReport('true',$query,$db->get_error_text(),__METHOD__,__LINE__,"The level list is broken. The level must be functional before reordering.");	
				}
		
				
				//SELECT questions for the level and sort them by page num
				$questions=array();
				$query="SELECT * FROM {$this->pathway_name}_questions{$this->path_qualifier} WHERE `level_num`='$this->level_order'";
				$db->execute($query);
				if($db->success())
				{
					while($row = $db->fetch_assoc2())
					{
						$questions[$row['page_num']][] = $row['IDX'];	
					}
				}else{
					return $this->errorReport('true',$query,$db->get_error_text(),__METHOD__,__LINE__,"There was a problem setting setting up the questions array");	
				}
				
				foreach($pages as $key => $value)
				{//if vals are changed
					//echo "key = $key; value = ";
					if(isset($reo_pages[$key]))
					{
						$query = "UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET page_num={$reo_pages[$key]} WHERE idx={$value['IDX']};";
						$db->execute($query);
						if(!$db->success())
						{
							//echo "Failed ".$db->get_error_text().$query." <br><br>";
							//return '{"err":true,"query":"'.$query.'","error text":"'.$db->get_error_text().'"}';
							return $this->errorReport('true',$query,$db->get_error_text(),__METHOD__,__LINE__,"There was a problem renumbering a page.");
						}
						if($value['page_next'] != null && $value['page_next'] != '')
						{
							$query = "UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET page_next={$reo_pages[$value['page_next']]} WHERE idx={$value['IDX']}; ";
							$db->execute($query);
							if(!$db->success())
							{
								//return '{"err":true,"query":"'.$query.'","error text":"'.$db->get_error_text().'"}';
								return $this->errorReport('true',$query,$db->get_error_text(),__METHOD__,__LINE__,"There was a problem setting the 'next page' for a page.");
							}
						}
						if($value['page_from'] != null && $value['page_from'] != '')
						{
							$query = "UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET page_from={$reo_pages[$value['page_from']]} WHERE idx={$value['IDX']}; ";
							$db->execute($query);
							if(!$db->success())
							{
								//return '{"err":true,"query":"'.$query.'","error text":"'.$db->get_error_text().'"}';
								return $this->errorReport('true',$query,$db->get_error_text(),__METHOD__,__LINE__,"There was a problem renumbering setting the 'from page' for a page.");
							}
						}
						if($value['has_questions'] ==1)
						{
							//replace this query with one that use the pagenum as key with list of question ids
							//$query = "UPDATE {$this->pathway_name}_questions{$this->path_qualifier} SET page_num='{$reo_pages[$key]}' WHERE level_num='{$value['level_num']}' AND page_num='$key';";
							$query = "UPDATE {$this->pathway_name}_questions{$this->path_qualifier} SET page_num='{$reo_pages[$key]}' WHERE `IDX` IN (".implode(',',$questions[$key]).")";
							$db->execute($query);
							if(!$db->success())
							{
								return $this->errorReport('true',$query,$db->get_error_text(),__METHOD__,__LINE__,"There was a problem renumbering the questions associated with a page");
							}
						}
					}
					
				}
				return '{"err":false,"errorlist":[]}';
			}else{
				//return '{"err":true,"error":"Broken list at page '.$validlist['broken_page'].'"}';
				return $this->errorReport('true',"null","null",__METHOD__,__LINE__,"Broken list at page {$validlist['broken_page']}.");
			}
			
		}else{
			//return '{"err":true,"query":"'.$query.'","error text":"'.$db->get_error_text().'"}';
			return $this->errorReport('true',$query,$db->get_error_text(),__METHOD__,__LINE__,"There was a problem finding the level pages for reordering.");
		}
		
		
	}
	
	/**
		Provides the page list in a naive manner.
		@return json containing pages ordered by page number
	**/
	public function getPages()
	{
		$db = $this->path_session->get_db();
		$query = "SELECT `IDX`,`section_num`,`page_num`,`title`,`name`,`has_questions` FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `level_num`='$this->level_order' ORDER BY `section_num`, `page_num`";
		$db->execute($query);
		if($db->success())
		{
			$ret_string = '{"err":false,"pages":[';
			while($row = $db->fetch_assoc2())
			{
				$ret_string .="{";
				$ret_string .='"id":"'.$row['IDX'].'",';
				$ret_string .='"snum":"'.$row['section_num'].'",';
				$ret_string .='"pnum":"'.$row['page_num'].'",';
				$ret_string .='"title":"'.$this->jsonstringescape($row['title']).'",';
				$ret_string .='"name":"'.$row['name'].'",';
				$ret_string .='"questions":'.$row['has_questions'];
				$ret_string .="},";
				
			}
			$s_length = strlen($ret_string);
			if($ret_string[$s_length-1]==',')
			{
				$ret_string = substr($ret_string,0,$s_length-1);	
			}
			$ret_string .='],"errorlist":[]}';
			return $ret_string;
		}else{
			//return '{"err":true,"query":"'.$query.'"}';
			return $this->errorReport('true',$query,$db->get_error_text(),__METHOD__,__LINE__,"There was a problem getting the level page list.");
		}
	}
	
	/**
		Build the page list and display as the pages are entered.
		@return json pages listed as described by list structure
	*/
	public function getPages2()
	{
		$db = $this->path_session->get_db();
		//$query = "SELECT `IDX`,`section_num`,`page_num`,`title`,`name`,`has_questions` FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `level_num`='$this->level_order' ORDER BY `section_num`, `page_num`";
		$query = "SELECT * FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `level_num`=$this->level_order";
		$db->execute($query);
		$pages = array();
		$reo_pages= array();
		
		if($db->success())
		{
			//Put all the pages in an array based on page number
			while($row = $db->fetch_assoc2())
			{
				$pages[$row['page_num']] = $row;
			}
			$head = new DoublyLinkedList();
			$head->insertLast($pages[101]); //insert the first page
			$next_page = $pages[101]['page_next']; //look at the next page
			while($next_page != '' || $next_page != null)
			{
				$head->insertLast($pages[$next_page]); //insert the next page in the list
				$next_page = $pages[$next_page]['page_next']; //set the pointer to the page after the next page
			}
			
			$ret_string = '{"err":false,"pages":[';
			$head->prepareWalk();
			while(($row = $head->walk()) != null)
			{
				$ret_string .="{";
				$ret_string .='"id":"'.$row['IDX'].'",';
				$ret_string .='"snum":"'.$row['section_num'].'",';
				$ret_string .='"pnum":"'.$row['page_num'].'",';
				$ret_string .='"title":"'.$this->jsonstringescape($row['title']).'",';
				$ret_string .='"name":"'.$row['name'].'",';
				$ret_string .='"questions":'.$row['has_questions'];
				$ret_string .="},";
				
				
			}
			$s_length = strlen($ret_string);
			if($ret_string[$s_length-1]==',')
			{
				$ret_string = substr($ret_string,0,$s_length-1);	
			}
			$ret_string .='],"errorlist":[]}';
			return $ret_string;
		}else{
			//return '{"err":true,"query":"'.$query.'"}';
			return $this->errorReport('true',$query,$db->get_error_text(),__METHOD__,__LINE__,"There was a problem getting the level page list.");
		}
	}
	
	/**
		Helper function for various functions
		@return DoublyLinkedList or null Contains page definition and connections or null on failure 
	**/
	public function getLastPage()
	{
		$db = $this->path_session->get_db();
		//$query = "SELECT `IDX`,`section_num`,`page_num`,`title`,`name`,`has_questions` FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `level_num`='$this->level_order' ORDER BY `section_num`, `page_num`";
		$query = "SELECT * FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `level_num`=$this->level_order";
		$db->execute($query);
		$pages = array();
		$reo_pages= array();
		
		if($db->success())
		{
			//Put all the pages in an array based on page number
			while($row = $db->fetch_assoc2())
			{
				$pages[$row['page_num']] = $row;
			}
			$head = new DoublyLinkedList();
			$head->insertLast($pages[101]); //insert the first page
			$next_page = $pages[101]['page_next']; //look at the next page
			while($next_page != '' || $next_page != null)
			{
				$head->insertLast($pages[$next_page]); //insert the next page in the list
				$next_page = $pages[$next_page]['page_next']; //set the pointer to the page after the next page
			}
			
			//$ret_string = '{"err":false,"pages":[';
			$head->prepareWalk();
			$lastpage;
			while(($row = $head->walk()) != null)
			{
				$lastpage = $row;
				
			}
			return $lastpage;
		}else{
			return null;	
		}
	}
	
	/**
		Returns the level sections in a list sorted by section number
		@return json Sections or error report
	**/
	public function getLevelSections()
	{
		$db = $this->path_session->get_db();
		$query = "SELECT `section_num`,`section_name` FROM `{$this->pathway_name}_sections{$this->path_qualifier}` WHERE `level_num`='$this->level_order' ORDER BY `section_num`";
		$db->execute($query);
		if($db->success())
		{
			$ret_string = '{"sections":[';
			while($row = $db->fetch_assoc2())
			{
				$ret_string.= "{";
				$ret_string .='"snum":"'.$row['section_num'].'",';
				$ret_string .='"sname":"'.$row['section_name'].'"';
				$ret_string.= "},";
			}
			$s_length = strlen($ret_string);
			if($ret_string[$s_length-1]==',')
			{
				$ret_string = substr($ret_string,0,$s_length-1);	
			}
			$ret_string .='],"err":false}';
			return $ret_string;
		}else{
			//return '{"err":true,"query":"'.$query.'"}';
			return $this->errorReport('true',$query,$db->get_error_text(),__METHOD__,__LINE__,"There was a problem getting the level section list.");
		}		
		
	}
	
	/**
		Helper function which provides prebuilt level list.
		@return DoublyLinkedList The levels main list (orphaned pages are not included)
	**/
	private function getPageLL()
	{
		$db = $this->path_session->get_db();
		$query = "SELECT * FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `level_num`=$this->level_order";
		$db->execute($query);
		$pages = array();
		$reo_pages= array();
		
		if($db->success())
		{
			//Put all the pages in an array based on page number
			while($row = $db->fetch_assoc2())
			{
				$pages[$row['page_num']] = $row;
			}
			$head = new DoublyLinkedList();
			$head->insertLast($pages[101]); //insert the first page
			$next_page = $pages[101]['page_next']; //look at the next page
			while($next_page != '' || $next_page != null)
			{
				$head->insertLast($pages[$next_page]); //insert the next page in the list
				$next_page = $pages[$next_page]['page_next']; //set the pointer to the page after the next page
			}
			return $head;
		}else{
			return null;	
		}
				
	}
	
	/**
		Renames a section 
		@param section integer Section number
		@param name string The new name of the section
	**/
	
	public function renameSection($section,$name)
	{
		$db = $this->path_session->get_db();
		$query = "UPDATE {$this->pathway_name}_sections{$this->path_qualifier} SET `section_name`='".mysql_real_escape_string($name)."' WHERE `level_num`='{$this->level_order}' AND `section_num`='$section'";
		$db->execute($query);
		if($db->success())
		{
			//$level_sections[$section].setName($name);
			return '{"err":false}';	
		}else{
			//return '{"edit":"fail","query":"'.$query.'"}';
			return $this->errorReport('true',$query,$db->get_error_text(),__METHOD__,__LINE__,"There was a problem renaming the section.");
		}
	}

	/**
		Two cases one where a section is moved up and the other down
		Assume perfectly ordered levels
		Move up +to section number
		Select the lowest page and highest page from the moving section (possibly the highest page with a final_section_page toggle)
		Find the to and from destinations from the highest and lowest respectively.
		
		Select the lowest and highest pages from the moved section
		
		check for section first or lasts
		Below Tail <-> Moved Head
		Moving Head <-> Moved Tail
		Moving Tail <-> Above Head
		Update Section numbering and question numbering
		update the pals_section table
		
	**/
	/*
	*Needs error output standardizing
	*/
	private function moveSection($section,$direction) //this function is not yet added to the page editor gui
	{
		/*THIS NEEDS LOTS OF TESTING*/
		$moving = 0;
		$moved = 0;
		$db = $this->path_session->get_db();
		$query = "SELECT MIN(section_num),MAX(section_num) FROM {$this->pathway_name}_sections{$this->path_qualifier} WHERE `level_num`={$this->level_order}";
		$db->execute($query);
		$minmaxsection = $db->fetch_assoc2();
		
		if($direction == 1) //direction moving a section up 
		{
			$moving = $section;
			$moved = $section+1;
			if($minmaxsection['MAX(section_num)'] == $section)
			{
				return '{"move":"fail","res":"Cannot move up the highest section"}';	
			}
		}else{ //moving a section down
			$moving = $section-1;
			$moved = $section;
			if($minmaxsection['MIN(section_num)'] == $section)
			{
				return '{"move":"fail","res":"Cannot move down the lowest section"}';	
			}
		}
			
		
		
		$query = "SELECT * FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE page_num=(SELECT MAX(page_num) FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `level_num`='{$this->level_order}' AND `section_num`='$moving') AND `level_num`='{$this->level_order}'";
		$db->execute($query);
		if(!$db->success())
		{
			return '{"move":"fail","query":"'.$query.'"}';
		}
		$movingtail = $db->fetch_assoc2();
		$query = "SELECT * FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE page_num=(SELECT MAX(page_num) FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `level_num`='{$this->level_order}' AND `section_num`='$moved') AND `level_num`='{$this->level_order}'";
		$db->execute($query);
		if(!$db->success())
		{
			return '{"move":"fail","query":"'.$query.'"}';
		}
		$movedtail = $db->fetch_assoc2();
		$query = "SELECT * FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE page_num=(SELECT MIN(page_num) FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `level_num`='{$this->level_order}' AND `section_num`='$moving') AND `level_num`='{$this->level_order}'";
		$db->execute($query);
		if(!$db->success())
		{
			return '{"move":"fail","query":"'.$query.'"}';
		}
		$movinghead = $db->fetch_assoc2();
		$query = "SELECT * FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE page_num=(SELECT MIN(page_num) FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `level_num`='{$this->level_order}' AND `section_num`='$moved') AND `level_num`='{$this->level_order}'";
		$db->execute($query);
		if(!$db->success())
		{
			return '{"move":"fail","query":"'.$query.'"}';
		}
		$movedhead = $db->fetch_assoc2();
		
		$belowtail =array();
		if($movinghead['page_from'] != null && $movinghead['page_from'] != "")
		{
			$query = "SELECT * FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE page_num={$movinghead['page_from']} AND `level_num`='{$this->level_order}'";
			$db->execute($query);
			$belowtail = $db->fetch_assoc2();
			if(!$db->success())
			{
				return '{"move":"fail","query":"'.$query.'"}';
			}
		}
		
		$abovehead =array();
		if($movedtail['page_next'] != null && $movedtail['page_next'] != "")
		{
			$query = "SELECT * FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE page_num={$movedtail['page_next']} AND `level_num`='{$this->level_order}'";
			$db->execute($query);
			$abovehead = $db->fetch_assoc2();
			if(!$db->success())
			{
				return '{"move":"fail","query":"'.$query.'"}';
			}
		}
		
		//Below Tail <-> Moved Head
		if(!empty($belowtail))
		{
			$query = "UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET page_next={$movedhead['page_num']} WHERE `IDX`={$belowtail['IDX']}";
			$db->execute($query);
			$query = "UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET page_from={$belowtail['page_num']} WHERE `IDX`={$movedhead['IDX']}";
			$db->execute($query);
		}else{
			$query = "UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET page_from=null WHERE `IDX`={$movedhead['IDX']}";
			$db->execute($query);
		}
		if(!$db->success())
		{
			return '{"move":"fail","query":"'.$query.'"}';
		}
		
		//Moving Head <-> Moved Tail
		$query = "UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET page_next={$movinghead['page_num']} WHERE `IDX`={$movedtail['IDX']}";
		$db->execute($query);
		if(!$db->success())
		{
			return '{"move":"fail","query":"'.$query.'"}';
		}
		$query = "UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET page_from={$movedtail['page_num']} WHERE `IDX`={$movinghead['IDX']}";
		$db->execute($query);
		if(!$db->success())
		{
			return '{"move":"fail","query":"'.$query.'"}';
		}
		//Moving Tail <-> Above Head
		if(!empty($abovehead))
		{
			$query = "UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET page_next={$abovehead['page_num']} WHERE `IDX`={$movingtail['IDX']}";
			$db->execute($query);
			$query = "UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET page_from={$movingtail['page_num']} WHERE `IDX`={$abovehead['IDX']}";
			$db->execute($query);
		}else{
			$query = "UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET page_next=null WHERE `IDX`={$movingtail['IDX']}";
			$db->execute($query);
		}
		if(!$db->success())
		{
			return '{"move":"fail","query":"'.$query.'"}';
		}
		
		$query = "UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET section_num=127 WHERE `level_num`='{$this->level_order}' AND `section_num`=$moved";
		$db->execute($query);
		if(!$db->success())
		{
			return '{"move":"fail","query":"'.$query.'"}';
		}
		$query = "UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET section_num=$moved WHERE `level_num`='{$this->level_order}' AND `section_num`=$moving";
		$db->execute($query);
		if(!$db->success())
		{
			return '{"move":"fail","query":"'.$query.'"}';
		}
		$query = "UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET section_num=$moving WHERE `level_num`='{$this->level_order}' AND `section_num`=127";
		$db->execute($query);
		if(!$db->success())
		{
			return '{"move":"fail","query":"'.$query.'"}';
		}
		//Update question numbering
		$query = "UPDATE {$this->pathway_name}_questions{$this->path_qualifier} SET section_num=127 WHERE `level_num`='{$this->level_order}' AND `section_num`=$moved";
		$db->execute($query);
		if(!$db->success())
		{
			return '{"move":"fail","query":"'.$query.'"}';
		}
		$query = "UPDATE {$this->pathway_name}_questions{$this->path_qualifier} SET section_num=$moved WHERE `level_num`='{$this->level_order}' AND `section_num`=$moving";
		$db->execute($query);
		if(!$db->success())
		{
			return '{"move":"fail","query":"'.$query.'"}';
		}
		$query = "UPDATE {$this->pathway_name}_questions{$this->path_qualifier} SET section_num=$moving WHERE `level_num`='{$this->level_order}' AND `section_num`=127";
		$db->execute($query);
		if(!$db->success())
		{
			return '{"move":"fail","query":"'.$query.'"}';
		}
		//update the pals_section table
		$query ="SELECT * FROM {$this->pathway_name}_sections{$this->path_qualifier} WHERE `level_num`='{$this->level_order}' AND `section_num`=$moving";
		$db->execute($query);
		if(!$db->success())
		{
			return '{"move":"fail","query":"'.$query.'"}';
		}
		$movingsection =  $db->fetch_assoc2();
		
		$query ="SELECT * FROM {$this->pathway_name}_sections{$this->path_qualifier} WHERE `level_num`='{$this->level_order}' AND `section_num`=$moved";
		$db->execute($query);
		if(!$db->success())
		{
			return '{"move":"fail","query":"'.$query.'"}';
		}
		$movedsection =  $db->fetch_assoc2();
		
		$query = "UPDATE {$this->pathway_name}_sections{$this->path_qualifier} SET section_name='{$movingsection['section_name']}' WHERE `level_num`='{$this->level_order}' AND `section_num`=$moved";
		$db->execute($query);
		if(!$db->success())
		{
			return '{"move":"fail","query":"'.$query.'"}';
		}
		$query = "UPDATE {$this->pathway_name}_sections{$this->path_qualifier} SET section_name='{$movedsection['section_name']}' WHERE `level_num`='{$this->level_order}' AND `section_num`=$moving";
		$db->execute($query);
		if($db->success())
		{
			return '{"move":"success"}';	
		}else{
			return '{"move":"fail","query":"'.$query.'"}';
		}
		
	}
	
	/**
		Delete a section, 
		Pick the level and section
		*Then orphan or delete all of the pages and questions for that section
		*Then move all sections head of that section down 1
		Finally move all the pages and questions associated with those sections.
		Tie up the pages between the prev and next section
		
		beginning/middle/end? This pretty much only handles a middle section...
	*/
	/*
	*Needs error output standardizing
	*/
	private function deleteSection($section) /*Not yet implemented in GUI*/
	{
		$db = $this->path_session->get_db();
		$query = "DELETE FROM {$this->pathway_name}_sections{$this->path_qualifier} WHERE `level_num`='$this->level_order' AND `section_num`='$section';";
		$db->execute($query);
		if($db->success())
		{
			$query = "DELETE FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `level_num`='$this->level_order' AND `section_num`='$section';";
			$db->execute($query);
			if($db->success())
			{
				$query = "SELECT * FROM {$this->pathway_name}_sections{$this->path_qualifier} WHERE `level_num`='$this->level_order' AND `section_num`>'$section' ORDER BY `section_num` ASC;";
				$db->execute($query);
				if($db->success())
				{
					$highersections = array();
					while($row = $db->fetch_assoc2()) //This will break if we remove the first section
					{
						$highersections[] = $row;
					}
					//print_r($highersections);
					$query = "";
					foreach($highersections as $row) //This will break if we remove the first section
					{
						$query="UPDATE {$this->pathway_name}_sections{$this->path_qualifier} SET `section_num`=".($row['section_num']-1)." WHERE `level_num`={$this->level_order} AND `section_num`={$row['section_num']};";
						$db->execute($query);
						$query="UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET `section_num`=".($row['section_num']-1)." WHERE `level_num`={$this->level_order} AND `section_num`={$row['section_num']};";
						$db->execute($query);
						$query="UPDATE {$this->pathway_name}_questions{$this->path_qualifier} SET `section_num`=".($row['section_num']-1)." WHERE `level_num`={$this->level_order} AND `section_num`={$row['section_num']};";
						$db->execute($query);
					}
					
					if($db->success())
					{
						$query="SET @pmax=(SELECT MAX(`page_num`) FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `level_num`=$this->level_order AND `section_num`=".($section-1)."); ";
						$db->execute($query);
						$query="SET @pmin =(SELECT MIN(`page_num`) FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `level_num`='$this->level_order' AND `section_num`=".$section.");";
						$db->execute($query);
						echo $query."<br><br>";
						$query="UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET `page_from`=@pmax WHERE `level_num`='$this->level_order' AND `section_num`={$section} AND `page_num`=@pmin;";
						echo $query."<br><br>";
						$db->execute($query);
						if($db->success())
						{
							echo $query."<br><br>";
							echo "Rows affected: ".$db->affected_rows();
						}else{
							echo "<br>"." Error 5a ".$db->get_error_text();
							echo "<br><br>".$query."<br>";
						}
						/*
						$query="SET @pmin=(SELECT MIN(`page_num`) FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `level_num`=$this->level_order AND `section_num`=".$section.");";
						$db->execute($query);
						$query="SET @pmax =(SELECT MAX(`page_num`) FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `level_num`='$this->level_order' AND `section_num`=".($section-1)." ); ";
						$db->execute($query);*/
						$query="UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET `page_next`=@pmin WHERE `level_num`='$this->level_order' AND `section_num`=".($section-1)." AND `page_num`=@pmax;";
						$db->execute($query);
						if($db->success())
						{
							echo $query."<br><br>";
							echo "Rows affected: ".$db->affected_rows();
							return true;
						}else{
							echo "<br>"." Error 5b ".$db->get_error_text();
							echo "<br><br>".$query."<br>";
						}
					}else{
						
						echo $query."<br>";
						echo " Error 4 ".$db->get_error_text();
							
					}
				}else{
					echo " Error 3".$db->get_error_text();
						
				}
			}else{
				echo " Error 2".$db->get_error_text();
					
			}
		}else{
			echo " Error 1".$db->get_error_text();	
		}
		$db->get_error_text();
		return false;
	}
	
	/**
		@param id integer The page row id [IDX]
		@return json Contains the page row 
	**/
	
	public function getPage($id) {
		$db = $this->path_session->get_db();
		$query = "SELECT * FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `IDX`='$id'";
		$db->execute($query);
		if($db->success())
		{
			$ret_string = '{"err":false,"page":';
			$row = $db->fetch_assoc2();
			
			$ret_string .="{";
			$ret_string .='"id":"'.$row['IDX'].'",';
			$ret_string .='"lnum":"'.$row['level_num'].'",';
			$ret_string .='"snum":"'.$row['section_num'].'",';
			$ret_string .='"pnum":"'.$row['page_num'].'",';
			$ret_string .='"title":"'.$this->jsonstringescape($row['title']).'",';
			$ret_string .='"name":"'.$row['name'].'",';
			$ret_string .='"leftnav":"'.$row['is_left_nav'].'",';
			$ret_string .='"pagefrom":"'.$row['page_from'].'",';
			$ret_string .='"pagenext":"'.$row['page_next'].'",';
			$ret_string .='"fsp":"'.$row['final_section_page'].'",';
			$ret_string .='"type":"'.$row['type'].'",';
			$ret_string .='"snum":"'.$row['section_num'].'",';
			$ret_string .='"content_participant":"'.$this->jsonstringescape($row['content_participant']).'",';
			$ret_string .='"picture":"'.$row['photo_file'].'",';
			$ret_string .='"video":"'.$row['media_file'].'",';
			$ret_string .='"review_video_file":"'.$row['review_video_file'].'",';
			$ret_string .='"has_questions":"'.$row['has_questions'].'",';
			$ret_string .='"query_options":"'.$this->jsonstringescape($row['query_options']).'",';
			$ret_string .='"report_form_in":"'.$row['report_form_in'].'",';
			$ret_string .='"level_end":"'.$row['level_end'].'",';
			$ret_string .='"content_staff":"'.$this->jsonstringescape($row['content_staff']).'",';
			$ret_string .='"notes":"'.$this->jsonstringescape($row['notes']).'"';
			$ret_string .="},";
			
			$query = "SELECT `IDX`,`question_number`,`question_label`,`question_text` FROM {$this->pathway_name}_questions{$this->path_qualifier} WHERE `level_num`=$this->level_order AND `page_num`={$row['page_num']} ORDER BY `question_number`";
			$db->execute($query);
			$ret_string .= '"questions":[';
			$qs = 0;
			while($row = $db->fetch_assoc2())
			{
				$qs++;
				$ret_string .='{';
				$ret_string .='"id":"'.$row['IDX'].'",';
				$ret_string .='"qnum":"'.$row['question_number'].'",';
				$ret_string .='"qlabel":"'.$row['question_label'].'",';
				$ret_string .='"qtext":"'.$this->jsonstringescape($row['question_text']).'"';
				$ret_string .='},';
			}
			if($qs > 0)
			{
				$s_length = strlen($ret_string);
				if($ret_string[$s_length-1]==',')
				{
					$ret_string = substr($ret_string,0,$s_length-1);
				}
			
			}
			$ret_string .= ']';
			$ret_string .=',"errorlist":[]}';
			return $ret_string;
		}else{
			//return '{"err":true,"query":"'.$query.'"}';
			return $this->errorReport('true',$query,$db->get_error_text(),__METHOD__,__LINE__,"There was a problem getting the page details.");
		}
	}

	/*UNIT TESTS REQUIRED FOR addPage()
	*	1.add page to empty level
	*	2.add page to level with content
	*	-2a. add page after page without sgp
	*	-2b. add page after page with sfp
	*	-2b. add page after page with new section_num
	*	
	*	further error handling required:
	*	1.handle situation for 99+1 pages within subsection
	*/
	/**
		Adds a page to the end of the linked list.
		@return json Page details or error report
	**/
	public function addPage() {
		$db = $this->path_session->get_db();
		$maxpage = $this->getLastPage();
		
		if($maxpage !=null)
		{
			$lastpage= $maxpage['page_num'];

			$insert_id = null;
			if($lastpage > 0)
			{
				
					$endpage =$maxpage;
					
					$section = $endpage['section_num'];
					$subsection_num = intval($lastpage/100);
					$page_num = $lastpage%100;
					$new_pnum = $page_num+1;
					//Bug here: If pages become dislocated this can create double records for a pagenum which is very bad 
					$query ="SELECT `level_num`,`section_num`,MAX(`page_num`) FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `level_num`={$this->level_order} AND `page_num` < ".(($subsection_num+1)*100);
					$db->execute($query);
					if($db->success())
					{
						$row = $db->fetch_assoc2();
						if($lastpage != $row['MAX(page_num)'])
						{
							$new_pnum = ($row['MAX(`page_num`)']%100) +1;
						}
					}else{
						return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"The highest subsection page could not be found.");	
					}
					
					$new_ssnum = $subsection_num;
					if($endpage['final_section_page']==1)
					{
						$new_ssnum +=1;
						$new_pnum = 1;
					}
					if($new_pnum < 10)
					{
						$new_pnum = '0'.$new_pnum;	
					}
					$query = "INSERT INTO {$this->pathway_name}_pages{$this->path_qualifier} (`level_num`,`section_num`,`page_num`,`page_from`) VALUES ('$this->level_order','$section','".$new_ssnum.$new_pnum."','$lastpage')";
					$db->execute($query);
					if($db->success())
					{
						$insert_id = $db->insert_id();
						$query = "UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET `page_next`=".$new_ssnum.$new_pnum." WHERE `level_num`={$this->level_order} and `page_num`=$lastpage";
						$db->execute($query);
						if(!$db->success())
						{	
							return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"There was a problem updating the previous final page.");
						}
					}else{
						return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"There was a problem inserting a new page.");	
					}
				
			}else{
				$query = "INSERT INTO {$this->pathway_name}_pages{$this->path_qualifier} (`level_num`,`section_num`,`page_num`) VALUES ('$this->level_order','1','101')";
				$db->execute($query);
				$insert_id = $db->insert_id();
				
			}
			
			$query = "SELECT * FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `IDX`=$insert_id";
			$db->execute($query);
			$ret_string = '{"err":false,"pages":';
			while($row = $db->fetch_assoc2())
			{
				$ret_string .="{";
				$ret_string .='"id":'.$row['IDX'].',';
				$ret_string .='"snum":"'.$row['section_num'].'",';
				$ret_string .='"pnum":"'.$row['page_num'].'",';
				$ret_string .='"title":"'.$this->jsonstringescape($row['title']).'",';
				$ret_string .='"name":"'.$row['name'].'"';
				$ret_string .="}";
				
			}
			$ret_string .='}';
			return $ret_string;
		}else{
			$query = "INSERT INTO {$this->pathway_name}_pages{$this->path_qualifier} (`level_num`,`section_num`,`page_num`) VALUES ('$this->level_order','1','101')";
			$db->execute($query);
			if($db->success())
			{
				$insert_id = $db->insert_id();
				
				$query = "SELECT * FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `IDX`=$insert_id";
				$db->execute($query);
				$ret_string = '{"err":false,"pages":';
				while($row = $db->fetch_assoc2())
				{
					$ret_string .="{";
					$ret_string .='"id":'.$row['IDX'].',';
					$ret_string .='"snum":"'.$row['section_num'].'",';
					$ret_string .='"pnum":"'.$row['page_num'].'",';
					$ret_string .='"title":"'.$this->jsonstringescape($row['title']).'",';
					$ret_string .='"name":"'.$row['name'].'"';
					$ret_string .="}";
					
				}
				$ret_string .='}';
				return $ret_string;
			}else{
				return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"There was a problem inserting a new page.");
			}
		}
		
		
	}
	
	/**
		Inserts a page into the list after the selected page
		@param id integer The page row id [IDX]
		@return json Page details or error report
	**/
	
	public function addPageAfter($id)
	{
		$db = $this->path_session->get_db();
		$query = "SELECT `page_from`,`page_next`,`final_section_page`,`page_num`,`level_num`,`section_num` FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `IDX`='$id'";
		$db->execute($query);
		
		if($db->success())
		{
			if($db->num_rows() ==1)
			{
				$page = $db->fetch_assoc2();
				
				if(is_numeric($page['page_next'])) //essentially asking if page_next == null, the condition associated with the end of the level
				{	
					$snum = $page['section_num'];
					$sectionmaxpage = (floor($page['page_num']/100)+1)*100;
					if($page['final_section_page'] ==1)
					{
						$query = "SELECT `section_num` FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `level_num`='{$this->level_order}' AND `page_num`='{$page['page_next']}' ";
						$db->execute($query);
						if($db->num_rows() == 1)
						{
							$snum = $db->result(0);
						}else{
							//return '{"err":"Next page not acessible."}';
							return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"There was a problem find the page's next page details.");
						}
						$sectionmaxpage = (floor($page['page_num']/100)+2)*100;
					}
					
					
					$query = "SELECT MAX(`page_num`) FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `level_num`='{$this->level_order}' AND `page_num` < $sectionmaxpage";
					$db->execute($query);
					$maxpage = 0;
					if($db->num_rows() ==1)
					{
						$maxpage = $db->result(0) +1;
					}else{
						//return '{"err":"Last page could not be found."}';
						return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"There was a problem finding the subsections highest page number.");
					}
					$query = "INSERT INTO {$this->pathway_name}_pages{$this->path_qualifier} (`page_from`,`page_next`,`page_num`,`level_num`,`section_num`) VALUES ('{$page['page_num']}','{$page['page_next']}','$maxpage','{$this->level_order}','$snum');";
					$db->execute($query);
					$insertid = 0;
					if($db->success())
					{
						$insertid = $db->insert_id();
					}else{
						return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"New page could not be created.");
						//return '{"err":"New page could not be created."}';	
					}
					$warnings = "";
					$err = false;
					$errorlist=array();
					$query = "UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET `page_next`='$maxpage' WHERE `IDX`='$id'";
					$db->execute($query);
					if(!$db->success())
					{
						//$err = 'true';
						$errorlist[] = array("error"=>"Page next could not be updated on selected page");
					}
					$query = "UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET `page_from`='$maxpage' WHERE `level_num`='{$this->level_order}' AND `page_num`='{$page['page_next']}';";
					$db->execute($query);
					if(!$db->success())
					{
						//$err = 'true';
						$errorlist[] = array("error"=>"Page from could not be updated on the next page");
					}
					
					$ret_string = '{"pages":';
					
						$ret_string .="{";
						$ret_string .='"id":'.$insertid.',';
						$ret_string .='"snum":"'.$snum.'",';
						$ret_string .='"pnum":"'.$maxpage.'",';
						$ret_string .='"title":"",';
						$ret_string .='"name":""}';
						
					
						$ret_string .=',"err":false,"errorlist":'.json_encode($errorlist).'}';
					return $ret_string;
				}else{
					return $this->addPage();
					
				}
			}else{
				//return '{"err":"Selected page does not exist."}';
				return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"Selected page does not exist.");
			}
			
		}else{
			//return '{"err":"Page look up failed."}';
			return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"Page look up failed.");
		}
	}
	
	/**
		Edits a page
		@param id integer The page row id [IDX]
		@param pagedata array $_POST data containing page form fields
		@param superhipo int Allow or disallow editing page from and next fields
		@return json Success or error report
	**/
	public function editPage($id,$pagedata,$superhipo)
	{
		$db = $this->path_session->get_db();
		$p_data = array();
		foreach($pagedata as $datum)
		{
			$p_data[$datum['name']] = $datum['value'];	
		}
		$pln=0;
		if($p_data['p_lnav'] == 'on')
		{
			$pln = 1;
		}
		$sfp=0;
		if($p_data['p_sfp'] == 'on')
		{
			$sfp = 1;
		}
		//&#39;
		$pidx = mysql_real_escape_string($p_data['p_idx']);
		$psection = mysql_real_escape_string($p_data['p_section']);
		$pcontent = mysql_real_escape_string(stripslashes($p_data['p_content']));
		$ptitle = mysql_real_escape_string(stripslashes($p_data['p_title']));
		$ptype = mysql_real_escape_string($p_data['p_type']);
		$pvideo = mysql_real_escape_string($p_data['p_video']);
		$ppic = mysql_real_escape_string($p_data['p_picture']);
		
		$pnext =mysql_real_escape_string($p_data['p_next']);
		if($pnext == "")
			$pnext ='null';
		$pfrom =mysql_real_escape_string($p_data['p_from']);
		if($pfrom == "")
			$pfrom ='null';
		$query= "SELECT `page_num`,`section_num` FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `IDX`=$pidx";
		$db->execute($query);
		if($db->success())
		{
			$row = $db->fetch_assoc2();
			if($row['section_num'] != $psection)
			{
				$query = "UPDATE {$this->pathway_name}_questions{$this->path_qualifier} SET `section_num`='$psection' WHERE `level_num`='{$this->level_order}' AND `section_num`='{$row['section_num']}' AND `page_num`='{$row['page_num']}'";
				$db->execute($query);
				if(!$db->success())
				{
					return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"Section update failed.");	
				}
				
			}
				
			
		}else{
			return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"Page look up failed.");
		}
		
		$query= "";
		if($superhipo == 1)
		{
			$query = "UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET `section_num`='$psection',`page_next`=$pnext,`page_from`=$pfrom,`is_left_nav`='$pln',`final_section_page`='$sfp',`type`='$ptype',`title`='$ptitle',`content_participant`='$pcontent',`media_file`='$pvideo',`photo_file`='$ppic' WHERE `IDX`=$pidx";
		}else{
		
			$query = "UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET `section_num`='$psection',`is_left_nav`='$pln',`final_section_page`='$sfp',`type`='$ptype',`title`='$ptitle',`content_participant`='$pcontent',`media_file`='$pvideo',`photo_file`='$ppic' WHERE `IDX`=$pidx";
		}
		$db->execute($query);
		if($db->success())
		{
			return '{"edit":"Sucessful","err":false}';	
		}else{
			//return '{"edit":"failure","query":"'.$this->jsonstringescape($query).'"}';
			return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"The page edit failed.");
		}
		
		
	}
	
	/**
		Look up page
		Find position
		if(disconnected node)
		{
			delete
		}else if(internal node) {
			move pointer of page_next and page_prev to each other
			check for final_section_page
			delete the page
		}else if(tail node) {
			set prev_node next to null
			check for final_section_page
			delete node
		}if(head node) {
			do nothing DO NOT DELETE PAGE 101
		}
		Delete any associated questions for this page
		
		@param id integer The page row id [IDX]
		@return json Sucess or error report
	**/	
	public function deletePage($id)
	{
		$db = $this->path_session->get_db();
		$query = "SELECT `IDX`,`level_num`,`section_num`,`page_num`,`page_from`,`page_next`,`final_section_page` FROM `{$this->pathway_name}_pages{$this->path_qualifier}` WHERE `IDX`='$id'";
		$db->execute($query);
		if($db->success())
		{
			$page = $db->fetch_assoc2();
			if($page['page_num'] == 101) { //This should also cover the case where page_from is null. However some situation may create a rogue head node
				return '{"err":true,"errorlist":[{"error":"Page 101 cannot be deleted."}]}';
			}else if ($page['page_from'] == "" && $page['page_next'] == ""){
				$query = "DELETE FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `IDX`='$id'";
				$db->execute($query);
				if($db->success())
				{
					$query = "DELETE FROM {$this->pathway_name}_questions{$this->path_qualifier} WHERE `level_num`='{$this->level_order}' AND `section_num`='{$page['section_num']}' AND `page_num`='{$page['page_num']}'";
					$db->execute($query);
					if($db->success())
					{
						return '{"deleted":"'.$id.'","path":"disconnected node","err":false}'; 
					}else{
						return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"Page questions could not be deleted, path: Disconnected node");
						//return '{"err":"Page questions could not be deleted","path":"Disconnected node"}';	
					}
				}else{
					return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"Page could not be deleted, path: Disconnected node");
					//return '{"err":"Page could not be deleted","path":"Disconnected node"}';
				}
			}else if ($page['page_next'] == ""){ //There could be a bug here for pages which are right after final_section_pages
				$query = "UPDATE `{$this->pathway_name}_pages{$this->path_qualifier}` SET `page_next`=null WHERE `level_num`='{$this->level_order}' AND `page_num`='{$page['page_from']}'";
				$db->execute($query);
				if($db->success())
				{
					$query = "DELETE FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `IDX`='$id'";
					$db->execute($query);
					if($db->success())
					{
						$query = "DELETE FROM {$this->pathway_name}_questions{$this->path_qualifier} WHERE `level_num`='{$this->level_order}' AND `section_num`='{$page['section_num']}' AND `page_num`='{$page['page_num']}'";
						$db->execute($query);
						if($db->success())
						{
							return '{"deleted":"'.$id.'","path":"Tail node","err":false}'; 
						}else{
							//return '{"err":"Page questions could not be deleted","path":"Tail node"}';
							return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"Page questions could not be deleted, path: Tail node");
						}
					}else{
						//return '{"err":"Page could not be deleted","path":"Tail node"}';
						return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"Page could not be deleted, path: Tail node");
					}

				}else{
					//return '{"err":"Previous page could not be updated","path":"Tail node","query":"'.$query.'"}';
					return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"Previous page could not be updated, path: Tail node");
				}
			}else if ($page['page_from'] == ""){
				//return '{"err":"Broken node indicating a broken list contact site admins"}';
				return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"Broken node indicating a broken list contact site admins");
			}else{
				if($page['final_section_page'] == 1){
					$query = "UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET `page_next`='{$page['page_next']}',`final_section_page`=1 WHERE `level_num`='{$this->level_order}' AND `page_num`='{$page['page_from']}'";
				}else{
					$query = "UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET `page_next`='{$page['page_next']}' WHERE `level_num`='{$this->level_order}' AND `page_num`='{$page['page_from']}'";
				}
				$db->execute($query);
				if($db->success())
				{
					$query = "UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET `page_from`='{$page['page_from']}' WHERE `level_num`='{$this->level_order}' AND `page_num`='{$page['page_next']}'";
					$db->execute($query);
					if($db->success())
					{
						$query = "DELETE FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `IDX`='$id'";
						$db->execute($query);
						if($db->success())
						{
							$query = "DELETE FROM {$this->pathway_name}_questions{$this->path_qualifier} WHERE `level_num`='{$this->level_order}' AND `section_num`='{$page['section_num']}' AND `page_num`='{$page['page_num']}'";
							$db->execute($query);
							if($db->success())
							{
								return '{"deleted":"'.$id.'","path":"Internal node","err":false}'; 
							}else{
								return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"Page questions could not be deleted, path: Internal node");
								//return '{"err":"Page questions could not be deleted","path":"Internal node"}';
							}
						}else{
							return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"Page could not be deleted, path: Internal node.");
							//return '{"err":"Page could not be deleted","path":"Internal node"}';
						}	
					}else{
						return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"Next page could not be updated.");
						//return '{"err":"Next page could not be updated"}';	
					}
				}else{
					return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"Previous page could not be updated.");
					//return '{"err":"Previous page could not be updated"}';	
				}
			}
			
		}else{
			return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"Could not find page.");
			//return '{"err":"Could not find page"}';	
		}
		
	}
	
	/**
		Move Page 
		
	*/
	public function movePage($id,$dir)
	{
		
	}
	
	/**
		@param id integer The question row id [IDX]
		@return json question details or error report
	**/
	public function getQuestion($id)
	{
		$db = $this->path_session->get_db();

		$query = "SELECT * FROM {$this->pathway_name}_questions{$this->path_qualifier} WHERE `IDX`=$id";
		$db->execute($query);
		if($db->success())
		{
			$row = $db->fetch_assoc2();
			$ret_string = '{"err":false,"question":{';
			$ret_string .='"id":"'.$row['IDX'].'",';
			$ret_string .='"qnum":"'.$row['question_number'].'",';
			$ret_string .='"qtype":"'.$row['type'].'",';
			$ret_string .='"qlabel":"'.$row['question_label'].'",';
			$ret_string .='"qtext":"'.$this->jsonstringescape($row['question_text']).'",';
			$ret_string .='"qresponse":"'.$this->jsonstringescape($row['response_options']).'",';
			$ret_string .='"qcorrect":"'.$this->jsonstringescape($row['response_correct']).'"';
			$ret_string .= '}}';
			return $ret_string;
			
		}else{
			//return '{"err":"Select question fail"}';
			return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"Question retrieval failed");
		}
	}
	
	/**
		If this is the first question on the page then turn on the has_questions field
		@param id integer The page row id [IDX]
		@return json question details or error report
	**/
	public function addQuestion($pageid)
	{
		
		$db = $this->path_session->get_db();
		
		
		
		$query = "SELECT `section_num`,`page_num` FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `IDX`=$pageid";
		$db->execute($query);
		if($db->success())
		{
			$row=$db->fetch_assoc2();
			$query = "SET @lastq=(SELECT IF((SELECT MAX(`question_number`) FROM {$this->pathway_name}_questions{$this->path_qualifier} WHERE `level_num`={$this->level_order} AND `section_num`={$row['section_num']} AND `page_num`={$row['page_num']}),(SELECT MAX(`question_number`) FROM {$this->pathway_name}_questions{$this->path_qualifier} WHERE `level_num`={$this->level_order} AND `section_num`={$row['section_num']} AND `page_num`={$row['page_num']})+1,1))";
			$snum = $row['section_num'];
			$pnum = $row['page_num'];
			/*
			$query = "SET @lastq=(
					SELECT IF(
						(SELECT MAX(`question_number`) FROM {$this->pathway_name}_questions{$this->path_qualifier} WHERE `level_num`={$this->level_order} AND `section_num`={$row['section_num']} AND `page_num`={$row['page_num']})<>NULL,
						(SELECT MAX(`question_number`)+1 FROM {$this->pathway_name}_questions{$this->path_qualifier} WHERE `level_num`={$this->level_order} AND `section_num`={$row['section_num']} AND `page_num`={$row['page_num']}),
						1)
						)";
			*/
			
			$db->execute($query);
			if(!$db->success())
			{
				//return '{"err":"Last question error '.$db->get_error_text().'","query":"'.$query.'"}';
				return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"There was a problem finding the highest numbered question for this page.");
			}
			$query="UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET `has_questions`=1 WHERE `IDX`=$pageid";
			$db->execute($query);
			if(!$db->success())
			{
				return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"There was a problem updating the page to reflect that it has questions.");
			}
			
			$query = "INSERT INTO {$this->pathway_name}_questions{$this->path_qualifier} (`level_num`,`section_num`,`page_num`,`question_number`) VALUES({$this->level_order},{$row['section_num']},{$row['page_num']},@lastq)";
			$db->execute($query);
			if($db->success())
			{
				$insert_id = $db->insert_id();
				$query = "SELECT * FROM {$this->pathway_name}_questions{$this->path_qualifier} WHERE `IDX`=$insert_id";
				$db->execute($query);
				if($db->success())
				{
					$row = $db->fetch_assoc2();
					$ret_string = '{"question":{';
					$ret_string .='"id":"'.$row['IDX'].'",';
					$ret_string .='"qnum":"'.$row['question_number'].'",';
					$ret_string .='"qtype":"'.$row['type'].'",';
					$ret_string .='"qlabel":"'.$row['question_label'].'",';
					$ret_string .='"qtext":"'.$this->jsonstringescape($row['question_text']).'",';
					$ret_string .='"qresponse":"'.$this->jsonstringescape($row['response_options']).'"';
					$ret_string .= '},"err":false}';
					
					
					
					return $ret_string;
				}
			}else{
				//return '{"err":"Inserting question error","query":"'.$query.'"}';
				return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"There was a problem inserting the new question.");
			}
		}else{
			return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"The questions associated page could not be found.");
		}
	}
	
	
	/**
		If the last question is deleted turn has_questions off
		
		@param qid integer Question id number [IDX]
		@return json Success or error report
	*/
	public function deleteQuestion($qid)
	{
		$db = $this->path_session->get_db();
		$pageinfo = array();
		$query  = "SELECT `level_num`,`section_num`,`page_num` FROM {$this->pathway_name}_questions{$this->path_qualifier} WHERE `IDX`=$qid";
		$db->execute($query);
		if($db->success())
		{
			$pageinfo = $db->fetch_assoc2();
		}else{
			return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"The questions deletion failed because the page could not be found.");
		}
		
		$query = "DELETE FROM {$this->pathway_name}_questions{$this->path_qualifier} WHERE `IDX`=$qid";
		$db->execute($query);
		if($db->success())
		{
			$query="SELECT COUNT(*) FROM {$this->pathway_name}_questions{$this->path_qualifier} WHERE `level_num`='{$pageinfo['level_num']}' AND `section_num`='{$pageinfo['section_num']}' AND `page_num`='{$pageinfo['page_num']}'";
			$db->execute($query);
			if($db->result(0) == 0) {
				
				$query = "UPDATE {$this->pathway_name}_pages{$this->path_qualifier} SET `has_questions`=0 WHERE `level_num`='{$pageinfo['level_num']}' AND `section_num`='{$pageinfo['section_num']}' AND `page_num`='{$pageinfo['page_num']}'";
				$db->execute($query);
			}
			
			return '{"err":false}';
		}else{
			//return '{"err":"Question deletion error."}';
			return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"The question deletion failed.");
		}
	}
	
	/**
		@param qid integer Question id number [IDX]
		@param pagedata array $_POST data from question form fields
		@return json Success or error report
	**/
	public function editQuestion($qid,$pagedata)
	{
		$db = $this->path_session->get_db();
		$p_data = array();
		foreach($pagedata as $datum)
		{
			$p_data[$datum['name']] = $datum['value'];	
		}
		$q_num = mysql_real_escape_string($p_data['q_num']);
		$q_type = mysql_real_escape_string($p_data['q_type']);
		$q_label = mysql_real_escape_string($p_data['q_label']);
		$q_text = mysql_real_escape_string(stripslashes($p_data['q_text']));
		$q_options = mysql_real_escape_string(stripslashes($p_data['q_options']));
		$q_correct = mysql_real_escape_string($p_data['q_correct']);
		$query = "UPDATE {$this->pathway_name}_questions{$this->path_qualifier} SET `question_number`=$q_num,`type`='$q_type',`question_label`='$q_label',`question_text`='$q_text', `response_options`='$q_options',`response_correct`='$q_correct' WHERE `IDX`=$qid";
		$db->execute($query);
		if($db->success())
		{
			return '{"err":false}';	
		}else{
			return $this->errorReport(true,$query,$db->get_error_text(),__METHOD__,__LINE__,"The questions edit failed.");
			//return '{"edit":"fail","query":"'.$this->jsonstringescape($query).'"}';
		}
	}
	
	/**
	*Needs error output standardizing
	*/
	public function addSection()
	{
		$newsection = '';
		$db = $this->path_session->get_db();
		$query1 = "SELECT MAX(`section_num`) FROM {$this->pathway_name}_sections{$this->path_qualifier} WHERE `level_num`='{$this->level_order}';";
		$db->execute($query1);
		if($db->success())
		{
			$res = $db->fetch_assoc2();
			if($db->affected_rows() > 0)
			{
				$newsection = $res['MAX(`section_num`)']+1;
			}else{
				$newsection = 1;	
			}
			$query2 = "INSERT INTO {$this->pathway_name}_sections{$this->path_qualifier} (`level_num`,`section_num`,`section_name`) VALUES ('{$this->level_order}','$newsection','new section $newsection');";
			$db->execute($query2);
			if($db->success())
			{
				return '{"section_num":'.$newsection.',"name":"new section '.$newsection.'"}';	
			}else{
				return '{"err":"true","queryies":"'.$query1.';'.$query2.'"}';	
			}
		}
		
	}
	
	/**
	*Needs error output standardizing
	*/
	public function deleteLastSection() {
		$db = $this->path_session->get_db();
		$query1 = "SELECT MAX(`section_num`) FROM {$this->pathway_name}_sections{$this->path_qualifier} WHERE `level_num`='{$this->level_order}';";
		$db->execute($query1);
		if($db->success())
		{
			$res = $db->fetch_assoc2();
			if($db->affected_rows() > 0)
			{
				$removesection = $res['MAX(`section_num`)'];
				$query = "DELETE FROM {$this->pathway_name}_sections{$this->path_qualifier} WHERE `level_num`='{$this->level_order}' AND `section_num`='$removesection'";
				$db->execute($query);
				if($db->success())
				{
					return '{"remove":"success","section":"'.$removesection.'"}';
				}else{
					return '{"remove":"fail","query":"'.$query.'"}';
				}
			}else{
					return '{"remove":"fail"}';
			}
		}
	}
	
	
	public function jsonstringescape($pitastring)
	{	
		$retval = htmlentities($pitastring,ENT_QUOTES,"UTF-8");
		$retval = preg_replace('/(\n|\t|\r)/','',$retval);

		return $retval;
	}
	
	/**
	* Provides indepth page data to list provide the user interface
	* @param detailed boolean whether to provide additional page information
	* @return json Contains jsonized linked list of the level for the visualizer component and now the page list
	*/
	public function getLevelLinkedLists($detailed=false) /*Needs error output standardizing*/
	{
		$db = $this->path_session->get_db();
		$query = "SELECT * FROM {$this->pathway_name}_pages{$this->path_qualifier} WHERE `level_num`=$this->level_order";
		$db->execute($query);
		$pages = array();
		$handled_pages = array();
		$reo_pages= array();
		
		if($db->success())
		{
			//Put all the pages in an array based on page number
			while($row = $db->fetch_assoc2())
			{
				$pages[$row['page_num']] = $row;
			}
			$head[] = new DoublyLinkedList();
			$head[0]->insertLast($pages[101]); //insert the first page
			$next_page = $pages[101]['page_next']; //look at the next page
			unset($pages[101]);
			while($next_page != '' || $next_page != null)
			{
				$head[0]->insertLast($pages[$next_page]); //insert the next page in the list
				$handled_pages[$next_page] = $pages[$next_page];
				$old_page=$next_page;
				if($next_page == $pages[$next_page]['page_next']) //If a page is pointing to itself
				{
					$next_page = null;
				}else{
					$next_page = $pages[$next_page]['page_next']; //set the pointer to the page after the next page
				}
				unset($pages[$old_page]); //remove linked pages from the list of pages to be evaluated 
				//echo "first loop";
			}
			while(!empty($pages) ) //While the list of unlinked pages is not empty
			{
				$head[] = new DoublyLinkedList();
				$keys = array_keys($pages); //get the page numbers
				$elemt = array_rand($keys); //select the index of 1 key at random
				$curhead = count($head)-1; //select the last linked list in the array
				$head[$curhead]->insertLast($pages[$keys[$elemt]]); //select a random page and add it to the current list?
				
				$next_page=$pages[$keys[$elemt]]['page_next']; //grab the page next 
				$prev_page=$pages[$keys[$elemt]]['page_from']; //grab the page from
				while(array_key_exists($next_page,$pages)) //insert all valid next pages.
				{
					$head[$curhead]->insertLast($pages[$next_page]); //insert the next page
					$old_page=$next_page;
					$next_page = $pages[$next_page]['page_next'];
					unset($pages[$old_page]);
				}
				while(array_key_exists($prev_page,$pages)) //insert all valid previous pages
				{
					
						
					$head[$curhead]->insertFirst($pages[$prev_page]); //insert the from page
					$old_page=$prev_page;
					$prev_page = $pages[$prev_page]['page_from'];
					if($pages[$prev_page]['page_next'] != $old_page)
					{
						$prev_page = null;
					}
					
					unset($pages[$old_page]);
				}
				unset($pages[$keys[$elemt]]);
				//echo print_r($pages,true);
				
				
			}
			/*$_SESSION['shot_in_foot']['pages']=$pages;
			$_SESSION['shot_in_foot']['lists']=array_keys($head);*/
			
			
			$retstring = '{"lists":[';
			$j=0;
			//echo print_r($head);
			foreach($head as $list)
			{
				if($detailed)
				{
					if($j==0)
					{
						$retstring .= '{"pages":[';
					}else{
						$retstring .= ',{"pages":[';
					}
					$j++;
					
				}
				$list->prepareWalk();
				$i=0;
				while(($row = $list->walk()) != null)
				{
					if($detailed)
					{
						if($i==0)
						{
							$retstring .='{"pagenum":'.$row['page_num'].',';
						}else{
							$retstring .=',{"pagenum":'.$row['page_num'].',';
						}
						$i++;
						$retstring .='"next":'.($row['page_next'] != null ? $row['page_next']:'null').',';
						$retstring .='"prev":'.($row['page_from'] != null ? $row['page_from']:'null').',';
						$retstring .='"fsp":'.($row['final_section_page'] != null ? $row['final_section_page']:0).',';
						$retstring .='"leftnav":'.$row['is_left_nav'].',';
						$retstring .='"id":"'.$row['IDX'].'",';
						$retstring .='"snum":'.$row['section_num'].',';
						$retstring .='"questions":'.$row['has_questions'].',';
						
						$retstring .='"title":"'.$row['title'].'"}';
					}else{
						
						if($i==0 &&$j==0)
						{
							$retstring .='{"head":'.$row['page_num'];
							$i++;
							$j++;
						}else if($i==0) {
							$retstring .=',{"head":'.$row['page_num'];
							$i++;	
						}
						
						$retstring .=',"'.$row['page_num'].'":{';
						
						$retstring .='"next":'.($row['page_next'] != null ? $row['page_next']:'null').',';
						$retstring .='"prev":'.($row['page_from'] != null ? $row['page_from']:'null').',';
						$retstring .='"fsp":'.($row['final_section_page'] != null ? $row['final_section_page']:0).',';
						$retstring .='"leftnav":'.$row['is_left_nav'].',';
						$retstring .='"title":"'.$row['title'].'"}';
					}
				}
				if($detailed) 
				{
					$retstring .= ']}';
				}else{
					$retstring .= '}';
				}
			}
			$retstring .=']}';
			return $retstring;
		}
		
	}
	

	
}
?>
