<?php
include_once('jsonwrapper/jsonwrapper.php');
include_once('Decorator.php');
/**
	@author Aaron Elligsen
	@version 2.0
	This is the bottom level decorator. It provides the adapter between
	Decorators and the Level object. It must be the first decorator applied 
	to a Level object. 
	
	See the decorator ordering chart in docs. 
**/
class LevelDecorator extends Decorator {

	
	public function parse($functionname,array $requestdata)
	{
		$output = "";
		switch($functionname)
		{
			case "getLevelPages":
				//$output =  $this->level->getPages2();
				$output =  $this->level->getLevelLinkedLists(true);
				return $output;
				break;
			case "getPage":
				$output =  $this->level->getPage($requestdata['pageid']);
				return $output;
				break;
			case "addLevel":
				$output =  $pw->addLevel();
				return $output;
				break;
			case "addPage":
				$output =  $this->level->addPage();
				return $output;
				break;
			case "editPage":
				if($requestdata['superhipo'] ==1)
				{
					$output =  $this->level->editPage($requestdata['pageid'],$requestdata['pdata'],$requestdata['superhipo']);
				}else{
					$output =  $this->level->editPage($requestdata['pageid'],$requestdata['pdata'],0);
				}
				return $output;
				break;
			case "getQuestion":
				$output =  $this->level->getQuestion($requestdata['qid']);
				return $output;
				break;
			case "addQuestion":
				$output =  $this->level->addQuestion($requestdata['pageid']);
				return $output;
				break;
			case "deleteQuestion":
				$output =  $this->level->deleteQuestion($requestdata['qid']);
				return $output;
				break;
			case "editQuestion":
				$output =  $this->level->editQuestion($requestdata['qid'],$requestdata['pdata']);
				return $output;
				break;
			case "getLevelSections":
				$output =  $this->level->getLevelSections();
				return $output;
				break;
			case "renameLevel":
				$output =  $pw->renameLevel($requestdata['newname'],$requestdata['level']);
				return $output;
				break;
			case "renameSection":
				$output =  $this->level->renameSection($requestdata['section'],$requestdata['newname']);
				return $output;
				break;
			case "addSection":
				$output =  $this->level->addSection();
				return $output;
				break;
			case "deleteSection":
				$output =  $this->level->deleteLastSection();
				return $output;
				break;
			case "deletePage":
				$output =  $this->level->deletePage($requestdata['pageid']);
				return $output;
				break;
			case "addPageAfter":
				$output =  $this->level->addPageAfter($requestdata['pid']);
				return $output;
				break;
			case "pageDown":
				$output =  $this->level->addPage2();
				return $output;
				break;
			/*case "moveSection": //This method is not yet ready
				$output =  $this->level->moveSection($requestdata['section'],$requestdata['dir']);
				return $output;
				break;*/
			case "reorderLevel":
				$output =  $this->level->reorderLevel();
				return $output;
				break;
			case "getLevelLinkedLists":
				$output =  $this->level->getLevelLinkedLists();
				return $output;
				break;
			default:
				return '{"err":"fail"}';
				break;
		}
		
		
	}
	
	
	
	public function includeJS() 
	{ 
		//implement in the inheritting classes only
		//This will include methods to create html on the page
		//and implement javascript listener objects to interact with the javscript that already exists.
		/*
			
			return "<script src="decoratorscript.js"></script>\n".$this->includeJS($this->level);
		*/
	}
	
	
	
}
