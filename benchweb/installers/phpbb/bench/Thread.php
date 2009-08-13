<?php 

class Thread
{
    var $id;
    var $forum_id;
    function Thread($forum_id)
    {
    	$this->forum_id = $forum_id;
    }
    /**
     * Serializes and saves the thread into a the threads directory
     */
    function save() {
    	file_put_contents("threads/{$this->id}", serialize($this));
    }
}

?>