<?php
$size = 300000;

// create array

$testArray = array();

for ($i=0; $i<$size; $i++) {
	
	$testArray[$i] = rand(0,9);
}

$testObject = new ArrayObject($testArray);
$iterator = $testObject->getIterator();

while ($iterator->valid()) {
	
	if ($iterator->key()%2 == 0) {
			
		$testObject[$iterator->current()] = 'G';
	}

	$iterator->next();
}

?>
