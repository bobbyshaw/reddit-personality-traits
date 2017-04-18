# Reddit Personalities

Take CSV of reddit user comments and provide information on personality traits in CSV form.


## Usage Instructions

The tools can be run either by passing a single url in, or a file of URLs separated by new lines

	php app/console traits:check --file comments.csv > results.csv

Example output:


## Development instructions

PHPUnit can be run with:

	vendor/bin/phpunit

## Notes

Using PSR-4 autoloader and symfony console.
