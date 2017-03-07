# awesome-php-csv

AwesomePHPCSV is a simple, fast &amp; lightweight CSV parser built for PHP 5.3+

# Breaking Changes in 2.1
**1.** `hasHeadingRow` parameter has been renamed to `skipHeaderRow` which is what it actually does

**2.** `mapColumns` parameter has been added to use the values from the first row as associative array keys for the rest of the rows in the returned results *(may lower performance)*

# Usage

AwesomePHPCSV is simple and easy to use.  Just follow the instructions below:

**1.** include AwesomePHPCSV into your own PHP project via Composer.

From the command line:
```
composer require pfuri/awesomephp-csv:~2.1
```

Or in your composer.json file add pfuri/awesome-php-csv to the list of required packages and then run `composer update`:
```javascript
{
    ...
    "require": {
        ...
        "pfuri/awesome-php-csv": "~2.1",
        ...
    }
    ...
}
```

**2.** create an AwesomePHPCSV instance:
```php
use pfuri\AwesomePHPCSV;

$apcsv = new AwesomePHPCSV();
```

***Having trouble?  Don't forget to include the Composer autoload.php file!"***
```php
require __DIR__ . '/vendor/autoload.php';
```

**3.** create an options array (see options section below for a full set of options):
```php
$options = array(
    'pathToFile' => 'example.csv',
    'hasHeaderRow' => true
);
```

**4.** call the import function the CSV into a PHP array:
```php
$data = $apcsv->import($options);
if($data === false) {
    // false means there was some kind of error
    // error messages can be found in the error message array
    $errorMessages = $apcsv->errorMessages;
    print_r($errorMessages);
}
```

The result is now a PHP array with each row representing a row from the CSV file (see the result format section below for an example)



# Import Options
Below is the complete list of options for ***AwesomePHPCSV::import(array $options)***:

* *string **pathToFile** (required)*: The full path to the csv file to be parsed *(note: if ***file*** is used instead of ***pathToFile***, then ***pathToFile*** is not required)*
* *string **file** (optional)*: The CSV file in array format, where each row of the CSV is a string in the array *(note: must not include line endings)*
* *boolean **skipHeaderRow** (optional) (default: false)*: Whether or not to skip the first row
* *boolean **mapColumns** (optional) (default: false)*: use the values from the first row as associative array keys for the rest of the rows in the returned results *(may lower performance)*
* *int **columns** (optional) (default: null)*: Enables column validation by specifying how many the columns each row should have.  If the csv contains a row without exactly this many columns, import will fail
* *int **start** (optional) (default:1)*: The row to start on [inclusive]
* *int **end** (optional) (default:null)*: The row to end on [inclusive]
* *int **loopLimit** (optional) (default:100)*: The amount of time to spend parsing a single row.  You shouldn't need to change this
* *boolean **debug** (optional) (default:false)*: Prints error messages using debug() if any are encountered



# Result Format
If this is your CSV file:

> Year, Make, Model, Color

> 2016, Cadillac, Escalade, Black


> 2016, Mercedes Benz, ML350, Black

Then after importing, you would receive a PHP array that looks like:
```php
[
    [2016, Cadillac, Escalade, Black],
    [2016, Mercedes Benz, ML350, Black]
]
```



# Example File
*example.php* will import *example.csv* and output the results.
