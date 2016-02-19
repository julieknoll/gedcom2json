<?php

/**
 * <var>gedcom2json.php</var> contains the code for converting a gedcom file to JSON
 *
 * @author Julie Knoll <julieknoll@gmail.com>
 * @copyright 2016 Julie Knoll
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 0.1
 */

try {

	if (!isset($argv[1])) {
		throw new Exception('Missing gedcom file');
	}

	if (!is_file($argv[1])) {
		throw new Exception('Gedcom file does not exist');
	}

	if (!$gedcom = file($argv[1], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) {
		throw new Exception('Gedcom file not readable');
	}

	if (!$count = count($gedcom)) {
		throw new Exception('Gedcom file is empty');
	}

	// initialize records array
	$records = array(
		'families' => array(),
		'persons' => array());

	// start looping through lines in the gedcom file
	for ($i = 0; $i < $count; $i++) {

		// if the line code is 0 start a new record
		if (getGedcomCode($gedcom[$i]) === '0') {

			// break apart line to get the id and type of record
			list(, $id, $type) = explode(' ', $gedcom[$i]);

			switch ($type) {
				
				// start a new person record
				case 'INDI':

					$person = array();

					// get all the facts for this person
					while(getGedcomCode($gedcom[$i+1]) !== '0') {

						$i++;

						// get the type of fact and text
						list(, $fact, $text) = explode(' ', $gedcom[$i], 3); 

						// check the next line code
						// if it's 2 then there are details about the fact to get    
						if (getGedcomCode($gedcom[$i+1]) === '2') {

							// initialize the fact array
							$person[$fact] = array();

							// if the fact had text also, store in array
							if ($text) {
								$person[$fact]['TEXT'] = $text;
							}

							// loop through the fact details
							while (getGedcomCode($gedcom[$i+1]) === '2') {

								$i++;

								list(, $factAttr, $text) = explode(' ',$gedcom[$i], 3);

								if ($text) {
									$person[$fact][$factAttr] = $text;
								}
							}

						} else {
							// just store the fact as string if there were no sub details
							$person[$fact] = $text;
						}
					}

					// store the person to the main records array
					$records['persons'][$id] = $person;
				break;

				case 'FAM':

					// create a new family record
					$family = array();

					// get all the people in this family
					while(getGedcomCode($gedcom[$i+1]) !== '0') {

						$i++;

						// get the relationship and id of person
						list(, $relation, $personId) = explode(' ', $gedcom[$i]); 

						// initialize the relation array
						// if there's not a person id, the data might be about the marriage in the family
						if ($personId) {
							$family[$relation] = array('PERSON' => $personId);
						} else {
							$family[$relation] = array();
						}

						// check the next line code
						// if it's 2 then there are details about the relationship
						if (getGedcomCode($gedcom[$i+1]) === '2') {

							// loop through the relation details
							while (getGedcomCode($gedcom[$i+1]) === '2') {

								$i++;

								list(, $relAttr, $value) = explode(' ',$gedcom[$i], 3);

								$family[$relation][$relAttr] = $value;
							}
						}
					}

					// store the person to the main records array
					$records['families'][$id] = $family;
				break;
			}
		}
	}
	print json_encode($records, JSON_PRETTY_PRINT);

} catch (Exception $e) {

	echo "\n".$e->getMessage();
	exit;
}

function getGedcomCode($string) {
	return substr($string, 0, 1);
}