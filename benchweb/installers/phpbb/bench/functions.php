<?php

/**
 * Constructs and makes a random string with a variable length.
 * 
 * @param int $len The length of the random stirng
 * 
 * @return string The random string generated.
 */
function rand_string ($len = 8) {
    $chars = '0123456789abcdefghijklmnopqrstuvwxyz';
    $string = '';
    for ($i = 0; $i < $len; $i++) {
		$string .= $chars[mt_rand(0,strlen($chars)-1)];
    }
    return $string;
}
/**
 * Requests an url and returns the content. Can be used with postdata and
 * a different UserAgent.
 * 
 * @param string  $url      The url to be requested.
 * @param string  $postdata The postdata to be sent with the request.
 * @param string  $agent    The UserAgent to be used with the request.
 * @param boolean $verbose  Should all urls that are fetched be displayed or not.
 * 
 * @return string The page that was requested.
 */
function request($url, $postdata = "", $agent = "", $verbose = false) {
   $curl = curl_init();
   curl_setopt($curl, CURLOPT_URL, $url);
   curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
   curl_setopt($curl, CURLOPT_POST, 1);
   curl_setopt($curl, CURLOPT_USERAGENT, $agent);
   curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
   curl_setopt($curl, CURLOPT_HEADER, 1);
   curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
   $result = curl_exec($curl);
   if ($verbose) {
   echo $url."\n";
   }
   curl_close($curl);
   return $result; 
}
/**
 * Fetches the session id from a page.
 * 
 * @param string $data The HTML phpbb3-page to parse
 * 
 * @return string The session id found.
 */
function find_sid($data) {
    preg_match("/sid=(.*)&amp;/",$data,$match);
    return substr($match[1],0,strlen($match[1])-9);
}
/**
 * Fetches the form token of the form.
 * 
 * @param string $data The HTML phpbb3-page to parse
 * 
 * @return string The form token from the form
 */
function find_form_token($data) {
    preg_match("/name=\"form_token\" value=\"(.*)\"/",$data,$match);
    if (isset($match[1])) {
    	return $match[1];
    } else {
    //Something went wrong here, return -1
    return -1;
    }
}
/**
 * Fetches the creation time of the form
 * 
 * @param string $data The HTML phpbb3-page to parse
 * 
 * @return int The creation time of the form
 */
function find_creation_time($data) {
    preg_match("/creation_time\" value=\"(.*)\"/",$data,$match);
    if (isset($match[1])) {
    	return $match[1];
    } else {
    //Something went wrong here, return -1
    return -1;
    }
}
/**
 * Fetches the current post id in a thread before a post can be made. 
 * 
 * @param string $data The HTML phpbb3-page to parse
 * 
 * @return int The current post id 
 */
function find_cur_post_id($data) {
    preg_match("/name=\"topic_cur_post_id\" value=\"(.*?)\"/",$data,$match);
    if (isset($match[1])) {
    	return $match[1];
    } else {
    //Something went wrong here, return -1
    return -1;
    }
}

/**
 * Matches the thread id after a new thread has been created.
 * 
 * @param string $data The HTML phpbb3-page to parse
 * 
 * @return int The thread id 
 */
function find_thread_id($data) {
    preg_match("/&amp;t=(.*)&amp;/",$data,$match);
    if (isset($match[1])) {
    	return $match[1];
    } else {
    //Something went wrong here, return -1
    return -1;
    }
}
/**
 * Returns a random thread based on the files in the threads directory
 * 
 * @return Thread A random Thread-object
 */
function rand_thread() {
    $res = glob("threads/*");
    $rand = $res[array_rand($res)];
    return unserialize(file_get_contents("$rand"));
}
/**
 * Returns a random user based on the files in the users directory
 * 
 * @return User A random User-object
 */
function rand_user() {
    $res = glob("users/*");
    $rand = $res[array_rand($res)];
    return unserialize(file_get_contents("$rand"));
}
/**
 * Get a random forum to use for posting etc. Still hardcoded
 * 
 * @return int A random forum id.
 */
function rand_forum() {
	return rand(2,9);
}
function find_usage() {
	global $setting; // From config.php
    $last = 0;
    $cur = 0;
    $perc = rand(0,100);
    foreach($setting['order'] as $key => $val) {
        $cur = $val + $last;
        if($last <= $perc && $cur >= $perc) {
        	return $key;
    	}
    $last = $val;
	}
}
/**
 * Returns the numbef of child processes currently running
 */
function num_childs() {
	return count(glob("data/*"));
}
/**
 * Returns the numbef of users that have been created
 */
function num_users() {
	return count(glob("users/*"));
}

/**
 * Saves the record as a serialized variable to a file with each entry as an own row
 *
 * @param array $record A object of type record.
 */
function append_record ($record) {
	global $VERBOSE;
	if ($VERBOSE == true) {
		$rec = sum_record($record);
		echo round($rec["time"],3)."s : ".$rec["type"]."\n";
	}
	file_put_contents("record.txt", serialize($record)."\n", FILE_APPEND);
}

/**
 * Summarizes a record and returns it's type and total time
 */
function sum_record ($record) {
	$totaltime = 0;
	$count = 0;
	foreach ($record->time as $post)
    	{
    		$count++;
    		$totaltime += $post["time"];
    	}
    $rec["count"] = $count;
    $rec["type"] = $record->type;
    $rec["time"] = $totaltime;
    return $rec;
}
/**
 * **********************************************************************************************************************
 * The next three functions are taken from comment 13-Jun-2005 12:32 at http://www.php.net/manual/en/function.rand.php
 * **********************************************************************************************************************
 */
function gauss()
{   // N(0,1)
    // returns random number with normal distribution:
    //   mean=0
    //   std dev=1

    // auxilary vars
    $x=random_0_1();
    $y=random_0_1();

    // two independent variables with normal distribution N(0,1)
    $u=sqrt(-2*log($x))*cos(2*pi()*$y);
    $v=sqrt(-2*log($x))*sin(2*pi()*$y);

    // i will return only one, couse only one needed
    return $u;
}
function gauss_ms($m=0.0,$s=1.0)
{   // N(m,s)
    // returns random number with normal distribution:
    //   mean=m
    //   std dev=s

    return gauss()*$s+$m;
}
function random_0_1()
{   // auxiliary function
    // returns random number with flat distribution from 0 to 1
    return (float)rand()/(float)getrandmax();
}
/**
 * **********************************************************************************************************************
 * End of the three functions, taken from comment 13-Jun-2005 12:32 at http://www.php.net/manual/en/function.rand.php
 * **********************************************************************************************************************
 */
?>
