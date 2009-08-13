<?php 

class Post
{
    var $thread_id;
    var $forum_id;
    function Post($Thread)
    {
    	$this->thread_id = $Thread->id;
    	$this->forum_id  = $Thread->forum_id;
    }
}

?>