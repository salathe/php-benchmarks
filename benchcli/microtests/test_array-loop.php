<?php
$size = 20000;

// create array
$testArray = array();

for ($i=0; $i<$size; $i++)
{	
	$testArray[$i] = rand(0,9);
}

// loop
for ($i=0; $i<$size; $i++)
{	
	if (isset($testArray[$i]))
    {
		if ($i%2 == 0)
        {
			$testArray[$i] = 'G';
		}
	}
}