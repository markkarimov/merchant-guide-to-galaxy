<?php

require __DIR__ . '/vendor/autoload.php';

use Acme\{FileReader, InputParser};

$file = new FileReader("input.txt");
(new InputParser($file))->process();