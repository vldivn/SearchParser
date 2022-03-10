<?php

use App\SearchForm;

require __DIR__ . '/vendor/autoload.php';

if (!isset($argv[1])) {
    echo "Please take trademark name and try again." . PHP_EOL .
        "Example php search.php 'abc'." . PHP_EOL;
    exit();
}

$term = $argv[1];

$searchForm = (new SearchForm($term));
$worker = $searchForm->searchWorker();
$results = $worker->results();

print_r($results);

echo 'OBJECTS COUNT: ' . $worker->getResultCount() . PHP_EOL;