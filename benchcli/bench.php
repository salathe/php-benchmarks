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
    var $include;

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
     * Constructor for the benchmark class. Uses the PEAR package
     * Console_getargs for command line handling.
     *
     * @return void
     */
    function Benchmark() 
    {
        include_once 'misc/getargs.php';
        include_once 'misc/timer.php';

        $this->setConfig();
        $args =& Console_Getargs::factory($this->config);

        if (PEAR::isError($args) || $args->getValue('help')) {
            $this->printHelp($args);
            exit;
        }

        $this->debug        = $args->getValue('debug');
        $this->memory_limit = $args->getValue('memory-limit');
        if ($this->memory_limit == "") {
            $this->memory_limit = 128;
        }
        $this->include = $args->getValue('include');
        if (is_array($this->include)) {
            $this->include[] = "tests"; // Append default directory
        } else {
            //user has one directory as input, therefore
            //$this->include is recognized as a string
            $tmpdir          = $this->include;
            $this->include   = array();
            $this->include[] = $tmpdir;
            $this->include[] = "tests";
        }
        $this->php = $args->getValue('php');
        if ($this->php == "") {
            $this->php = "php";
        }
        $this->log_file = $args->getValue('log');
        if ($this->log_file == "") {
            $this->log_file = false;
        }
        $this->quite = $args->getValue('quite');
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
            'memory-limit' => array('short' => 'm',
                     'min' => 0,
                     'max' => 1,
                  'default' => 128,
                    'desc' => 'Set the maximum memory usage.'),
            'debug'        => array('short' => 'd',
                     'max' => 0,
                    'desc' => 'Switch to debug mode.'),
            'include'      => array('min' => 0,
                     'max' => -1,
                    'desc' => 'Include additional test directories'),
            'help'         => array('short' => 'h',
                     'max' => 0,
                    'desc' => 'Print this help message'),
            'php'          => array('min' => 0,
                     'max' => 1,
                 'default' => 'php',
                    'desc' => 'PHP binary path'),
            'quite'        => array('short' => 'q',
                     'max' => 0,
                    'desc' => 'Don\'t produce any output'),
            'log'          => array('min' => 0,
                     'max' => 1,
                  'default' => 'benchlog.txt',
                    'desc' => 'Log file path')
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
        foreach ($this->include as $dir) {
            foreach (glob("$dir/test_*.php") as $filename) {
                $t['filename'] = $filename;
                preg_match('/test_(.+)[.]php$/i', $filename, $matches);
                $t['name'] = $matches[1];
                $files[]   = $t;
            }
        }
        $this->test_files = $files;
    }

    /**
     * Runs the benchmark
     * 
     * @return void
     */
    function run() 
    {
        $timer     = new Timer();
        $totaltime = 0;
        $datetime  = date("Y-m-d H:i:s", time());

        $this->console("--------------- Bench.php ".$datetime."--------------\n");

        foreach ($this->test_files as $test) {
            $cmd = "{$this->php} -d memory_limit={$this->memory_limit}M ".$test['filename'];
            $this->console("$cmd\n", $this->debug);
            $timer->start();
            $debug =  `$cmd`;
            $timer->stop();
            $this->console($debug, $this->debug);
            $this->console("Results from ".$test['name'].": ".$timer->elapsed."\n");
            $totaltime += $timer->elapsed;
        }

        $this->console("Total time for the benchmark: ".$totaltime." seconds\n");

        $datetime = date("Y-m-d H:i:s", time());
        $this->console("-------------- END ".$datetime."---------------------\n");
    }
}

$bench = new Benchmark();
$bench->run();
?>
