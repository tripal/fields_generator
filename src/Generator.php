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
        'drupal:' => 'd:',
    ];

    /**
     * CLI Options Parser.
     *
     * @var \StatonLab\FieldGenerator\OptionsParser
     */
    protected $options;

    /**
     * Database connection.
     *
     * @var null|\StatonLab\FieldGenerator\DB
     */
    protected $db = null;

    /**
     * Drupal root directory.
     *
     * @var PathFinder
     */
    protected $pathFinder;

    /**
     * Generator constructor.
     * Set all questions here.
     *
     * @throws \Exception
     * @return void
     */
    public function __construct()
    {
        $this->questions = [
            'Field Label (A human readable label for the field. e,g. Germplasm Summary): ' => 'field_label',
            'Field Description (A human readable description of the field): ' => 'field_description',
            'Module Name (e,g. tripal_germplasm_module).  If distributed via libraries, use tripal_chado. : ' => 'module_name',
            'Database name. For simple ontologies, this will be the CV name. When tripal inserts your term, it will be in the form "database_name:accession": ' => 'db_name',
            'Controlled Vocabulary. For simple ontologies, the same as the DB name:  ' => 'cv_name',
            'Controlled Vocabulary Term (e,g. germplasm_summary): ' => 'cv_term',
            'Accession (The accession number for this term in the vocabulary, e,g. 30021. Accessions are integers for biological CVs, strings for semantic): ' => 'field_accession',
        ];
        $this->prompt = new CLIPrompt();
        $this->options = new OptionsParser($this->mapped_options);
        $this->validateOptions();
        $this->pathFinder = new PathFinder($this->options->drupal);
        if($this->pathFinder->getRoot()) {
            $this->db = new DB($this->pathFinder->getRoot());
        } else {
            $this->db = new DB($this->options->drupal);
        }
    }

    /**
     * Prompt the user and save answers then generate the files.
     *
     * @throws \Exception
     * @return array
     */
    public function run()
    {
        $this->printIntro();

        foreach ($this->questions as $question => $field) {
            $this->{$field} = trim($this->prompt->ask($question));
        }

        // Auto construct field name
        $lower = strtolower($this->db_name);

        $this->field_name = "{$lower}__{$this->cv_term}";
        $this->questions[$this->field_name] = 'field_name';

        $files = $this->generate();

        $this->validateTerms();

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
        if ($this->options->type && !in_array(
                strtolower($this->options->type),
                [
                    'tripal',
                    'chado',
                ]
            )) {
            throw new Exception(
                'Please specify a valid field type. You may choose between chado or tripal. For example, makefield --type=chado.'
            );
        }
    }

    /**
     * Validate provided terms in DB.
     *
     * @throws \Exception
     */
    protected function validateTerms()
    {
        $this->prompt->info('Performing DB checks to validate entries ...');
        $failed = false;

        //if no connection, run anyway but let the user know
        if ($this->db->checkOffline()) {
            $this->prompt->info('No valid DB connection. Continuing without querying terms against the DB.');
            $this->prompt->info("Chosen CV Term ID {$this->db_name}:{$this->field_accession} and CV {$this->cv_name}");

            return;
        }

        // Validate DB
        $count = $this->count('chado.db', 'name', $this->db_name);
        if ($count <= 0) {
            $failed = true;
            $answer = $this->prompt->askBool(
                "The DB \"{$this->db_name}\" does not exist in the chado.db table. Using this value will create a new DB. Are you sure?"
            );
            if (!$answer) {
                $this->terminate();
            }
        }

        // Validate CV
        $count = $this->count('chado.cv', 'name', $this->cv_name);
        if ($count <= 0) {
            $failed = true;
            $answer = $this->prompt->askBool(
                "The CV \"{$this->cv_name}\" does not exist in the chado.cv table. Using this value will create a new CV. Are you sure?"
            );
            if (!$answer) {
                $this->terminate();
            }
        }

        // Validate CV Term
        $count = $this->count('chado.cvterm', 'name', $this->cv_term);
        if ($count <= 0) {
            $failed = true;
            $answer = $this->prompt->askBool(
                "The CV term \"{$this->cv_term}\" does not exist in the chado.cvterm table. Using this value will create a new CV. Are you sure?"
            );
            if (!$answer) {
                $this->terminate();
            }
        }

        if (!$failed) {
            $results = $this->db->query(
                'SELECT CV.name AS cv_name, DB.name AS db_name, DBX.accession AS accession
                        FROM chado.cvterm AS CVTERM
                        JOIN  chado.cv AS CV ON CVTERM.cv_id  = CV.cv_id
                        JOIN  chado.dbxref AS DBX ON CVTERM.dbxref_id = DBX.dbxref_id
                        JOIN  chado.db AS DB ON DBX.db_id = DB.db_id
                        WHERE CVTERM.name = :cv_term',
                [':cv_term' => $this->cv_term]
            )->get();
            $count = count($results);
            switch ($count) {
                case 0:
                    $this->prompt->askBool(
                        'Warning: the CV, DB, and CVterm are not properly linked through the chado.dbxref table. If this term was manually inserted into the db, remove it before adding the new term.',
                        'warning'
                    );
                    break;
                case 1:
                    $this->verifyAccession($results[0]);
                    break;
                default:
                    $this->handleMultiDBXRef($results);
                    break;
            }
        }

        $this->prompt->info("Chosen CV Term ID {$this->db_name}:{$this->field_accession} and CV {$this->cv_name}");
        $this->prompt->info('DB checks succeeded');
    }

    /**
     * Verify that the entered accession is equivalent to the one in the DB.
     *
     * @param array $result
     */
    protected function verifyAccession($result)
    {
        if (!$this->checkAccessionInDB($result)) {
            $answer = $this->prompt->askBool(
                "The accession in chado is {$result['accession']}, which does not match the provided the accession ({$this->field_accession}). Would you like to use {$result['accession']} instead?",
                'warning'
            );
            if ($answer) {
                $this->field_accession = $result['accession'];
            }
        }
    }

    /**
     * Handle multiple results from DB.
     *
     * @param array $results 2d array of db results.
     */
    protected function handleMultiDBXRef($results)
    {
        // Create options array.
        $options = [];
        $len = count($results);
        foreach ($results as $result) {
            $options[] = "ID {$result['db_name']}:{$results['accesion']} and controlled vocabulary {$results['cv_name']}";
        }
        $options[] = "None of the above. I'd like to keep my settings.";

        $index = $this->prompt->askMultipleChoice(
            'Multiple links were found to the same CV term. Please select the most accurate CV term from the list below.',
            $options,
            'warning'
        );

        if ($index === $len) {
            return;
        }

        $selected = $results[$index];

        $this->field_accession = $selected['accession'];
        $this->db_name = $selected['db_name'];
        $this->cv_name = $selected['cv_name'];
    }

    /**
     * Checks the accession validity against the DB result.
     *
     * @param array $result A single DB result.
     *
     * @return bool
     */
    protected function checkAccessionInDB($result)
    {
        // Wrapped in quotes to make sure both are evaluated as strings
        return "$this->field_accession" === trim("{$result['accession']}");
    }

    /**
     * Get the total count to a query.
     *
     * @param $table
     * @param $condition_column
     * @param $condition_value
     *
     * @return mixed
     */
    protected function count($table, $condition_column, $condition_value)
    {
        $sql = "SELECT COUNT(*) AS count FROM $table AS DB WHERE DB.{$condition_column} = :condition_column";

        return $this->db->query($sql, [':condition_column' => $condition_value])
            ->count();
    }

    /**
     * Terminate the generator.
     *
     * @throws \Exception
     */
    protected function terminate()
    {
        throw new Exception('User Terminated.');
    }

    /**
     * Prints introduction message.
     */
    protected function printIntro()
    {
        $this->prompt->line(
            'This helper will automate field assembly based on the controlled vocabulary (CV) and controlled vocabulary term (CVterm).'
        );
        $this->prompt->line(
            'Ideally, every field should map to a controlled vocabulary (ontology) term.  If no term exists from a fitting CV, you can use the CV "local".'
        );
        $this->prompt->line(
            'Additional help is available in the README, and at http://tripal.info/tutorials/v3.x/developers_handbook.'
        );
        $this->prompt->line(
            'In particular, check the documentation for how each CV is mapped in the CV and DB table.  Future versions of this tool will handle this automatically.'
        );
        $this->prompt->line('');
        $this->prompt->line('***************************');
        $this->prompt->line('***************************');
        $this->prompt->line('');
        $this->prompt->line('Please fill the following form to generate a Tripal Field.');
        $this->prompt->line('');

        if ($this->pathFinder->getRoot() === false) {
            $this->prompt->error(
                'Could not find drupal root. Database checks have been disabled. Please move into your Drupal root directory to enable DB checks, which provide better error checking for your CV terms (press ctrl + c to exit)'
            );
        }
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
     *
     * @throws \Exception
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
     * @throws \Exception
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
     * @throws \Exception
     * @return array
     */
    protected function createFromWorkingDir()
    {
        $current = getcwd();
        $includes = "{$current}/{$this->field_name}_output";
        if (!mkdir($includes)) {
            throw new Exception(
                "Could not create directory $includes Please check path permissions or remove the directory if it exists."
            );
        }

        $path = "$includes/{$this->field_name}";
        if (!mkdir($path)) {
            throw new Exception(
                "Could not create directory $path Please check path permissions or remove the directory if it exists."
            );
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
        if (!file_exists($includes)) {
            if (!mkdir($includes)) {
                throw new Exception("Could not create directory $includes Please check path permissions.");
            }
        }

        // Create the TripalFields dir if does not exist
        $path = "$includes/TripalFields";
        if (!file_exists($path)) {
            if (!mkdir($path)) {
                throw new Exception("Could not create directory $path Please check path permissions.");
            }
        }

        // Create the field dir
        $path .= "/{$this->field_name}";
        if (!mkdir($path)) {
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
