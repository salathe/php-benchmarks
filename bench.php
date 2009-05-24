<?php
/**
 * Handles output and logging options.
 * @param $msg  The message to be handled
 * @param $print Whether it should be printed
 * 				 or not.
 * @param $log  File to log the output to.
 * 				Set to false if logging is
 * 				disabled.
 */
function console($msg,$print = true)
{
	if(VERBOSE == true)
		{
		if($print == true)
		{
			echo $msg;
		}

	}
	if(LOG_FILE != false && $print)
	{
		$fp = fopen(LOG_FILE,'a');
		fwrite($fp,$msg);
		fclose($fp);
	}
}
/**
 * Return an array with all the paths to the test files found
 * from the array of directories to search.
 * Returning array have this structure:
 * array("/path/to/test/test_name.php)" => "name.php",...,);
 * @param $test_dirs An array of directories to search in
 * @return Array of test files and their names.
 */
function get_test_files($test_dirs)
{
	$files = array();
	foreach($test_dirs as $dir)
	{
		foreach (glob("$dir/test_*.php") as $filename) {
			$t['filename'] = $filename;
			preg_match('/^'.preg_quote($dir,'/').'\/test_(.+)[.]php$/i', $filename, $matches);
			$t['name'] = $matches[1];
			$files[] = $t;
		}
	}
	
	return $files;
}
/**
 * Parses the arguments and returns them in a better structure.
 * Flags enabled will get the value 1 and the arguments will get
 * the value specified from the console.
 * @param $argv  The argument array ($argv)
 * @param $flags Existing flag options
 * @param $arguments Existing argument options
 * @return If flags and arguments were correct,
 * it will return an array with $key -> $value
 * structure.
 */
function arguments($argv,$flags,$arguments) {
    $ARGS = array();
    foreach ($argv as $arg) {
        if (ereg('--[a-zA-Z0-9]*=.*',$arg)) {
            $str = split("=",$arg); $arg = '';
            $key = ereg_replace("--",'',$str[0]);
            for ( $i = 1; $i < count($str); $i++ ) {
                $arg .= $str[$i];
            }
		if(in_array($key,$arguments))
                        $ARGS[$key] = $arg;
		else{
		console("Illegal argument: ".$key);
		usage();
		}
	   
        } elseif(ereg('-[a-zA-Z0-9]',$arg)) {
            $arg = ereg_replace("-",'',$arg);
	   if(in_array($arg,$flags))
	   {
            $ARGS[$arg] = true;
	   }
	   else
	   {
		console("Illegal flag: ".$arg);
		usage();
            }	
        }
    
    }
return $ARGS;
}
/**
 * Prints help message and exits.
 */
function usage()
{
console(<<<EOL
bench.php - Runs a sequence of algorithms to measure performance

Usage: bench [OPTIONS]

Options:
	--help				Print help message
		-h
		
	-debug			Debug mode. If combined with logging,
					debug output will come to the log as well.
	
	--m=MEM 		Sets the maximum allowed memory to MEM,
					default is 128.
					
	--include=DIR	Includes DIR as a test directory. Files
					with the filename structure of test_*.php
					in this directory will be included in the
					benchmark. Multiple directories can be 
					separated by commas (DIR,DIR,...,DIR).
					
	--log=FILE		Logs output to FILE. Can be combined with
					-debug.
	
	-q 				Sets benchmark to run quite.
EOL
);
die();
}

error_reporting(E_ALL | E_STRICT);
set_time_limit(0);

include("misc/timer.php");
$arguments = array("m","include","php","log","help");
$flags = array("debug","q");
$MEMORY_LIMIT = 128;
$DEBUG = false;
$VERBOSE = true;
$test_dirs = array('tests');
$LOG_FILE = false;
$PHP = "php";
$user_arguments = arguments($argv,$flags,$arguments);

foreach($user_arguments as $arg => $value)
{
switch($arg)
{
  case 'm': $MEMORY_LIMIT = $value; break; 
  case 'debug': $DEBUG = true; break;
  case 'include': 
	$dirs = explode(",",$value);

	foreach($dirs as $dir)
	{
	if($dir[strlen($dir)-1] == "/")
           $test_dirs[] = substr($dir,0,strlen($dir)-1);
	else
	   $test_dirs[] = $dir;
	}
   	break;
  case 'h': usage(); break;	
  case 'help': usage(); break;
  case 'php': $PHP = $value; break;
  case 'log':$LOG_FILE = $value;
  	break;
  case 'q': $VERBOSE = false; break;
}
}

$tests_list = array();
$results = array();
define("VERBOSE",$VERBOSE);
define("DEBUG",$DEBUG);
define("LOG_FILE",$LOG_FILE);
$tests_list = get_test_files($test_dirs);
$length = 1;
$timer = new Timer();
$total = 0;
$datetime =  date("Y-m-d H:i:s",time());
console("--------------- Bench.php ".$datetime."---------------------\n");
foreach($tests_list as $test)
{
	$cmd = "$PHP -d memory_limit={$MEMORY_LIMIT}M ".$test['filename'];
	console("$cmd\n",DEBUG);
	$timer->start();
	$deb =  `$cmd`;
	$timer->stop();
	console($deb,DEBUG);
	console("Results from ".$test['name'].": ".$timer->elapsed."\n");
	$total += $timer->elapsed;

		
}
console("Total time for the benchmark: ".$total." seconds\n");
console("-------------- END ".$datetime."----------------------------\n");

?>
