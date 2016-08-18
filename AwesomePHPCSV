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
 * @version 1.3
 * 
 */
class AwesomePHPCSV {
	
	public $errorMessages = array();
	
	/**
	 * This function will parse a given CSV file and return the results in array format
	 * 
	 * <em>note: empty lines are ignored</em>
	 * 
	 * @param array $options - an associative array with the following key(option), value pairs:
	 * 		<ul>
	 * 			<li><em>string <strong>pathToFile</strong> (required)</em>: The full path to the csv file to be parsed <em>(note: if file is used instead of pathToFile, then pathToFile is not required)</em></li>
	 * 			<li><em>array <strong>file</strong> (optional)</em>: The CSV file in array format, where each row of the CSV is a string in the array <em>(note: must not include line endings)</em>
	 * 			<li><em>boolean <strong>hasHeadingRow</strong> (optional) (default: false)</em>: Whether or not the csv has a heading row to ignore</li>
	 *			<li><em>int <strong>columns</strong> (optional) (default: null): Enables column validation by specifying how many the columns each row should have.  If the csv contains a row without exactly this many columns, import will fail</li>
	 *			<li><em>int <strong>start</strong> (optional) (default:1)</em>: The row to start on [inclusive]</li>
	 *			<li><em>int <strong>end</strong> (optional) (default:null)</em>: The row to end on [inclusive]</li>
	 *			<li><em>int <strong>loopLimit</strong> (optional) (default:100)</em>: The amount of time to spend parsing a single row.  You shouldn't need to change this in most cases</li>
	 *		</ul>
	 *
	 * @return boolean|array - the csv in array format or boolean FALSE if there was an error
	 */
	public function import(array $options = array()) {
		// reset error messages for new import
		$this->reset();
		
		// set defaults & merge in the options
		$defaults = array(
				'pathToFile' => '',
				'file' => null, 
				'hasHeadingRow' => false, 
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
		$hasHeadingRow = $options['hasHeadingRow'];
		$file = $options['file'];
		$loopLimit = $options['loopLimit'];
		$pathToFile = $options['pathToFile'];
		$start = $options['start'];

		// load CSV 
		if(!is_array($file)) {
			$file = file($pathToFile, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
		}
		if($file === false) { 
			$message = __('Error: could not load file: ').$pathToFile;
			$this->errorMessages[] = $message; 
			return false;
		}

		// parse CSV
		$parsedCSV = array();
		for($i = $start - 1; $i < count($file); $i++) {
			$line = $file[$i];
			if($hasHeadingRow && $i == 0) {
				// skip heading row
				continue; 
			} elseif($end && $i > $end) {
				// stop at end
				break; 
			} elseif(trim($line) == "") {
				// skip empty lines
				continue; 
			}
			
			// init parser
			$counter = $startPos = 0;
			$inString = false;
			$rowData = array();
			$term = '';
			
			// parse CSV
			while($startPos <= strlen($line)) {
				// check loop limit
				if($counter > $loopLimit) { 
					$this->errorMessages[] = __('Error parsing row on line: ').($i + 1).__(', Loop limit of ').$loopLimit.__(' exceeded'); 
					return false; 
				}
				
				// string or term?
				if(substr($line, $startPos, 1) == '"') { // string
					// init string extractor
					$searchPos = $startPos + 1; 
					$length = 0; 
					$found = false;
					
					// extract string
					while(!$found) {
						// extract string up until next quote
						$nextQuotePos = strpos($line, '"', $searchPos); 
						if($nextQuotePos === false) {
							$message =  __('Error parsing row on line: ').($i + 1).__('. Could not locate end of string');
							$this->errorMessages[] = $message; 
							return false; 
						}
						
						// determine end of string or inner quote
						$charAfterNextQuote = substr($line, $nextQuotePos + 1, 1);
						if($charAfterNextQuote === false || $charAfterNextQuote == ',') { // end of string
							// Extract the full term, with wrapping quotes
							$length = ($nextQuotePos - $startPos) + 1;
							$term = substr($line, $startPos, $length); 
							
							// remove wrapping quotes
							$term = substr($term, 1); 
							$term = substr($term, 0, strlen($term) - 1); 
							
							// unescape inner quotes
							$term = str_replace('""', '"', $term);
							
							// add term to csv row
							$rowData[] = $term;
							
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
					if($nextCommaPos === false) { // last term
						if($startPos == strlen($line)) {
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
					$rowData[] = $term; 
				}
				
				// update loop counter
				$counter++; 
			}

			// validate
			$valid = $columns == null || count($rowData) == $columns; // column validation
			if(!$valid) { 
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
	public function reset() { 
		$this->errorMessages = array();
	}
}
