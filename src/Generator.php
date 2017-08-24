<?php

namespace StatonLab\FieldGenerator;

use Exception;

class Generator
{
    /**
     * Field human-readable label.
     *
     * @var string
     */
    protected $field_label;

    /**
     * Field machine name.
     *
     * @var string
     */
    protected $field_name;

    /**
     * Description.
     *
     * @var string
     */
    protected $field_description;

    /**
     * The module this field will be part of.
     *
     * @var string
     */
    protected $module_name;

    /**
     * Controlled vocabulary term such as germplasm_summary.
     *
     * @var string
     */
    protected $cv_term;

    /**
     * Controlled vocabulary name in the chado.CV table.
     * case EDAM:  EDAM.
     * case general multilevel: subontology.
     * Case SO: sequence
     *
     * @var
     */
    protected $cv_name;

    /**
     * Controlled vocabulary name in the chado.DB table.
     * edge case EDAM: this is the subontology.
     * case SO: this should be SO.
     *
     * @var
     */
    protected $db_name;

    /**
     * Accession for the cv term such as 873210 or germplasm_summary.
     * Verify that this term is available in the `chado`.`dxref`.
     *
     * @var string
     */
    protected $field_accession;

    /**
     * Prompt questions.
     * Each key should point to a property to save the answer to.
     *
     * @usage Example:
     *          'Field Description: ' => 'field_description'
     *        where field_description is a protected property of this class
     *        and accessible as $this->field_description.
     *
     * @var array
     */
    protected $questions;

    /**
     * Holds the prompt output/input handler.
     *
     * @var \StatonLab\FieldGenerator\CLIPrompt
     */
    protected $prompt;

    /**
     * CLI Options mapped long form => shorthand form.
     * Read http://php.net/manual/en/function.getopt.php for more
     * information about the optional vs required.
     *
     * @var array
     */
    protected $mapped_options = [
        'type:' => 't:',
        'output:' => 'o:',
    ];

    /**
     * CLI Options Parser.
     *
     * @var \StatonLab\FieldGenerator\OptionsParser
     */
    protected $options;

    /**
     * Generator constructor.
     * Set all questions here.
     *
     * @return void
     */
    public function __construct()
    {
        $this->questions = [
            'Field Label (A human readable label for the field. e,g. Germplasm Summary): ' => 'field_label',
            'Field Description (A human readable description of the field): ' => 'field_description',
            'Module Name (The machine name of the module this field is distributed with. e,g. tripal_germplasm_module): ' => 'module_name',
            'Database name. For simple ontologies, this will be the CV name.  When tripal inserts your term, it will be in the form [Database name]:Accession. ' => 'db_name',
            'Controlled Vocabulary. For simple ontologies, the same as the DB name.  ' => 'cv_name',
            'Controlled Vocabulary Term (e,g. germplasm_summary): ' => 'cv_term',
            'Accession (The accession number for this term in the vocabulary, e,g. 30021. Accessions are integers for biological CVs, strings for semantic.): ' => 'field_accession',
        ];
        $this->prompt = new CLIPrompt();
        $this->options = new OptionsParser($this->mapped_options);
        $this->validateOptions();
    }

    /**
     * Prompt the user and save answers then generate the files.
     *
     * @return array
     */
    public function run()
    {
        $this->printIntro();

        foreach ($this->questions as $question => $field) {
            $this->{$field} = $this->prompt->ask($question);
        }

        // Auto construct field name
        $lower = strtolower($this->db_name);

        $this->field_name = "{$lower}__{$this->cv_term}";
        $this->questions[$this->field_name] = 'field_name';

        $files = $this->generate();

        try {
            return $this->make($files);
        } catch (Exception $exception) {
            throw $exception;
        }
    }

    /**
     * Validate options.
     *
     * @throws \Exception
     */
    protected function validateOptions()
    {
        if ($this->options->type && ! in_array(strtolower($this->options->type), ['tripal', 'chado'])) {
            throw new Exception('Please specify a valid field type. You may choose between chado or tripal. For example, makefield --type=chado.');
        }
    }

    /**
     * Prints introduction message.
     */
    protected function printIntro()
    {
        $this->prompt->line('This helper will automate field assembly based on the controlled vocabulary (CV) and controlled vocabulary term (CVterm).');
        $this->prompt->line('Ideally, every field should map to a controlled vocabulary (ontology) term.  If no term exists from a fitting CV, you can use the CV "local".');
        $this->prompt->line('Additional help is available in the README, and at http://tripal.info/tutorials/v3.x/developers_handbook.');
        $this->prompt->line('In particular, check the documentation for how each CV is mapped in the CV and DB table.  Future versions of this tool will handle this automatically.');
        $this->prompt->line('');
        $this->prompt->line('***************************');
        $this->prompt->line('***************************');
        $this->prompt->line('');
        $this->prompt->line('Please fill the following form to generate a Tripal Field.');
        $this->prompt->line('');
    }

    /**
     * Generate the field files content by replacing the available variables.
     * This function does not create the directories and files.
     *
     * @return array
     */
    protected function generate()
    {
        list($fields_stub, $class_stub, $formatter_stub, $widget_stub) = $this->getFilesContent();

        // Find and replace variables in stubs.
        // The structure of variables are $$name_of_var$$ and they correspond
        // to saved class properties.
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

    /**
     * Get the correct stub files according to the type option.
     *
     * @return array
     */
    protected function getFilesContent()
    {
        $type = $this->options->type ? strtolower($this->options->type) : 'chado';

        $fields_stub = file_get_contents(__DIR__."/../stubs/{$type}_fields");
        $class_stub = file_get_contents(__DIR__."/../stubs/{$type}_field_class");
        $formatter_stub = file_get_contents(__DIR__."/../stubs/{$type}_field_formatter");
        $widget_stub = file_get_contents(__DIR__."/../stubs/{$type}_field_widget");

        return [$fields_stub, $class_stub, $formatter_stub, $widget_stub];
    }

    /**
     * Make each field file.
     *
     * @param $files
     * @return array
     */
    protected function make($files)
    {
        $path = $this->getFieldsDir();

        // Field settings
        $fields_name = $this->module_name;
        if ($this->options->output) {
            if (file_exists("{$path['includes']}/{$fields_name}.fields.inc")) {
                $fields_name .= '.stub';
            }
        }
        file_put_contents("{$path['includes']}/{$fields_name}.fields.inc", $files['fields']);

        // Create the class files
        file_put_contents("{$path['field']}/{$this->field_name}.inc", $files['class']);
        file_put_contents("{$path['field']}/{$this->field_name}_widget.inc", $files['widget']);
        file_put_contents("{$path['field']}/{$this->field_name}_formatter.inc", $files['formatter']);

        return $path;
    }

    /**
     * Get the path to output files after creating any missing folders.
     *
     * @return array
     */
    protected function getFieldsDir()
    {
        if ($this->options->output) {
            return $this->createFromOutputPath();
        }

        return $this->createFromWorkingDir();
    }

    /**
     * Create output directory structure starting from working directory.
     *
     * @return array
     * @throws \Exception
     */
    protected function createFromWorkingDir()
    {
        $current = getcwd();
        $includes = "{$current}/{$this->field_name}_output";
        if (! mkdir($includes)) {
            throw new Exception("Could not create directory $includes Please check path permissions or remove the directory if it exists.");
        }

        $path = "$includes/{$this->field_name}";
        if (! mkdir($path)) {
            throw new Exception("Could not create directory $path Please check path permissions or remove the directory if it exists.");
        }

        return [
            'includes' => $includes,
            'field' => $path,
        ];
    }

    /**
     * Create output directory structure according to output option.
     *
     * @return array
     * @throws \Exception
     */
    protected function createFromOutputPath()
    {
        // Create the includes dir if does not exist
        $includes = "{$this->options->output}/includes";
        if (! file_exists($includes)) {
            if (! mkdir($includes)) {
                throw new Exception("Could not create directory $includes Please check path permissions.");
            }
        }

        // Create the TripalFields dir if does not exist
        $path = "$includes/TripalFields";
        if (! file_exists($path)) {
            if (! mkdir($path)) {
                throw new Exception("Could not create directory $path Please check path permissions.");
            }
        }

        // Create the field dir
        $path .= "/{$this->field_name}";
        if (! mkdir($path)) {
            throw new Exception("Could not create directory $path Please check path permissions.");
        }

        return [
            'includes' => $includes,
            'field' => $path,
        ];
    }

    /**
     * Give public access to the prompt.
     *
     * @return \StatonLab\FieldGenerator\CLIPrompt
     */
    public function prompt()
    {
        return $this->prompt;
    }
}
