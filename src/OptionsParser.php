<?php

namespace StatonLab\FieldGenerator;

class OptionsParser {
  /**
   * Mapped long option => shorthand option.
   *
   * @var array
   */
  protected $mapped_options = [];

  /**
   * Long options.
   *
   * @var array
   */
  protected $options = [];

  /**
   * Shorthand options.
   *
   * @var array
   */
  protected $shorthand = [];

  /**
   * Values array. Magically accessible as class properties.
   * Any item in this array will be accessible as $instance->key using __call().
   *
   * @var array
   */
  protected $values = [];

  /**
   * OptionsParser constructor.
   *
   * @param array $mapped_options Example: ['long_option' => 'shorthand]
   */
  public function __construct($mapped_options) {
    $this->mapped_options = $mapped_options;

    foreach ($mapped_options as $key => $option) {
      $this->options[] = $key;
      $this->shorthand[] = $option;
    }

    $this->parse();
  }

  /**
   * Magically get values.
   *
   * @param $name
   *
   * @return string|boolean|null
   */
  public function __get($name) {
    if (isset($this->values[$name])) {
      return $this->values[$name];
    }

    return NULL;
  }

  /**
   * Parse long and short options and make values accessible as properties.
   * Also set default values to false in case the option was not provided.
   */
  protected function parse() {
    $short_options = implode('', $this->shorthand);
    $this->values = getopt($short_options, $this->options);

    // Parse long options
    foreach ($this->options as $option) {
      // Remove trailing colons
      $this->removeTrailingColons($option);

      if (!isset($this->values[$option])) {
        $this->values[$option] = FALSE;
      }
    }

    // Parse shorthand options and convert to long form.
    foreach ($this->shorthand as $option) {
      $long = array_search($option, $this->mapped_options);

      // Remove trailing colons
      $this->removeTrailingColons($option);
      $this->removeTrailingColons($long);

      // Convert short options to long form
      $this->convertShortToLongValue($option, $long);
    }
  }

  /**
   * Convert short option values to long and make them accessible.
   * This would unify the api so the user doesn't have to check whether
   * short is or long options were used.
   *
   * @param string $option Shorthand option.
   * @param string $long Long format option.
   */
  protected function convertShortToLongValue($option, $long) {
    if (!isset($this->values[$option]) && !isset($this->values[$long])) {
      $this->values[$long] = FALSE;

      return;
    }

    if (isset($this->values[$option])) {
      $this->values[$long] = $this->values[$option];

      return;
    }

    if (isset($this->values[$long])) {
      $this->values[$option] = $this->values[$long];
    }
  }

  /**
   * Remove trailing colons from the end of the option.
   *
   * @param string $option
   */
  protected function removeTrailingColons(&$option) {
    while (substr($option, -1) === ':') {
      $option = trim($option, ':');
    }
  }
}