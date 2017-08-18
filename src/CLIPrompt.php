<?php

namespace FieldGenerator\Src;

class CLIPrompt
{
    protected $file_descriptor;

    protected $colored;

    public function __construct()
    {
        $this->file_descriptor = fopen('php://stdin', 'r');
        $this->colored = new Colors();
    }

    public function ask($question)
    {
        $response = readline($question);

        return $response;
    }

    public function line($message)
    {
        echo $message.PHP_EOL;
    }

    public function info($message)
    {
        echo $this->colored->str($message, 'black', 'blue').PHP_EOL;
    }

    public function error($message)
    {
        echo $this->colored->str($message, 'black', 'red').PHP_EOL;
    }

    public function warn($message)
    {
        echo $this->colored->str($message, 'black', 'yellow').PHP_EOL;
    }

    public function success($message)
    {
        echo $this->colored->str($message, 'black', 'green').PHP_EOL;
    }

    public function __destruct()
    {
        fclose($this->file_descriptor);
    }
}