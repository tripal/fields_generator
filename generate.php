<?php

namespace FieldGenerator;

require __DIR__.'/src/bootstrap.php';

use FieldGenerator\Src\Generator;
use Exception;

$app = new Generator();
$prompt = $app->prompt();

try {
    $path = $app->run();
} catch (Exception $exception) {
    $prompt->error($exception->getMessage());
    exit(1);
}

$prompt->success('Field generated successfully.');
$prompt->success("The field can be found at {$path['field']}");
