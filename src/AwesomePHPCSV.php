<?php
/**
 * AwesomePHPCSV
 *
 * <p>AwesomePHPCSV is a simple, fast & lightweight CSV importer built for PHP 5.3+.</p>
 *
 * <p>See github project page for latest version and usage information:</p>
 *
 * @link https://github.com/pfuri/awesome-php-csv
 *
 * @author Paul Furiani <pf@furiani.net>
 *
 * @copyright 2016 Paul Furiani
 *
 * @version 2.1.0
 *
 */

namespace pfuri;

class AwesomePHPCSV
{
    public $errorMessages = array();

    /**
     * This function will parse a given CSV file and return the results in array format
     *
     * <em>note: empty lines are ignored</em>
     *
     * @param array $options - an associative array with the following key(option), value pairs:
     *      <ul>
     *          <li><em>string <strong>pathToFile</strong> (required)</em>: The full path to the csv file to be parsed <em>(note: if file is used instead of pathToFile, then pathToFile is not required)</em></li>
     *          <li><em>array <strong>file</strong> (optional)</em>: The CSV file in array format, where each row of the CSV is a string in the array <em>(note: must not include line endings)</em>
     *          <li><em>boolean <strong>skipHeaderRow</strong> (optional) (default: false)</em>: Whether or not to skip the first row</li>
     *          <li><em>boolean <strong>mapColumns</strong> (optional) (default: false)</em>: use the values from the first row as associative array keys for the rest of the rows in the returned results (may lower performance)</li>
     *          <li><em>int <strong>columns</strong> (optional) (default: null): Enables column validation by specifying how many the columns each row should have.  If the csv contains a row without exactly this many columns, import will fail</li>
     *          <li><em>int <strong>start</strong> (optional) (default:1)</em>: The row to start on [inclusive]</li>
     *          <li><em>int <strong>end</strong> (optional) (default:null)</em>: The row to end on [inclusive]</li>
     *          <li><em>int <strong>loopLimit</strong> (optional) (default:100)</em>: The amount of time to spend parsing a single row.  You shouldn't need to change this in most cases</li>
     *      </ul>
     *
     * @return boolean|array - the csv in array format or boolean FALSE if there was an error
     */
    public function import(array $options = array())
    {
        // reset error messages for new import
        $this->reset();

        // set defaults & merge in the options
        $defaults = array(
            'pathToFile' => '',
            'file' => null,
            'skipHeaderRow' => false,
            'mapColumns' => false,
            'columns' => null,
            'start' => 1,
            'end' => null,
            'loopLimit' => 100,
            'debug' => false
        );
        $options = array_merge($defaults, $options);

        // safe extract
        $columns = $options['columns'];
        $debug = $options['debug'];
        $end = $options['end'];
        $skipHeaderRow = $options['skipHeaderRow'];
        $mapColumns = $options['mapColumns'];
        $file = $options['file'];
        $loopLimit = $options['loopLimit'];
        $pathToFile = $options['pathToFile'];
        $start = $options['start'];

        // load CSV
        if (!is_array($file)) {
            $file = file($pathToFile, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
        }
        if ($file === false) {
            $message = __('Error: could not load file: ').$pathToFile;
            $this->errorMessages[] = $message;
            return false;
        }

        // parse header row
        $headerRow = false;
        if($mapColumns && count($file) > 0) {
            $headerRow = $this->parseLine($file[0]);
            if(!$headerRow) {
                $this->errorMessages[] = __('Error parsing header row');
            } else {
                $skipHeaderRow = true;
            }
        }

        // parse CSV
        $parsedCSV = array();
        for ($i = $start - 1; $i < count($file); $i++) {
            $line = $file[$i];
            if ($skipHeaderRow && $i == 0) {
                // skip header row
                continue;
            } elseif ($end && $i > $end - 1) {
                // stop at end row
                break;
            } elseif (trim($line) == "") {
                // skip empty lines
                continue;
            }

            $rowData = $this->parseLine($line, $headerRow);
            if($rowData === false) {
                // error
                $this->errorMessages[] = __('Error parsing row on line: ').($i + 1);
                return false;
            }

            // validate number of columns
            $valid = $columns == null || count($rowData) == $columns; // column count validation
            if (!$valid) {
                $message = __('Error, wrong number of columns parsed on line ').($i + 1).__('. Expecting: ').$columns.__(', Found: ').count($rowData);
                $this->errorMessages[] = $message;
                return false;
            }

            // add CSV row to CSV array
            $parsedCSV[] = $rowData;
        }

        // return CSV array
        return $parsedCSV;
    }

    /**
     * resets the error messages
     */
    public function reset()
    {
        $this->errorMessages = array();
    }

    private function parseLine($line = '', $columnMap = false, $loopLimit = 100) {
        // init parser
        $counter = $startPos = 0;
        $inString = false;
        $rowData = array();
        $term = '';

        // parse CSV
        while ($startPos <= strlen($line)) {
            // check loop limit
            if ($counter > $loopLimit) {
                $this->errorMessages[] = __('Error: Loop limit of ').$loopLimit.__(' exceeded');
                return false;
            }

            // string or term?
            if (substr($line, $startPos, 1) == '"') { // string
                // init string extractor
                $searchPos = $startPos + 1;
                $length = 0;
                $found = false;

                // extract string
                while (!$found) {
                    // extract string up until next quote
                    $nextQuotePos = strpos($line, '"', $searchPos);
                    if ($nextQuotePos === false) {
                        $message =  __('Error: Could not locate end of string');
                        $this->errorMessages[] = $message;
                        return false;
                    }

                    // determine end of string or inner quote
                    $charAfterNextQuote = substr($line, $nextQuotePos + 1, 1);
                    if (in_array($charAfterNextQuote, array(false, '', ','))) { // end of string
                        // Extract the full term, with wrapping quotes
                        $length = ($nextQuotePos - $startPos) + 1;
                        $term = substr($line, $startPos, $length);

                        // remove wrapping quotes
                        $term = substr($term, 1);
                        $term = substr($term, 0, strlen($term) - 1);

                        // unescape inner quotes
                        $term = str_replace('""', '"', $term);

                        // add term to CSV row
                        if($columnMap) {
                            $columnMapInd = count($rowData);
                            if(isset($columnMap[$columnMapInd])) {
                                $columnName = $columnMap[$columnMapInd];
                                $rowData[$columnName] = $term;
                            } else {
                                // column not found
                                $message =  __('Error: Column name for index ').$columnMapInd.__(' not found in column map');
                                $this->errorMessages[] = $message;
                                return false;
                            }
                        } else {
                            // numeric index
                            $rowData[] = $term;
                        }

                        // update parser
                        $found = true;
                        $startPos = $nextQuotePos + 2;
                    } else {
                        // inner quote or empty string, skip 2 places because inner quotes should be in the form ""
                        $searchPos = $nextQuotePos + 2;
                    }
                }
            } else { // term
                // find the next comma delimiter
                $nextCommaPos = strpos($line, ',', $startPos);

                // extract term
                if ($nextCommaPos === false) { // last term
                    if ($startPos == strlen($line)) {
                        // last term empty, do special handling
                        $term = "";
                    } else {
                        // last term not empty
                        $term = substr($line, $startPos);
                    }

                    // update parser
                    $startPos += strlen($term) + 1;
                } else { // not last term
                    // get term length
                    $length = ($nextCommaPos - $startPos);

                    // extract the term
                    $term = substr($line, $startPos, $length);

                    // update the Parser
                    $startPos = $nextCommaPos + 1;
                }

                // add term to CSV row
                if($columnMap) {
                    $columnMapInd = count($rowData);
                    if(isset($columnMap[$columnMapInd])) {
                        $columnName = $columnMap[$columnMapInd];
                        $rowData[$columnName] = $term;
                    } else {
                        // column not found
                        $message =  __('Error: Column name for index ').$columnMapInd.__(' not found in column map');
                        $this->errorMessages[] = $message;
                        return false;
                    }
                } else {
                    // numeric index
                    $rowData[] = $term;
                }
            }

            // update loop counter
            $counter++;
        }

        // return the parsed row
        return $rowData;
    }
}
