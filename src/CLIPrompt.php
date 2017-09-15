<?php

namespace StatonLab\FieldGenerator;

/**
 * Class CLIPrompt.
 * Creates colored output
 *
 * @package FieldGenerator\Src
 */
class CLIPrompt {

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
   * @param string $file [optional] Input file path.
   *
   * @return void
   */
  public function __construct($file = 'php://stdin') {
    $this->input = fopen($file, 'r');
    $this->colored = new Colors();
  }

  /**
   * Prompts the user for an answer to the provided question.
   *
   * @param string $question Question to prompt the user.
   *
   * @return string
   */
  public function ask($question, $style = NULL) {
    if ($style) {
      $question = $this->applyStyle($question, $style);
    }

    echo $question;
    while (empty($response = readline())) {
      $this->error('Please provide a response');
      echo $question;
    }

    return $response;
  }

  protected function applyStyle($str, $style) {
    switch ($style) {
      case 'warning':
        $str = $this->colored->str($str, 'yellow');
        break;
      case 'danger':
      case 'error':
        $str = $this->colored->str($str, 'red');
        break;
      case 'info':
        $str = $this->colored->str($str, 'blue');
        break;
    }

    return $str;
  }

  /**
   * Ask a yes or no question.
   *
   * @param string $question the question.
   *
   * @return bool
   */
  public function askBool($question, $style = NULL) {
    $answer = strtolower($this->ask($question . ' [Y/n]:', $style));
    while (!in_array($answer, ['y', 'n', 'yes', 'no'])) {
      $this->error('Please answer with "yes", "no", "y" or "n"');
    }

    if (substr($answer, 0, 1) === 'y') {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Ask multiple choice questions.
   *
   * @param string $question The question.
   * @param array $options A list of options.
   *
   * @return int The index of the selected option.
   */
  public function askMultipleChoice($question, $options, $style = NULL) {
    if ($style) {
      $question = $this->applyStyle($question, $style);
    }

    $this->line($question);
    $len = count($options);

    for ($i = 1; $i <= $len; $i++) {
      $this->line("[$i] {$options[$i - 1]}");
    }

    do {
      $answer = $this->ask('Please enter an option number: ');
    } while ($answer < 1 || $answer > $len);

    return intval($answer) - 1;
  }

  /**
   * Prints a line to the file.
   *
   * @param $message
   */
  public function line($message) {
    echo $message . PHP_EOL;
  }

  /**
   * Prints a line with a blue background.
   *
   * @param string $message
   */
  public function info($message) {
    echo $this->colored->str($message, 'blue') . PHP_EOL;
  }

  /**
   * Prints a line with a red background.
   *
   * @param string $message
   */
  public function error($message) {
    echo $this->colored->str($message, 'red') . PHP_EOL;
  }

  /**
   * Prints a line with yellow background.
   *
   * @param string $message
   */
  public function warn($message) {
    echo $this->colored->str($message, 'yellow') . PHP_EOL;
  }

  /**
   * Prints a line with a green background.
   *
   * @param string $message
   */
  public function success($message) {
    echo $this->colored->str($message, 'black', 'green') . PHP_EOL;
  }

  /**
   * Close the file before exiting.
   */
  public function __destruct() {
    fclose($this->input);
  }
}