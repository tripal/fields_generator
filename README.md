[![Build Status](https://travis-ci.org/statonlab/fields_generator.svg?branch=master)](https://travis-ci.org/statonlab/fields_generator)


# Tripal Fields Generator
This is a CLI tool to help automate the generation of [Tripal fields](http://tripal.info/tutorials/v3.x/developers_handbook/custom_field) for use in [Tripal 3](http://tripal.info/) development.  We highly recommend you read the developer handbook before attempting to use this tool.  It supports creating both Tripal and Chado fields.


![TFG hearthstone logo](/assets/TFG.png)

## Documentation

### Installation

#### Using Composer

The easiest way to install **Tripal Fields Generator** is using [Composer](https://getcomposer.org/).

**Install command**
```shell
composer global require "statonlab/fields_generator:~0.1"
```
**Update command**
```shell
composer global update
```
**Note:** make sure you export the composer bin directory by running the following command:
```shell
# Add this line to your .bashrc or .bash_profile to persist between shell sessions.
export PATH="$PATH:~/.composer/vendor/bin"
```

#### Manual installation
You can download this tool using the "[Clone or download](https://github.com/statonlab/fields_generator/archive/master.zip)" button above or cloning the repository using Git.
```shell
git clone https://github.com/statonlab/fields_generator.git
```

### Usage

Generate a new field by running the following command and answering a few questions.  By default, **Tripal Fields Generator** will generate a Chado field, but it can also generate Tripal fields.

```shell
# If installed globally with composer
makefield [--output|-o=/full/path/to/module]

# If installed manually without composer
./makefield [--output|-o=/full/path/to/module]
```

The generator will ask you for both the DB name and the CV name: these names correspond to the chado.db and chado.cv tables.  If you aren't sure what these values are, you can use the [EBI Ontology lookup Service](http://www.ebi.ac.uk/ols/) CVterm entry as a guide: the DB name is the value in the *orange* box, and the CV name is the value in the *teal* box. For a full explanation, please read [the CV guide](CV_guide.md). 

Alternatively, the below table shows the DB and CV values for commonly used ontologies in Tripal 3.

| FULL NAME                                      | DB        | CV                 |
|------------------------------------------------|-----------|--------------------|
| DCMI metadata terms                            | dc        | dc                 |
| Eagle-I Research Ontology                      | ERO       | ero                |
| EDAM data                                      | data      | EDAM               |
| EDAM format                                    | format    | EDAM               |
| EDAM operation                                 | operation | EDAM               |
| EDAM topic                                     | topic     | EDAM               |
| Friend of a Friend                             | foaf      | foaf               |
| Gene ontology biological process               | GO        | biological_process |
| Gene ontology cellular component               | GO        | cellular_component |
| Gene ontology molecular function               | GO        | molecular_function |
| hydra                                          | hydra     | hydra              |
| Information Artifact Ontology                  | IAO       | IAO                |
| local                                          | local     | local              |
| Ontology for Biomedical Investigation          | OBI       | OBI                |
| Ontology for genetic interval                  | OGI       | ogi                |
| Ontology of Biological and Clinical Statistics | OBCS      | OBCS               |
| Relationship ontology (legacy)                 | RO        | ro                 |
| Resource Description Framework Schema          | rdfs      | rdfs               |
| schema                                         | schema    | schema             |
| Semanticscience Integrated Ontology            | SIO       | SIO                |
| Sequence ontology                              | SO        | sequence           |
| Software Ontology                              | SWO       | swo                |
| Systems Biology                                | SBO       | sbo                |
| Taxonomic Rank                                 | TAXRANK   | taxonomic_rank     |

In order for Drupal to recognize your fields, you must...

* Place the file generated by this tool in the correct location (see below)
* Specify which bundles your field will attach to.  Fields generated by this tool attach to the `organism` bundle by default.
* Clear the cache (`drush cc all`).
* Add the field in Structure->Tripal_Content

#### Options

|Option|default|description|example|
|------|-------|-----------|-------|
|\--output (-o)|Current working directory|The path to the module responsible for the field. |`makefield -o="/var/www/html/sites/all/modules/my_module"`|
|\--type (-t)|`chado`|The type of field to generate. Choose between Chado which would extend ChadoField or Tripal to extend TripalField|`makefield -t=tripal`|

### Output
**Tripal Fields Generator** will create four files that define your field.  For the custom controlled vocabulary (CV) term `example` defined in the `local` CV, the field is defined in three files:
* The Fields class, `local__example.inc`
* The field formatter, `local__example_formatter.inc`
* The field widget, `local__example_widget.inc`

Additionally, a fields file stub describing the fields declared in your module is generated: for this example module, the file might be  `tripal_example_module.fields.inc`.  Note that *all* of the fields in your module are described here: running **Tripal Fields Generator** multiple times will require you to combine this file for each field.
The final structure of your fields should look like the example below, with a given field `CV__CVTERM` in `module/includes/TripalFields/CV_CVterm`, and the `module.fields.inc` located in `module/includes`. For a full example of a field please visit the [Tripal Example Module repository](https://github.com/tripal/tripal_example) by [@laceysanderson](https://github.com/laceysanderson).

#### Output file structure 

By default, the module-level field file (`moduleName.fields.inc`) will be placed in `CV__CVterm_output`, and the classes defining your field will be in the field folder `CV__CVterm_output/CV__CVterm`.  Note that the value of `CV` corresponds to what you enter for the *DB*, not the *CV* table!

```
CV__CVterm_output/
├── CV__CVterm/
│   ├── CV__CVterm.inc
│   ├── CV__CVterm_formatter.inc
│   └── CV__CVterm_widget.inc  
└── module.fields.inc
```
You will need to move these files to conform to the pattern in the previous section.  Alternatively, you may specify a different output path using the output flag, `-o="/path/to/module"` or `--output="/path/to/module"`.  This will automatically define your fields in the correct place.  In either case, your final module field structure should look like the example below.

```
module/
├── includes/
│   ├── TripalFields/
│   │   └── CV__CVterm/
│   │       ├── CV__CVterm.inc
│   │       ├── CV__CVterm_formatter.inc
│   │       └── CV__CVterm_widget.inc
│   └── module.fields.inc
└── rest of my module...
```

### Terms
The below terms must be provided for each field you generate.

 - **Field Label**: A human readable label for the field. e,g. Germplasm Summary
 - **Field Description**:  A human readable description of the field
 - **Module Name**:  The machine name of the module this field is distributed with.  e,g. tripal_germplasm_module
 - **Database name**:
  - **CV name**:
 - **Controlled Vocabulary Term**: The term name. e,g. germplasm_summary
 - **Accession**: The accession number for this term in the vocabulary, e,g. 30021.  This must match the dbxref value in Chado.  Biological CVs will always use numeric CVterms.  Semantic CVs will generally use strings.

## Examples


## Contributing
Contributions are highly welcomed and recommended.
- Fork the repository.
- Create a branch that contains your code.
- Create a pull request with a clear description of your contribution for us to review.

## License
This tool is licensed under [GNU GPLv2](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html). Copyright 2017 [University of Tennessee](https://utk.edu). All rights reserved.

The Tripal Fields Generator "logo" is derived from the collectible card game Hearthstone, copyright © Blizzard Entertainment, Inc. Hearthstone® is a registered trademark of Blizzard Entertainment, Inc. Tripal Fields Generator is not affiliated or associated with or endorsed by Hearthstone® or Blizzard Entertainment, Inc.

