<?php 

class User
{
var $username;
var $password;
var $sid;
	function User($username, $password)
	{
		$this->username = $username;
		$this->password = $password;
	}
	/**
	 * Simulates a user that registers
	 * 
	 * @return array The record of the request
	 */
	function register()
	{
    	global $server;
        $record = new Record("register");
  
    	$start = microtime(true);
    	$url = "http://{$server['web']}/phpBB3/ucp.php?mode=register";
        $record->add($url,microtime(true) - $start);
    	$data = request($url);
    	
    	$agreed = "I agree to these terms";
    	$creation_time = find_creation_time($data);
    	$form_token = find_form_token($data);
    	$sid = find_sid($data);
    	
    	$start = microtime(true);
    	$url = "http://{$server['web']}/phpBB3/ucp.php?mode=register&sid=$sid";
        $data = request($url,"agreed=$agreed&creation_time=$creation_time&form_token=$form_token");
        $record->add($url,microtime(true) - $start);
    	$email = $this->username."@example.com";
        $pass = $this->password;
        $lang = "en";
        $tz = 0;
        $agreed ="true";
        $change_lang = 0;
        $submit = "Submit";
        $form_token = find_form_token($data);
        $creation_time = find_creation_time($data);
        $post = "username=$this->username&email=$email&email_confirm=$email&new_password=$pass&password_confirm=$pass&lang=$lang&tz=$tz&agreed=$agreed&change_lang=$change_lang&submit=$submit&form_token=$form_token&creation_time=$creation_time";
        $url = "http://{$server['web']}/phpBB3/ucp.php?mode=register&sid=$sid";
        
    	$start = microtime(true);
        $res = request($url,$post);
        $record->add($url,microtime(true) - $start);
        return $record;
	}
	/**
	 * Simulates a user that logs in
	 * 
	 * @return array The record of the request
	 */
	function login()
	{
    	global $server;
        $record = new Record("login");
    	$start = microtime(true);
    	$url = "http://{$server['web']}/phpBB3/ucp.php?mode=login";
        $res = request($url,"",$this->username);
        $record->add($url,microtime(true) - $start);
        
        $redirect = "index.php";
        $login = "Login";
        
        $post = "username=$this->username&password=$this->password&redirect=$redirect&login=$login";
    	$start = microtime(true);
    	$url = "http://{$server['web']}/phpBB3/ucp.php?mode=login";
        $res = request($url,$post, $this->username);
        $record->add($url,microtime(true) - $start);
        $this->sid = find_sid($res);
        return $record;
	}
	/**
	 * Simulates a user that wants to post a thread.
	 * 
	 * @param Thread $Thread The thread to be posted.
	 * 
	 * @return array The record of the request
	 */
	function post_thread($Thread)
	{
    	global $server;
		global $generator;
		global $setting;
		
        $record = new Record("post_thread");
		$start = microtime(true);
		$url = "http://{$server['web']}/phpBB3/posting.php?mode=post&f=$Thread->forum_id&sid=$this->sid";
        $res = request($url, "",$this->username); // So the session will know what's going on
        $record->add($url,microtime(true) - $start);
        $creation = find_creation_time($res);
        $lastclick = $creation; 
        
        $subject = $generator->getContent(gauss_ms($setting['avg_subject'],$setting['subject_dev']),'plain',false);
        $message = $generator->getContent(gauss_ms($setting['avg_post'],$setting['post_dev']),'plain',false);
        $form_token = find_form_token($res);
        $postdata = "icon=0&subject=$subject&addbbcode20=100&message=$message&lastclick=$lastclick&post=Submit&attach_sig=on&creation_time=$creation&form_token=$form_token";
        $url = "http://{$server['web']}/phpBB3/posting.php?mode=post&f={$Thread->forum_id}&sid=$this->sid";
        
		$start = microtime(true);
        $res = request($url,$postdata,$this->username);
        $record->add($url,microtime(true) - $start);
		$Thread->id = find_thread_id($res);
		
        return $record;
	}
	/**
	 * Simulates a user that wants to make post in a thread.
	 * 
	 * @param Post $Post The post to be made
	 * 
	 * @return array The record of the request
	 */
	function post_reply($Post) 
	{
		global $server;
		global $generator;
		global $setting;
        $record = new Record("post_reply");
        
		$start = microtime(true);
		$url = "http://{$server['web']}/phpBB3/viewtopic.php?f=$Post->forum_id&t=$Post->thread_id&sid=$this->sid";
		request($url, "",$this->username);
		$start = microtime(true);
		$res = request("http://{$server['web']}/phpBB3/posting.php?mode=reply&f=$Post->forum_id&t=$Post->thread_id&sid=$this->sid", "",$this->username);
        $record->add($url,microtime(true) - $start);
		$creation = find_creation_time($res);
        $lastclick = $creation; 
        $topic_cur_post_id = find_cur_post_id($res);
        $subject = $generator->getContent(gauss_ms($setting['avg_subject'],$setting['subject_dev']),'plain',false);
        $message = $generator->getContent(gauss_ms($setting['avg_post'],$setting['post_dev']),'plain',false);
        $form_token = find_form_token($res);
        $postdata = "icon=0&subject=$subject&addbbcode20=100&message=$message&lastclick=$lastclick&post=Submit&attach_sig=on&creation_time=$creation&form_token=$form_token&topic_cur_post_id=$topic_cur_post_id";
		
		$start = microtime(true);
        $url = "http://{$server['web']}/phpBB3/posting.php?mode=reply&f=$Post->forum_id&t=$Post->thread_id&sid=$this->sid";
		$req = request($url, $postdata,$this->username);
		
        $record->add($url,microtime(true) - $start);
        return $record;
	}
	/**
	 * Simulates a user that views a thread post
	 * 
	 * @param Thread $Thread The thread to view.
	 * 
	 * @return array The record of the request
	 */
	function view_thread($Thread) 
	{
		global $server;
        $record = new Record("post_reply");
		$start = microtime(true);
		$url = "http://{$server['web']}/phpBB3/viewtopic.php?f=$Thread->forum_id&t=$Thread->id&sid=$this->sid";
		$req = request($url, "",$this->username);
        $record->add($url,microtime(true) - $start);
        return $record;
	}
    /**
     * Serializes and saves the user into a the users directory
     */
    function save() {
    	file_put_contents("users/{$this->username}", serialize($this));
    }
}

?>