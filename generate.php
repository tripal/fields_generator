<?php

namespace FieldGenerator;

require __DIR__.'/src/bootstrap.php';

use FieldGenerator\Src\Generator;
$app = new Generator();
if($app->run()) {
    $app->prompt()->success('Fields generated successfully.');
}
