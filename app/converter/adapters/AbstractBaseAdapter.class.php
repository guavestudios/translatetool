<?php

namespace Guave\translatetool;

abstract class AbstractBaseAdapter {

	abstract public function save($content);

	/**
	 * Returns all the translate-data in the $file.
	 * The data is returned as an Array of associative Arrays. Each of these "subarrays"
	 * has the following keys: "key" containing the path.to.the.element and one key
	 * for each language. The keys for the languages are the country-codes as given
	 * in the config of the project e.g. de for German
	 *
	 * [
	 * 	[
	 * 		["key"] => "path.to.entry",
	 * 		["langcode01"] => "valueForLang01",
	 * 		["langcode02"] => "valueForLang02"
	 * 	]
	 * ]
	 *
	 * @param  String		$file		Path to the file to save
	 * @return Array						Array of arrays holding all the entries of the
	 *                          translated values (see above for the structure).
	 */
	abstract public function load($file);

}
