<?php

require_once __DIR__.'/job.php';
require_once __DIR__.'/producer.php';

$job = new HelloWorldJob("Christoph");

$producer = new Producer;
$producer->push($job);
