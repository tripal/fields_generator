<?php

namespace StatonLab\FieldGenerator;

/**
 * Class CLIPrompt.
 * Creates colored output
 *
 * @package FieldGenerator\Src
 */
class CLIPrompt
{
    /**
     * Input File Resource.
     *
     * @var Resource
     */
    protected $input;

    /**
     * Holds the prompt colors generator.
     *
     * @var \StatonLab\FieldGenerator\Colors
     */
    protected $colored;

    /**
     * CLIPrompt constructor.
     *
     * @param $file string [optional] Input file path.
     *
     * @return void
     */
    public function __construct($file = 'php://stdin')
    {
        $this->input = fopen($file, 'r');
        $this->colored = new Colors();
    }

    /**
     * Prompts the user for an answer to the provided question.
     *
     * @param $question string Question to prompt the user.
     * @return string
     */
    public function ask($question)
    {
        while (empty($response = readline($question))) {
           $this->error('Please provide a response');
        }

        return $response;
    }

    /**
     * Prints a line to the file.
     *
     * @param $message
     */
    public function line($message)
    {
        echo $message.PHP_EOL;
    }

    /**
     * Prints a line with a blue background.
     *
     * @param $message
     */
    public function info($message)
    {
        echo $this->colored->str($message, 'black', 'blue').PHP_EOL;
    }

    /**
     * Prints a line with a red background.
     *
     * @param $message
     */
    public function error($message)
    {
        echo $this->colored->str($message, 'black', 'red').PHP_EOL;
    }

    /**
     * Prints a line with yellow background.
     *
     * @param $message
     */
    public function warn($message)
    {
        echo $this->colored->str($message, 'black', 'yellow').PHP_EOL;
    }

    /**
     * Prints a line with a green background.
     *
     * @param $message
     */
    public function success($message)
    {
        echo $this->colored->str($message, 'black', 'green').PHP_EOL;
    }

    /**
     * Close the file before exiting.
     */
    public function __destruct()
    {
        fclose($this->input);
    }
}