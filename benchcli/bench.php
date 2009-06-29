<?php
// +----------------------------------------------------------------------+
// | PHP Version 5                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 2009 The PHP Group                                     |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is available through the world-wide-web at the following url:   |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you were unable to obtain it  though the world-wide-web, please   |
// | send a note to license@php.net so we can mail you a copy immediately |
// +----------------------------------------------------------------------+
// | Author: Alexander Hjalmarsson <hjalle@php.net>                       |
// +----------------------------------------------------------------------+
//

/**
 * This benchmark measures the performance of PHP
 * Example of usage:
 * bench.php --log log.txt -d -m 256
 *
 * @author    Alexander Hjalmarsson <hjalle@php.net>
 * @copyright 2009
 * @license   http://www.php.net/license/3_0.txt PHP License 3.0
 */


error_reporting(E_ALL | E_NOTICE);
set_time_limit(0);
/**
 * This is the main class of the benchmark script
 */
class Benchmark
{
    /**
     * Maximum memory usage for php test files.
     * @var int
     */
    var $memory_limit;

    /**
     * Debug switch
     * @var boolean
     */
    var $debug;

    /**
     * Directories to search for php test files.
     * @var array
     */
    var $paths;

    /**
     * Path to the php binary
     * @var string
     */
    var $php;

    /**
     * The config for command line arguments
     * @var array
     */
    var $config;

    /**
     * The log file for output
     * @var string
     */
    var $log_file;

    /**
     * Produce output or not
     */
    var $quite;

    /**
     * Holds the test files that will be tested
     * @var array
     */
    var $test_files;

    /**
     * Switch for verbosity
     * @var boolean
     */
    var $verbose;

    /**
     * Whether cachegrind should be used or not
     * @var boolean
     */
    var $cachegrind;

    /**
     * Class for parsing and sorting Smaps-files
     * @var object
     */
    var $smapsparser;

    /**
     * Variable that holds the total results if
     * a tool is used
     * @var array
     */
    var $totresults;

    /**
     * Switch for showing memory usage or not
     * @var boolean
     */
    var $memusage;

    /**
     * Log file for comparing different runs
     */
    var $complog;

    /**
     * Current tool that is used
     * @var string
     */
    var $tool;
    /**
     * Constructor for the benchmark class. Uses the PEAR package
     * Console_getargs for command line handling.
     *
     * @return void
     */
    function Benchmark()
    {
        include_once 'misc/getargs.php';
        include_once 'misc/timer.php';
        include_once 'misc/smapsparser.php';
        include_once 'misc/cachegrindparser.php';
        $this->setConfig();
        $args              = &Console_Getargs::factory($this->config);
        $this->smapsparser = new Smapsparser();
        if (PEAR::isError($args) || $args->getValue('help')) {
            $this->printHelp($args);
            exit;
        }

        $this->debug        = $args->getValue('debug');
        $this->memory_limit = $args->getValue('memory-limit');
        if ($this->memory_limit == "") {
            $this->memory_limit = 128;
        }
        $this->paths = $args->getValue('path');
        if (is_string($this->paths)) {
            //user has one directory as input, therefore
            //$this->paths is recognized as a string
            $tmpdir        = $this->paths;
            $this->paths   = array();
            $this->paths[] = $tmpdir;
        } else if (empty($this->paths)) {
            //There are no included directories
            //Let's add the default ones
            $this->paths   = array();
            $this->paths[] = "tests";
            $this->paths[] = "microtests";
        }

        $this->php = $args->getValue('php');
        if ($this->php == "") {
            $this->php = "php";
        }
        $this->log_file = $args->getValue('log');
        if ($this->log_file == "") {
            $this->log_file = false;
        }
        $valid_tools = array("memusage", "cachegrind", "papiex");
        $tool = $args->getValue('tool');
        if (!empty($tool)) {
            if (in_array($tool, $valid_tools)) {
                $this->$tool = true;
                $this->tool = $tool;
            } else {
                $this->printHelp($args);
                exit;
            }
        }
        $this->complog = $args->getValue('complog');
        if (empty($this->complog)) {
            $this->complog = false;

        } else if($this->complog == "bench_PHPVERSION_TOOL_PID.txt") {
            //This is the default log, change PHPVERSION and PID to
            //the correct values.
            $this->complog = "bench_".phpversion()."_".$this->tool."_".getmypid().".txt";
        }
        $this->comparelogs = $args->getValue('comparison');
        if(!empty($this->comparelogs)) {
            if (count($this->comparelogs) == 1) {
                $this->printHelp($args);
                exit;
            } else {
                $this->compareLogs();
                exit();
            }
        }
        $this->quite      = $args->getValue('quite');
        $this->verbose    = $args->getValue('verbose');
        $this->totresults = array();
        $this->setTestFiles();
    }
    /**
     * Prints message to the screen
     *
     * @param string $msg   The message to print
     * @param bool   $print Whether to print or not print
     *
     * @return void
     */
    function console($msg, $print=true)
    {
        if (!$this->quite) {
            if ($print) {
                echo $msg;
            }
        }
        if ($this->log_file && $print) {
            file_put_contents($this->log_file, $msg, FILE_APPEND);
        }
    }

    /**
     *	Sets the config for command line arguments
     *
     *  @return void
     */
    function setConfig()
    {
        $this->config = array(
            'memory-limit' => array('short' => 'ml',
                     'min' => 0,
                     'max' => 1,
                 'default' => 128,
                    'desc' => 'Set the maximum memory usage.'),
            'debug'        => array('short' => 'd',
                     'max' => 0,
                    'desc' => 'Switch to debug mode.'),
            'path'         => array('min' => 0,
                     'max' => -1,
                    'desc' => 'Path/paths to php test files',
            'default'      => 'microtests, tests'),
            'help'         => array('short' => 'h',
                     'max' => 0,
                    'desc' => 'Print this help message'),
            'php'          => array('min' => 0,
                     'max' => 1,
                 'default' => 'php',
                    'desc' => 'PHP binary path'),
            'verbose'      => array('short' => 'v',
                     'max' => 0),
            'quite'        => array('short' => 'q',
                     'max' => 0,
                    'desc' => 'Don\'t produce any output'),
            'tool'         => array('max' => 1,
                     'min' => 0,
                    'desc' => 'Specify which tool you want to use for special measurements. Valid tools are cachegrind, memusage and papiex',
                 'default' => ""),
            'log'          => array('min' => 0,
                     'max' => 1,
                 'default' => 'benchlog.txt',
                    'desc' => 'Log file path. This is a general log that not is made for comparing results, for that purpose, use complog'),
            'complog'      => array('min' => 0,
                     'max' => 1,
                 'default' => 'bench_PHPVERSION_TOOL_PID.txt',
                    'desc' => 'Log file made for comparing runs.'),
            'comparison'   => array('min' => 0,
                     'max' => -1,
                    'desc' => 'Log files to compare. All log files must have the same tool used when benchmarked. You must compare at least two log files.')
        );
    }

    /**
     *	Prints the help message for the benchmark
     *
     *  @param array $args The arguments to be listed
     *
     *  @return void
     */
    function printHelp($args)
    {
        $header = "Php Benchmark Example\n".
              'Usage: '.basename($_SERVER['SCRIPT_NAME'])." [options]\n\n";
        if ($args->getCode() === CONSOLE_GETARGS_ERROR_USER) {
            echo Console_Getargs::getHelp($this->config, $header, $args->getMessage())."\n";
        } else if ($args->getCode() === CONSOLE_GETARGS_HELP) {
            echo Console_Getargs::getHelp($this->config, $header)."\n";
        }
    }
    /**
     * Return an array with all the paths to the test files found
     * from the array of directories to search. It searches for files
     * with the structure of test_*.php. It then strips the full path
     * so the elements in the returning array will hold both the full
     * path and the name of the script.
     * Example:
     *
     * A file called test_array.php that lies in /home/user/tests can
     * make the returning array look like this:
     *
     * array([0] => array (	 [filename] => "/home/user/tests/test_array.php",
     * 						 [name]	    => "array.php")
     * );
     *
     * @return void
     */
    function setTestFiles()
    {
        $files = array();
        foreach ($this->paths as $dir) {
            foreach (glob("$dir/test_*.php") as $filename) {
                $t['filename'] = $filename;
                preg_match('/test_(.+)[.]php$/i', $filename, $matches);
                $t['name'] = $matches[1];
                $files[]   = $t;
            }
        }
        $this->test_files = $files;
    }

    function addTotal($result)
    {
        foreach ($result as $key => $value) {
            if (is_numeric($value)) {
                $this->totresults[$key] += $value;
            }
        }
    }
    /**
     * Runs the benchmark
     *
     * @return void
     */
    function run()
    {
        $timer       = new Timer();
        $totaltime   = 0;
        $datetime    = date("Y-m-d H:i:s", time());
        $startstring = "";
        $res = array();
        $this->console("--------------- Bench.php ".$datetime."--------------\n");
        $this->console("PHP version: ".phpversion()."\n");
        $this->console(php_uname("s")." ".php_uname("r")." ".php_uname("m")."\n");
        if (count($this->test_files) == 0) {
            $this->console("No test files were found in chosen path: exiting.");
            die();
        }
        foreach ($this->test_files as $test) {
            $output = array();
            if ($this->cachegrind) {
                $startstring = "valgrind --tool=cachegrind --branch-sim=yes";
            }
            $cmd = "$startstring {$this->php} -d memory_limit={$this->memory_limit}M ".$test['filename'];

            $this->console("Start of ".$test['name']."\n");
            $this->console("$cmd\n", $this->debug);
            $timer->start();
            list($out, $err, $exit) = $this->completeExec($cmd, null, 0);
            if ($this->memusage) {
                $res = $this->smapsparser->peak;
                if (!$this->quite && $this->verbose) {
                    $this->smapsparser->printMaxUsage(10);
                }
                if ($this->log_file && $this->verbose) {
                    $this->smapsparser->printMaxUsage(10, $this->log_file);
                }
                $this->smapsparser->clear();
            } else if ($this->cachegrind) {
                $cacheparser = new Cachegrindparser();
                $cacheparser->parse($err);
                $res = $cacheparser->results;
                if (!$this->quite && $this->verbose) {
                    $cacheparser->printResults();
                }
                if ($this->log_file && $this->verbose) {
                    $cacheparser->printResults($this->log_file);
                }
            }
            if (empty($this->totresults)) {
                $this->totresults = $res;
            } else {
                $this->addTotal($res);
            }

            $timer->stop();
            $this->console($out, $this->debug);
            $this->console("Results from ".$test['name'].": ".$timer->elapsed."\n");
            $totaltime += $timer->elapsed;
        }
        switch ($this->tool) {
            case "cachegrind":
                $cacheparser          = new Cachegrindparser();
                $cacheparser->results = $this->totresults;
                if (!$this->quite) {
                    $cacheparser->printResults();
                }
                if ($this->log_file) {
                    $cacheparser->printResults($this->log_file);
                }
                break;
            case "memusage":
                $this->smapsparser->peak = $this->totresults;
                if (!$this->quite) {
                    $this->smapsparser->printSumUsage();
                }
                if ($this->log_file) {
                    $this->smapsparser->printSumUsage($this->log_file);
                }
                break;
            case "papiex":
                break;
            default:
                break;
        }
        $this->console("Total time for the benchmark: ".$totaltime." seconds\n");
        if ($this->complog) {
            $this->createCompLog($totaltime);
            $this->console("Comparison log: ".$this->complog." has been created\n");
        }
        $datetime = date("Y-m-d H:i:s", time());
        $this->console("-------------- END ".$datetime."---------------------\n");
    }
    /**
     * Creates a comparable log. It serializes the totresults array and add some
     * additional information in the array for comparison details.
     *
     * @param int $runtime The total runtime of the benchmark
     *
     * @return void
     */
    function createCompLog($runtime)
    {
        $log                = $this->totresults;
        $log["php_version"] = phpversion();
        $log["php_uname"]   = php_uname("s")." ".php_uname("r")." ".php_uname("m");
        $log["runtime"]     = $runtime;
        $log["tool"]        = $this->tool;
        $serialized         = serialize($log);
        $fh                 = fopen($this->complog,"w");
        fwrite($fh,$serialized);
        fclose($fh);
    }
    /**
     * Compare the elements based on runtime.
     * (Lowest is the best)
     * 
     * @param $a First element
     * @param $b Seconds element
     * 
     * @return -1, 1 or 0 depending on the comparison
     */
    static function cmpRuntime($a,$b)
    {
        if ($a['runtime'] == $b['runtime']) {
            return 0;
        }
        return ($a['runtime'] > $b['runtime']) ? 1 : -1;
    }
    /**
     * Compare the number of instructions
     * (Lowest is the best)
     * 
     * @param $a First element
     * @param $b Seconds element
     * 
     * @return -1, 1 or 0 depending on the comparison
     */
    static function cmpInstructions($a,$b)
    {
        if ($a['instruction'] == $b['instruction']) {
            return 0;
        }
        return ($a['instruction'] > $b['instruction']) ? 1 : -1;
    }
    /**
     * Compare elements based on VM Size
     * (Lowest is the best)
     * 
     * @param $a First element
     * @param $b Seconds element
     * 
     * @return -1, 1 or 0 depending on the comparison
     */
    static function cmpMemusage($a,$b)
    {
        if ($a['Size'] == $b['Size']) {
            return 0;
        }
        return ($a['Size'] > $b['Size']) ? 1 : -1;
    }
    /**
     * Compare the branch mispredictions
     * (Lowest is the best)
     * 
     * @param $a First element
     * @param $b Seconds element
     * 
     * @return -1, 1 or 0 depending on the comparison
     */
    static function cmpBranchmispredictions($a,$b)
    {
        if ($a['branch_misprediction'] == $b['branch_misprediction']) {
            return 0;
        }
        return ($a['branch_misprediction'] > $b['branch_misprediction']) ? 1 : -1;
    }
    /**
     *  Compare the total cache misses
     * (Lowest is the best)
     * 
     * @param $a First element
     * @param $b Seconds element
     * 
     * @return -1, 1 or 0 depending on the comparison
     */
    static function cmpCachemisses($a,$b)
    {
        $totA = $a['instruction_l1_miss'] + $a['data_l1_miss'] + $a['data_l2_miss'] + $a['data_l2_miss'];
        $totB = $b['instruction_l1_miss'] + $b['data_l1_miss'] + $b['data_l2_miss'] + $b['data_l2_miss'];
        if ($totA == $totB) {
            return 0;
        }
        return ($totA > $totB) ? 1 : -1;
    }
    /**
     * Compares the log files and prints the results. It do only compare runs that have used
     * the same benchmark-tools, ie: memusage or cachegrind.
     * 
     * @return void
     */
    function compareLogs()
    {
        $results = array();
        $this->console("--------------- bench.php Comparison --------------\n");
        $tool = "";
        foreach ($this->comparelogs as $log) {
            if ($temp = file_get_contents($log)) {
                $res = unserialize($temp);
                if ($res['tool'] != $tool && $tool != "") {
                    $this->console("The benchmarks do not have the same tools benchmarked. Exiting\n");
                    exit;
                }
                $results[] = $res;
                $tool = $res['tool'];
            } else {
                $this->console("File: $log could not be found. Exiting\n");
                exit;
            }
        }
        $this->printComparison($results);
        $this->console("--------------- bench.php End Comparison -----------\n");
    }
    /**
     * Prints the comparison results of the selected results
     * 
     * @param array $results List of results to be compared
     * 
     * @return void
     */
    function printComparison($results)
    {
        $tool = $results[0]['tool'];
        $winmem = "";
        $wintime = "";
        switch ($tool) {
            case "cachegrind":
                usort($results, "Benchmark::cmpInstructions");
                $wininstructions = $results[0]['instruction'];
                usort($results, "Benchmark::cmpCachemisses");
                $totcachemisses = $results[0]['instruction_l1_miss'] + $results[0]['data_l1_miss'] + $results[0]['data_l2_miss'] + $results[0]['data_l2_miss'];
                usort($results, "Benchmark::cmpBranchmispredictions");
                $branchmispred  = $results[0]['branch_misprediction'];
                printf("%-15s %-35s %-22s %-20s %-20s\n", "PHP version", "PHP uname", "Branch Mispred.", "Instructions","Cache misses");
                foreach ($results as $result) {
                    $loinstr         = sprintf("%e", $result["instruction"])." (".round((($result["instruction"]/$wininstructions)),1)*100 ."%)";
                    $lototcache      = $result['instruction_l1_miss'] + $result['data_l1_miss'] + $result['data_l2_miss'] + $result['data_l2_miss'];
                    $locachemiss     = sprintf("%e", $lototcache)." (".round((($lototcache/$totcachemisses)),1)*100 ."%)";
                    $lobranchmispred = sprintf("%e", $result["branch_misprediction"]). " (".round((($result["branch_misprediction"]/$branchmispred)),1)*100 . "%)";
                    printf("%-15s %-35s %-22s %-20s %-20s\n", $result['php_version'], $result['php_uname'],$lobranchmispred, $loinstr,$locachemiss);
                }
                break;
            case "memusage":
                usort($results, "Benchmark::cmpRuntime");
                $wintime = $results[0]['runtime'];
                usort($results, "Benchmark::cmpMemusage");
                $winmem = $results[0]['Size'];
                printf("%-15s %-35s %-17s %-15s\n", "PHP version", "PHP uname", "Runtime", "VM Size");
                foreach ($results as $result) {
                    $loruntime = round($result["runtime"],1)."s (".(round($result["runtime"]/$wintime,2)*100)."%)";
                    $lomem     = round($result["Size"]/1024,1)."mB (".round((($result["Size"]/$winmem)),1)*100 ."%)";
                    printf("%-15s %-35s %-17s %-15s \n", $result['php_version'], $result['php_uname'], $loruntime,$lomem);
                }
                break;
            case "papiex":
                break;
            default:
                //Sort benchmark results by runtime
                usort($results, "Benchmark::cmpRuntime");
                $wintime = $results[0]['runtime'];
                printf("%-15s %-35s %-17s\n", "PHP version", "PHP uname", "Runtime");
                foreach ($results as $result) {
                    $loruntime = round($result["runtime"],1)."s (".(round($result["runtime"]/$wintime,2)*100)."%)";
                    printf("%-15s %-35s %-17s %-15s \n", $result['php_version'], $result['php_uname'], $loruntime);
                }
                break;
        }

    }
    /**
     * Executes a program in proper way. The function is borrowed
     * the php compiler project, http://www.phpcompiler.org.
     *
     * @param string $command The command to be executed
     * @param string $stdin   stdin
     * @param int    $timeout Seconds until it timeouts
     *
     * @return array
     */
    function completeExec($command, $stdin = null, $timeout = 20)
    {
        $descriptorspec = array(0 => array("pipe", "r"),
        1 => array("pipe", "w"),
        2 => array("pipe", "w"));
        $pipes          = array();
        $handle         = proc_open($command, $descriptorspec, &$pipes, getcwd());

        // read stdin into the process
        if ($stdin !== null) {
            fwrite($pipes[0], $stdin);
        }
        fclose($pipes[0]);
        unset($pipes[0]);

        // set non blocking to avoid infinite loops on stuck programs
        stream_set_blocking($pipes[1], 0);
        stream_set_blocking($pipes[2], 0);

        $out = "";
        $err = "";

        $start_time = time();
        do {
            $status = proc_get_status($handle);
            // It seems that with a large amount fo output, the process
            // won't finish unless the buffers are periodically cleared.
            // (This doesn't seem to be the case is async_test. I don't
            // know why).
            $new_out = stream_get_contents($pipes[1]);
            $new_err = stream_get_contents($pipes[2]);
            $out    .= $new_out;
            $err    .= $new_err;
            $pid     = $this->getChildren($handle, $pipes);
            if ($this->memusage) {
                if ($data = $this->smapsparser->readSmapsData($pid[0])) {
                    $this->smapsparser->parseSmapsData($data);
                }
            }
            if ($timeout != 0 && time() > $start_time + $timeout) {
                $out = stream_get_contents($pipes[1]);
                $err = stream_get_contents($pipes[2]);

                $this->killProperly($handle, $pipes);

                return array("Timeout", $out, $err);
            }

            // Since we use non-blocking, the for loop could well take 100%
            // CPU. time of 1000 - 10000 seems OK. 100000 slows down the
            // program by 50%.
            usleep(50000);
        } while ($status["running"]);
        stream_set_blocking($pipes[1], 1);
        stream_set_blocking($pipes[2], 1);
        $out .= stream_get_contents($pipes[1]);
        $err .= stream_get_contents($pipes[2]);

        $exit_code = $status["exitcode"];
        $this->killProperly($handle, $pipes);

        return array($out, $err, $exit_code);

    }
    /**
     * Get's the child processes of a shell execution.
     *
     * @param handler &$handle The handler
     * @param array   &$pipes  The pipes
     *
     * @return array  All children processes pid-number.
     */
    function getChildren(&$handle, &$pipes)
    {
        $status = proc_get_status($handle);
        $ppid   = $status["pid"];
        $pids   = preg_split("/\s+/", trim(`ps -o pid --no-heading --ppid $ppid`));
        return $pids;
    }
    /**
     * Kills a process properly
     *
     * @param handler &$handle The handler
     * @param array   &$pipes  The pipes
     *
     * @return void
     */
    function killProperly(&$handle, &$pipes)
    {

        // proc_terminate kills the shell process, but won't kill a runaway infinite
        // loop. Get the child processes using ps, before killing the parent.
        $pids = $this->getChildren($handle, $pipes);

        // if we dont close pipes, we can create deadlock, leaving zombie processes.
        foreach ($pipes as &$pipe) {
            fclose($pipe);
        }
        proc_terminate($handle);
        proc_close($handle);

        // Not necessarily available.
        if (function_exists("posix_kill")) {
            foreach ($pids as $pid) {
                if (is_numeric($pid)) {
                    posix_kill($pid, 9);
                }
            }
        }
    }


}

$bench = new Benchmark();
$bench->run();
?>
