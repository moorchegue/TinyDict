<?php

/**
 * Tiny Dictionary
 *
 * @author murchik <murchik@nigma.ru>
 * @version 0.0.1
 */
abstract class TinyDict {

	protected $_dict = '';

	/**
	 * @see normalizeInput()
	 */	
	protected $_normalizationMatrix = array();

	/**
	 * @see __construct()
	 */	
	private $_input = '';
	private $_tags = array();
	private $_normalizationMatrixReady = array();


	/**
	 * Constructor
	 *
	 * @param String $input input word
	 * @param String $tags comma separated tags to filter
	 */	
	public function __construct($input, $tags) {
		$this->_input = trim($input);

		$this->_tags = explode(',', $tags);
		foreach ($this->_tags as $k => &$tag) {
			$tag = trim($tag);
			if (empty($tag)) {
				unset($this->_tags[$k]);
			}
		}

		$this->_dict = dirname(__FILE__) . '/' . $this->_dict;
		if (!file_exists($this->_dict)) {
			die('Gimme dictionary file');
		}

		foreach ($this->_normalizationMatrix as $toSym => &$fromArr) {
			foreach ($fromArr as $fromSym) {
				$this->_normalizationMatrixReady['from'][] = $fromSym;
				$this->_normalizationMatrixReady['to'][] = $toSym;
			}
		}
	}

	/**
	 * Try to find matches
	 *
	 * @param Array $words
	 * @return Array
	 */	
	public function run() {
		$result = $this->_search($this->_input, $this->_tags);

		// search normalized word if there's no exact matches
		if (empty($result) || $this->_input == $this->_normalize($this->_input)) {
			$this->_input = $this->_normalize($this->_input);
			$result = $this->_search($this->_input, $this->_tags, true);
		}

		// format output
		$out = '';
		foreach ($result as $direction => &$translations) {
			foreach ($translations as &$chars) {
				if ($direction == 0) {
					$out .= $chars[0] . "\t—\t" . $chars[1];
				}
				if ($direction == 1) {
					$out .= $chars[1] . "\t—\t" . $chars[0];
				}
				$out .= ' (' . str_replace(',', ', ', $chars[2]) . ")\n";
			}
			//$out .= "\n";
		}

		return $out;
	}

	/**
	 * Normalize input word
	 */	
	protected function _normalize($input) {
		$result = str_replace(
			$this->_normalizationMatrixReady['from'],
			$this->_normalizationMatrixReady['to'],
			$input);
		return $result;
	}

	/**
	 * Search
	 *
	 * @param String $input
	 * @param Array $tags
	 * @param Boolean $normalize
	 * @return Array
	 */	
	private function _search($input, $tags, $normalize = false) {
		$result = array();
		$anormalSymbols = implode('', $this->_normalizationMatrixReady['from']);
		$wordPattern = '/[^a-zа-яё' . $anormalSymbols . ']+/si';

		$file = file_get_contents($this->_dict);
		$dict = explode("\n", $file);

		foreach ($dict as &$row) {
			$pieces = explode("\t", $row);
			$dump = $pieces;

			// searchin only by tags
			if (!empty($tags) && empty($input)) {
				if (array_key_exists(2, $pieces)) {
					$dictTags = explode(',', $pieces[2]);
					$allTagsFound = true;
					foreach ($tags as &$t) {
						if (array_search($t, $dictTags) === false) {
							$allTagsFound = false;
						}
					}
					if ($allTagsFound) {
						$result[0][] = $pieces;
					}
				}
			// simple or mixed search
			} else {
				if ($normalize) {
					if (array_key_exists(0, $pieces)) {
						$dump[0] = $this->_normalize($dump[0]);
					}
					if (array_key_exists(1, $pieces)) {
						$dump[1] = $this->_normalize($dump[1]);
					}
				}
				if (empty($tags) || (!empty($tags) && (array_key_exists(2, $pieces)
					&& array_intersect($tags, explode(',', $pieces[2]))))) {
					foreach ($dump as $k => $d) {
						if ($k > 1) {
							break;
						}
						$quasiWords = explode(' ', $d);
						foreach ($quasiWords as &$q) {
							if ($input == preg_replace($wordPattern, '', $q)) {
								$result[0][] = $pieces;
								break;
							}
						}
					}
				}
			}
		}
		return $result;
	}

}
