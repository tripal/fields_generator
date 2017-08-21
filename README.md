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
Tripal Fields Generator will create four files that define your field.  For the custom controlled vocabulary (CV) term `example` defined in the `local` CV, the field is defined in four files:
* The Fields class, `local__example.inc`
* The field formatter, `local__example_formatter.inc`
* The field widget, `local__example_widget.inc`

Additionally, a fields file stub describing the fields declared in your module is generated: for this example module, the file might be  `tripal_example_module.fields.inc`.


Once your fields are generated, 

## Contributing
Contributions are highly welcomed and recommended.
- Fork the repository.
- Create a branch that contains your code.
- Create a pull request with a clear description of your contribution for us to review.

## License
This tool is licensed under [GNU GPLv2](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html). Copyright 2017 [University of Tennessee](https://utk.edu). All rights reserved.