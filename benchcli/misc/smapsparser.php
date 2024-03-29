<?php
/**
 * This smaps-parser requires linux with kernel 2.6.14 or newer.
 *
 */
class Smapsparser
{
    var $results;
    var $usage;
    var $peak;
    function parseSmapsData($data)
    {
        $this->usage       = array();
        $lines             = explode("\n",$data);
        $i                 = 0;
        $last              = array();
        $result            = array();
        $result["objects"] = array();
        foreach ($lines as $line) {
            $matches = array();
            if (preg_match('/[a-z0-9]+[-][a-z0-9]+/',$line)) {
                if (preg_match('/[\d]{2}:[\d]{2}[\s][\d]+[\s]+(?<name>.*)/', $line,$matches)) {
                    if(!empty($last))
                    $result["objects"][] = $last;
                    $last["name"]        = $matches["name"];
                }
            } else if (preg_match('/(?<name>[a-zA-Z_]+):/', $line,$match_type)) {
                preg_match('/(?<amount>\d+)[\s]kB/', $line, $match_amount);
                $last[$match_type["name"]] = $match_amount["amount"];
                if (!isset($result[$match_type["name"]])) {
                    $result[$match_type["name"]] = 0;
                }
                $result[$match_type["name"]] += $match_amount["amount"];
            }
        }
        $result["objects"][] = $last;
        $this->usage[]       = $result;
        if (isset($this->peak)) {
            $this->usage[] = $this->peak;
        }
        $this->peak = $this->usage[$this->getPeak()];
        unset($this->usage);
        $this->sortMemUsage($this->peak["objects"]);
    }
    function getUsage()
    {
        return $this->usage;
    }
    /**
     * Compare elements based on Private_Dirty
     *
     */
    static function cmpKey($a,$b)
    {
        if ($a["Private_Dirty"] == $b["Private_Dirty"]) {
            return 0;
        }
        return ($a["Private_Dirty"] < $b["Private_Dirty"]) ? 1 : -1;
    }
    /**
     * Get element index of peak memory usage
     *
     * @return int The index of $this->used's peak
     */
    function getPeak()
    {
        $maxi   = 0;
        $maxval = 0;
        $i      = 0;

        foreach ($this->usage as $mem_array) {
            $val = $mem_array["Private_Dirty"] + $mem_array["Private_Clean"];
            if ($maxval < $val) {
                $maxi   = $i;
                $maxval = $val;
            }
            $i++;
        }
        return $maxi;
    }
    /**
     * Clear the memory usage of the parser
     */
    function clear()
    {
        unset($this->results);
        unset($this->usage);
        unset($this->peak);
    }
    /**
     * Prints the top n memory consuming files based on Dirty Private RSS,
     * for the maximum peak of memory consumption.
     *
     * @param int    $n        The number of files to be printed
     * @param string $resource If specified, it will append to this file instead
     *                         of stdout
     *
     * @return void
     */
    function printMaxUsage($n,$resource = "php://stdout")
    {

        $i = 0;
        if ($resource == "php://stdout") {
            $fh = fopen($resource, "w");
        } else {
            $fh = fopen($resource, "a+");
        }
        fprintf($fh, "RSS private  : %10skB Total\n", $this->peak['Private_Clean'] + $this->peak['Private_Dirty']);
        fprintf($fh, "VM Size      : %10skB\n", $this->peak['Size']);
        fprintf($fh, "             : %10skB Shared total\n", $this->peak['Shared_Dirty'] + $this->peak["Shared_Clean"]);
        fprintf($fh, "               %10skB Private Clean\n", $this->peak['Private_Clean']);
        fprintf($fh, "               %10skB Private Dirty\n", $this->peak['Private_Dirty']);
        fprintf($fh, "%10s %10s %10s %-15s\n", "vm size", "Clean", "Dirty", "File name");
        fprintf($fh, "%'-59s\n", "-");
        foreach ($this->peak["objects"] as $key) {
            $i++;
            if ($i > $n) {
                break;
            }
            // If it's anonymous, don't print it.
            if ($key["name"] != "") {
                fprintf($fh, "%8skB %8skB %8skB %-18s\n", $key['Size'], $key["Private_Clean"], $key["Private_Dirty"], $key["name"]);
            } else {
                $i--;
            }
        }
        fprintf($fh, "%s", "\n");
    }
    /**
     * Prints the summary of the memory usage.
     *
     * @param string $resource If specified, it will append to this file instead
     *                         of stdout
     *
     * @return void
     */
    function printSumUsage($resource = "php://stdout")
    {

        $i = 0;
        if ($resource == "php://stdout") {
            $fh = fopen($resource, "w");
        } else {
            $fh = fopen($resource, "a+");
        }

        fprintf($fh, "%'-59s\n", "-");
        fprintf($fh, "RSS private  : %10sMB Total\n", round(($this->peak['Private_Clean'] + $this->peak['Private_Dirty'])/1024,1));
        fprintf($fh, "VM Size      : %10sMB\n", round(($this->peak['Size']/1024),1));
        fprintf($fh, "             : %10skB Shared total\n", $this->peak['Shared_Dirty'] + $this->peak["Shared_Clean"]);
        fprintf($fh, "               %10skB Private Clean\n", $this->peak['Private_Clean']);
        fprintf($fh, "               %10skB Private Dirty\n", $this->peak['Private_Dirty']);
        fprintf($fh, "%'-59s\n", "-");
        fprintf($fh, "%s", "\n");
    }
    /**
     * Sorts the memory usage from the array
     *
     * @param &array &$array The parsed smaps results to be sorted
     *
     * @return void
     */
    function sortMemUsage(&$array)
    {
        usort($array, "Smapsparser::cmpKey");
    }
    /**
     * Reads the Smaps data of the given PID
     *
     * @param int $pid The PID to read Smaps data from
     *
     * @return File contents in form of string or if file not exists
     * 	       false is returned.
     */
    function readSmapsData($pid)
    {
        if (file_exists("/proc/$pid/smaps")) {
            return file_get_contents("/proc/$pid/smaps");
        }
        return false;
    }

}
?>