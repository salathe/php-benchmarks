<?php
class Cachegrindparser
{

    /**
     * Array of the results that have been parsed
     * @var array
     */
    var $results;

    /**
     * Parses the returning string from a cachegrind call.
     * The results are then set into $this->results.
     *
     * @param string $text The text to parse
     *
     * @return void
     */
    function parse($text)
    {
        $match_names = array (    "instruction"
        ,    "instruction_l1_miss"
        ,    "instruction_l2_miss"

        ,	  "data"
        ,	  "data_read"
        ,	  "data_write"

        ,	  "data_l1_miss"
        ,	  "data_l1_miss_read"
        ,    "data_l1_miss_write"

        ,    "data_l2_miss"
        ,    "data_l2_miss_read"
        ,    "data_l2_miss_write"
        
        ,    "l2"
        ,    "l2_read"
        ,    "l2_write"
                    
        ,    "l2_miss"
        ,    "l2_miss_read"
        ,    "l2_miss_write"
                    
        ,    "branch"
        ,    "branch_conditional"
        ,    "branch_indirect"
                    
        ,    "branch_misprediction"
        ,    "branch_conditional_misprediction"
        ,    "branch_indirect_misprediction");

        $start     = "^==\d+==";
        $middle    = ":\s+";
        $number    = "([0-9,]+)";
        $rw        = "\s*\(\s*$number rd\s*\+\s*$number wr\)";
        $branch_rw = "\s*\(\s*$number cond\s*\+\s*$number ind\)";
        $end       = "$.";
        preg_match("/"
        ."$start I\s+refs$middle$number$end"
        ."$start I1\s+misses$middle$number$end"
        ."$start L2i\s+misses$middle$number$end"
        .".*"
        ."$start D\s+refs$middle$number$rw$end"
        ."$start D1\s+misses$middle$number$rw$end"
        ."$start L2d\s+misses$middle$number$rw$end"
        .".*"
        ."$start L2\s+refs$middle$number$rw$end"
        ."$start L2\s+misses$middle$number$rw$end"
        .".*"
        ."$start Branches$middle$number$branch_rw$end"
        ."$start Mispredicts$middle$number$branch_rw$end"
        ."/ms", $text, $matches);

        array_shift($matches); // strip off the

        // remove commas
        foreach ($matches as &$match) {
            $match = preg_replace("/,/", "", $match);
        }

        unset($match); // remove the reference from match
        // make key-value pairs
        $results = array_combine($match_names, $matches);

        $this->results = $results;
    }

    /**
     * Prints result from the cachegrind output.
     *
     * @param string $resource If specified, it will append to this file instead
     *                         of stdout
     *
     * @return void
     */
    function printResults($resource = "php://stdout")
    {
        $i = 0;
        if ($resource == "php://stdout") {
            $fh = fopen($resource, "w");
        } else {
            $fh = fopen($resource, "a+");
        }
        
        fprintf($fh, "%'-59s\n", "-");
        fprintf($fh, "Instructions  			: %10s\n", $this->results['instruction']);
        fprintf($fh, "L1 misses     			: %10s\n", $this->results['instruction_l1_miss']);
        fprintf($fh, "L2 misses     			: %10s\n", $this->results['instruction_l2_miss']);
        
        fprintf($fh, "\nData        			: %10s\n", $this->results['data']);
        fprintf($fh, "Data read     			: %10s\n", $this->results['data_read']);
        fprintf($fh, "Data write    			: %10s\n", $this->results['data_write']);
        fprintf($fh, "Data L1 misses     		: %10s\n", $this->results['data_l1_miss']);
        fprintf($fh, "Data L1 write misses		: %10s\n", $this->results['data_l1_miss_write']);
        fprintf($fh, "Data L1 read misses		: %10s\n", $this->results['data_l1_miss_read']);
        fprintf($fh, "Data L2 misses     		: %10s\n", $this->results['data_l2_miss']);
        fprintf($fh, "Data L2 write misses		: %10s\n", $this->results['data_l2_miss_write']);
        fprintf($fh, "Data L2 read misses		: %10s\n", $this->results['data_l2_miss_read']);
        
        fprintf($fh, "\nL2				: %10s\n", $this->results['l2']);
        fprintf($fh, "L2 writes	         	: %10s\n", $this->results['l2_write']);
        fprintf($fh, "L2 reads	         	: %10s\n", $this->results['l2_read']);
        fprintf($fh, "L2 misses	         	: %10s\n", $this->results['l2_miss']);
        fprintf($fh, "L2 write misses			: %10s\n", $this->results['l2_miss_write']);
        fprintf($fh, "L2 read misses			: %10s\n", $this->results['l2_miss_read']);

        fprintf($fh, "\nBranches			: %10s\n", $this->results['branch']);
        fprintf($fh, "Conditional        		: %10s\n", $this->results['branch_conditional']);
        fprintf($fh, "Indirect	       	 	: %10s\n", $this->results['branch_indirect']);
        
        fprintf($fh, "\nBranch mispredictions		: %10s\n", $this->results['branch_misprediction']);
        fprintf($fh, "Conditional mispredictions	: %10s\n", $this->results['branch_conditional_misprediction']);
        fprintf($fh, "Indirect mispredictions		: %10s\n", $this->results['branch_indirect_misprediction']);
                
        fprintf($fh, "%'-59s\n", "-");
        fprintf($fh, "%s", "\n");
    }
}
?>
