<?php
 /**
	@version 1.7
	@modified 6/13/2011
	@author http://www.codediesel.com/algorithms/doubly-linked-list-in-php/
	Instead of writing my own linked list for the Nth time I found one online. 
	I realized later php has a native implementation. Version 3 should probably use it
	instead.
**/
/* doublelist.class.php */
 
class ListNode {
 
    public $data;
    public $next;
    public $previous;
 
    function __construct($data) {
        $this->data = $data;
    }
 
    public function readNode() {
        return $this->data;
    }
 
}
 
 
class DoublyLinkedList {
 
    private $_firstNode;
    private $_lastNode;
    private $_count;
    private $_walkPointer;
    private $_inWalk;
 
    function __construct() {
        $this->_firstNode = NULL;
        $this->_lastNode = NULL;
        $this->_walkPointer = NULL;
        $this->_count = 0;
        $this->_inWalk = false;
    }
 
    public function isEmpty() {
        return ($this->_firstNode == NULL);
    }
 
    public function insertFirst($data) {
        $newLink = new ListNode($data);
 
        if($this->isEmpty()) {
            $this->_lastNode = $newLink;
        } else {
            $this->_firstNode->previous = $newLink;
        }
 
        $newLink->next = $this->_firstNode;
        $this->_firstNode = $newLink;
        $this->_count++;
    }
 
 
    public function insertLast($data) {
        $newLink = new ListNode($data);
 
        if($this->isEmpty()) {
            $this->_firstNode = $newLink;
        } else {
            $this->_lastNode->next = $newLink;
        }
 
        $newLink->previous = $this->_lastNode;
        $this->_lastNode = $newLink;
        $this->_count++;
    }
 
 
    public function insertAfter($key, $data) {
        $current = $this->_firstNode;
 
        while($current->data != $key) {
            $current = $current->next;
 
            if($current == NULL)
                return false;
        }
 
        $newLink = new ListNode($data);
 
        if($current == $this->_lastNode) {
            $newLink->next = NULL;
            $this->_lastNode = $newLink;
        } else {
            $newLink->next = $current->next;
            $current->next->previous = $newLink;
        }
 
        $newLink->previous = $current;
        $current->next = $newLink;
        $this->_count++;
 
        return true;
    }
 
 
    public function deleteFirstNode() {
 
        $temp = $this->_firstNode;
 
        if($this->_firstNode->next == NULL) {
            $this->_lastNode = NULL;
        } else {
            $this->_firstNode->next->previous = NULL;
        }
 
        $this->_firstNode = $this->_firstNode->next;
        $this->_count--;
        return $temp;
    }
 
 
    public function deleteLastNode() {
 
        $temp = $this->_lastNode;
 
        if($this->_firstNode->next == NULL) {
            $this->firtNode = NULL;
        } else {
            $this->_lastNode->previous->next = NULL;
        }
 
        $this->_lastNode = $this->_lastNode->previous;
        $this->_count--;
        return $temp;
    }
 
 
    public function deleteNode($key) {
 
        $current = $this->_firstNode;
 
        while($current->data != $key) {
            $current = $current->next;
            if($current == NULL)
                return null;
        }
 
        if($current == $this->_firstNode) {
            $this->_firstNode = $current->next;
        } else {
            $current->previous->next = $current->next;
        }
 
        if($current == $this->_lastNode) {
            $this->_lastNode = $current->previous;
        } else {
            $current->next->previous = $current->previous;
        }
 
        $this->_count--;
        return $current;
    }
 
 
    public function displayForward() {
 
        $current = $this->_firstNode;
 
        while($current != NULL) {
            echo $current->readNode() . " ";
            $current = $current->next;
        }
    }
    
 
    public function displayBackward() {
 
        $current = $this->_lastNode;
 
        while($current != NULL) {
            echo $current->readNode() . " ";
            $current = $current->previous;
        }
    }
 
    public function totalNodes() {
        return $this->_count;
    }
    
    //My functions
    public function walkForward() {
    	    $current = $this->_firstNode;
    	    $page_count = 0;
        while($current != NULL) {
        	$page_count++;
        	$node_data = $current->readNode();
            echo $node_data['page_num'] . " -> ";
            $current = $current->next;
        }
        echo " Page Count: $page_count";
        
    }
    
    public function levelIntegrityCheck()
    {
    	$current = $this->_firstNode;
    	$pages =  array();
    	echo "<br>walking forwards\n<br>";
    	while($current != NULL) {
    		
        	$node_data = $current->readNode();
        	$pages[] = $node_data['page_num'];
            echo $node_data['page_num'] . " -> ";
            $current = $current->next;
        }
        echo "<br>";
        $rev_pages = array();
       // $i = 0;
        $current = $this->_lastNode;
        $finalp = $current->readNode();
        $rev_pages[] = $finalp['page_num'];
        $retval = array();
        echo "Walking backwards: ";
        while($current != NULL) {
        	$node_data = $current->readNode();
        	if($node_data['page_from'] != null)
        	{
        		$rev_pages[] = $node_data['page_from'];
        	}
        	echo $node_data['page_from'] . " -> ";
        	$current = $current->previous;
        }
        if(count($rev_pages)  != count($pages))
        {
        	echo "Size of arrays unequal";	
        }else{
        	for($i=0; $i < count($rev_pages); $i++)
        	{
        		if($pages[count($pages)-$i-1] != $rev_pages[$i])
        		{
        			$retval["message"] = "Broken page on $i ".$rev_pages[$i]."<br>".$pages[count($pages)-$i-1];
        			$retval["valid_list"] = false;
        			break;
        		}
        	}
        	
        }
        /*echo "
        
        
        
        ".print_r($rev_pages)."<br><br>".print_r($pages);*/
        return $retval;
        
    }
    
    public function levelNodeIntegrity()
    {
    	    
	$current = $this->_firstNode;
	$previous = null;
	$pages =  array();
	//echo "<br>walking forwards\n<br>";
	while($current != NULL) {
		$node_data = $current->readNode();
		$previous = $current;
		$current = $current->next;
		if($current != NULL) {
			if($current->previous != $previous)
			{
				$finalp = $current->readNode();
				
				return array("brokenlist"=>true,"broken_page"=>$finalp['page_num']);
				break;
			}
		}
	}
	return array("brokenlist"=>false);
    }
    
    public function prepareWalk()
    {
    	$this->_walkPointer = $this->_firstNode;	    
    } 
    
    
    public function getLast()
    {
	if($this->_lastNode != null)
    	{
    		return NULL;
    	}else{
		$retval = $this->_lastNode->readNode();
		return $retval;
    	}
    }
   
    
    public function walk() 
    {
    	
    	if($this->_walkPointer != NULL)
    	{
    		$retval =$this->_walkPointer->readNode();
    		if($this->_walkPointer == $this->_walkPointer->next)
    		{
    			$this->_walkPointer = NULL;
    		}else{
    			$this->_walkPointer = $this->_walkPointer->next;
    		}
    		//echo $retval['page_num'].'<br>';
    		return $retval;
    		
    	}else{
    		return NULL;	
    	}
    	
    }
    
    
 
}
 
 
?>
