<?php
chdir("/home/bench/"); //Set's the directory
require "config.php";
if($argc == 3) {
$tot_rec = $argv[1];
$concurrency = $argv[2];
} else {
// Default settings
$tot_rec = 50;
$concurrency = 5;
}
/**
 * Create the default thread that is always existing
 */
$thr = new Thread(2);
$thr->id = 1;
$thr->save();

/**
 * Create 10 users to start off with.
 */
for($i = 0; $i < 10; $i++ ) {
    $usr = new User(rand_string(),"phpbench");
    $usr->register();
    $usr->save();
}

$x = 1;
$cur = 0;
$timerecord = array();
$VERBOSE = TRUE;
while($x < $tot_rec) {
        $cur = num_childs();
	usleep(200);
        if ($cur < $concurrency)
        {
                $pid = pcntl_fork();
                if($pid != -1) {
                        if($pid) {
                                //PARENT
                        } else {
                                    file_put_contents('data/'.$x,$x);
                                    $usage = find_usage();
                                    $forum_id = rand_forum();
                                	switch ($usage) {
                                		case 'view_topic':
                                			break;
                                		case 'reply':
                                			//Make reply
                                			$post = new Post(rand_thread());
                                			$user = rand_user();
                                			append_record($user->login());
                                			append_record($user->post_reply($post));
											$user->save();
                                			break;	
                                		case 'thread':
                                		    //MAKE THREAD
                                			$thread = new Thread($forum_id);	
                                			$user = rand_user();
                                			append_record($user->login());
											append_record($user->post_thread($thread));
                                		    if($thread->id) {	
												$thread->save();
											}
											break;
                                		case 'register':
                                			$usr = new User(rand_string(),"phpbench");
                                			append_record($usr->register());
                                			$usr->save();
											break;
                                	}
                                    unlink("data/".$x);
                                    exit();
                        }
                }
                $x++;
        }

}

//Wait for all processes to complete
while(num_childs() != 0) {
	sleep(1);
}


$file = file('record.txt');
$totaltime = 0;
$reqs = 0;
foreach ($file as $line)
{
$record = unserialize($line);
$rec = sum_record($record);
$totaltime += $rec["time"];
$reqs += $rec["count"];
}
echo "\n";
echo "Total requests: ". $reqs."\n";
echo "Requests per s: ". round($totaltime/$reqs,2)."\n";
?>
