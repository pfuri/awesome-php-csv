<?php
/**
 * example.php
 *
 * <p>example.php is a usage example for AwesomePHPCSV</p>
 *
 * <p>See project page for latest version and usage information:</p>
 *
 * @link https://github.com/pfuri/awesome-php-csv
 *
 * @author Paul Furiani <pf@furiani.net>
 *
 * @copyright 2016 Paul Furiani
 *
 */

	// include AwesomePHPCSV.php
	include('AwesomePHPCSV.php');
	
	// create the options array
	$options = array(
			'pathToFile' => 'example.csv',
			'hasHeadingRow' => true
	);
	
	// create an AwesomePHPCSV instance
	$apcsv = new AwesomePHPCSV();
	
	// import the example.csv
	$data = $apcsv->import($options);
	
	// print the result
	echo '<pre>';
		print_r($data);
	echo '</pre>';
?>
