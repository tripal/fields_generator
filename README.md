# Tripal Fields Generator
This is a CLI tool to help automate the generation of [Tripal fields](http://tripal.info/tutorials/v3.x/developers_handbook/custom_field).

## Documentation

### Download
You can download this tool using the "[Clone or download](https://github.com/statonlab/fields_generator/archive/master.zip)" button above or cloning the repository using Git.
```shell
git clone https://github.com/statonlab/fields_generator.git
```

### Usage
* Generate a new ChadoField by running the following command and answering a few questions.
```shell
php generate.php
```

### Output

Tripal Fields Generator will create four files that define your field.  For the custom controlled vocabulary (CV) term `example` defined in the `local` CV, the field is defined in three files:
* The Fields class, `local__example.inc`
* The field formatter, `local__example_formatter.inc`
* The field widget, `local__example_widget.inc`

Additionally, a fields file stub describing the fields declared in your module is generated: for this example module, the file might be  `tripal_example_module.fields.inc`.  Note that *all* of the fields in your module are described here: running TFG multiple times will require you to combine this file for each field.
The final structure of your fields should look like the example below, with a given field `CV__CVTERM` in `module/includes/TripalFields/CV_CVterm`, and the `module.fields.inc` located in `module/includes/TripalFields`.

```
     module/
      ├── includes/
      │   ├── TripalFields/
      │   │    └── CV__CVterm/
      │   │    │   ├── CV__CVterm.inc
      │   │    │   ├── CV__CVterm_formatter.inc
      │   │    │   └── CV__CVterm_widget.inc
      │   └── module.fields.inc
      │   
      ├── rest of my module...

```

#### Output file structure 

By default, the field file be placed in `CV__CVterm_output`, and the classes defining your field will be in the field folder `CV__CVterm_output/CV__CVterm`.  You may specify a different output path using the output flag, `-o="/path/to/module"` or `--output="/path/to/module"`.  


### Terms
The below terms must be provided for each field you generate.

 * **Field Label**: A human readable label for the field. e,g. Germplasm Summary
 * **Field Description**:  (A human readable description of the field)
 *  **Module Name**:  The machine name of the module this field is distributed with.  e,g. tripal_germplasm_module)
   *  **Controlled Vocabulary** The machine name of the Chado controlled vocabulary containing your field term. e,g. go)
 *  **Controlled Vocabulary Term** e,g. germplasm_summary
 * **Accession**: The accession number for this term in the vocabulary, e,g. 30021.  This must match the dbxref value in Chado.

## Contributing
Contributions are highly welcomed and recommended.
- Fork the repository.
- Create a branch that contains your code.
- Create a pull request with a clear description of your contribution for us to review.

## License
This tool is licensed under [GNU GPLv2](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html). Copyright 2017 [University of Tennessee](https://utk.edu). All rights reserved.