<?php

namespace FieldGenerator\Src;

class Generator
{
    protected $field_label;

    protected $field_name;

    protected $field_description;

    protected $module_name;

    protected $cv_term;

    protected $cv_name;

    protected $field_accession;

    protected $questions = [];

    protected $prompt;

    public function __construct()
    {
        $this->questions = [
            'Field Label (E,g. Germplasm Summary): ' => 'field_label',
            'Field Machine Name (E,g. local__germplasm_summary): ' => 'field_name',
            'Field Description: ' => 'field_description',
            'Module Name (E,g. tripal_germplasm_module): ' => 'module_name',
            'Controlled Vocabulary Name (E,g. local): ' => 'cv_name',
            'Controlled Vocabulary Term (E,g. germplasm_summary): ' => 'cv_term',
            'Accession (E,g. 30021 or germplasm): ' => 'field_accession',
        ];

        $this->prompt = new CLIPrompt();
    }

    public function run()
    {
        $this->prompt->line('Please fill the following form to generate a Tripal Field.');

        foreach ($this->questions as $question => $field) {
            $this->{$field} = $this->prompt->ask($question);
        }

        $files = $this->generate();

        return $this->make($files);
    }

    protected function generate()
    {
        $fields_stub = file_get_contents(__DIR__.'/../stubs/fields');
        $class_stub = file_get_contents(__DIR__.'/../stubs/field_class');
        $formatter_stub = file_get_contents(__DIR__.'/../stubs/field_formatter');
        $widget_stub = file_get_contents(__DIR__.'/../stubs/field_widget');

        foreach ($this->questions as $question => $field) {
            $fields_stub = str_replace('$$'.$field.'$$', $this->{$field}, $fields_stub);
            $class_stub = str_replace('$$'.$field.'$$', $this->{$field}, $class_stub);
            $formatter_stub = str_replace('$$'.$field.'$$', $this->{$field}, $formatter_stub);
            $widget_stub = str_replace('$$'.$field.'$$', $this->{$field}, $widget_stub);
        }

        return [
            'fields' => $fields_stub,
            'class' => $class_stub,
            'formatter' => $formatter_stub,
            'widget' => $widget_stub,
        ];
    }

    protected function make($files)
    {
        // Make output directory
        $current = getcwd();
        $path = "{$current}/{$this->field_name}_output";

        if (! mkdir($path)) {
            $this->prompt->error('Could not create directory '.$path.'. Please check path permissions.');

            return false;
        }

        // Field settings
        file_put_contents("$path/{$this->module_name}.fields.inc", $files['fields']);

        // Create the field dir
        $path = "{$path}/{$this->field_name}";
        if (! mkdir($path)) {
            $this->prompt->error('Could not create directory '.$path.'. Please check path permissions.');

            return false;
        }

        // Create the class files
        file_put_contents("$path/{$this->field_name}.inc", $files['class']);
        file_put_contents("$path/{$this->field_name}_widget.inc", $files['widget']);
        file_put_contents("$path/{$this->field_name}_formatter.inc", $files['formatter']);

        return true;
    }
}
