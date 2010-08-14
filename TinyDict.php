<?php

/**
 * Tiny Dictionary
 *
 * @author murchik <murchik@nigma.ru>
 */
abstract class TinyDict {

	/**
	 * @see normalizeInput()
	 */	
	protected $_normalizationMatrix = array();

	/**
	 * @see __construct()
	 */	
	private $_input = '';
	private $_tags = array();

	protected $_dict = '';


	/**
	 * Constructor
	 *
	 * @param String $input input word
	 * @param String $tags comma separated tags to filter
	 */	
	public function __construct($input, $tags) {
		$this->_input = trim($input);

		$this->_tags = explode(',', $tags);
		foreach ($this->_tags as &$tag) {
			$tag = trim($tag);
		}

		$this->_dict = dirname(__FILE__) . '/' . $this->_dict;
		if (!file_exists($this->_dict)) {
			die('Gimme dictionary file');
		}
	}

	/**
	 * Try to find matches
	 *
	 * @param Array $words
	 * @return Array
	 */	
	public function run() {
		$result = $this->_search();

		// search normalized word if there's no exact matches
		if (empty($result)) {
			$this->_input = $this->_normalizeInput();
			$result = $this->_search();
		}

		// filter matches by tags
		if (!empty($result)) {
			$result = $this->_filter($result);
		}

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
			$out .= "\n";
		}

		return $out;
	}

	/**
	 * Normalize input word
	 */	
	protected function _normalizeInput() {
		$from = array();
		$to = array();
		foreach ($this->_normalizationMatrix as $toSym => &$fromArr) {
			foreach ($fromArr as $fromSym) {
				$from[] = $fromSym;
				$to[] = $toSym;
			}
		}
		$result = str_replace($from, $to, $this->_input);
		return $result;
	}

	/**
	 * Search
	 *
	 * @return Array
	 */	
	private function _search() {
		//exec("grep '" . $this->_input . "' " . $this->_dict, $output);
		$result = array();

		$file = file_get_contents($this->_dict);
		$dict = explode("\n", $file);

		foreach ($dict as &$row) {
			$pieces = explode("\t", $row);
			if (array_key_exists(0, $pieces) && $pieces[0] == $this->_input) {
				$result[0][] = $pieces;
			}
			if (array_key_exists(1, $pieces) && $pieces[1] == $this->_input) {
				$result[1][] = $pieces;
			}
		}
		return $result;
	}

	/**
	 * Filter by tags
	 *
	 * @param Array $words
	 * @return Array
	 */	
	private function _filter($words) {
		return $words;
	}

}
