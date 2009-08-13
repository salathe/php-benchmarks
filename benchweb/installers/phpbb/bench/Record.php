<?php 

class Record
{
	var $type;
	var $time;
	function Record($type)
	{
    	$this->type = $type;
    	$this->time = array();
	}
	function add($url, $time)
	{
    	$this->time[] = $time;
    	$temp["url"] = $url;
    	$temp["time"] = $time;
    	$this->time[] = $temp;
	}

}

?>