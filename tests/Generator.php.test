<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once(__DIR__ . '/../src/bootstrap.php');

final class GeneratorEngineTest extends TestCase {

	private $generator;

protected function setUp() {
   
$this->generator = new StatonLab\FieldGenerator\Generator();
}
 protected function tearDown()
    {
        $this->generator = NULL;
    }

/**
*Assert that the Generator constructs
*
**/
    public function testAdd()
    {
        $generator = $this->generator;

        $this->assertInstanceOf("StatonLab\FieldGenerator\Generator", $generator);

    }

}