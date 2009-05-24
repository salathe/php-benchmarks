<?php 

/**
 * Simple timer class.
 */

class Timer{
	var $starttime;
	var $elapsed;
	
	function __construct()
	{
		$this->starttime = 0;
		$this->elapsed = 0;
	}
	function start()
	{
		$this->starttime = $this->_clock();
		$this->elapsed = 0;
	}
	function stop()
	{
		$this->elapsed = $this->_clock() - $this->starttime; 
		$this->starttime = 0;
	}
	function _clock()
	{
	    $t = microtime(); 
        $t = explode(' ', $t); 
        $t = $t[1] + $t[0]; 
        return $t; 
	}
}

?>