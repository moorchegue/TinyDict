<?php

/**
 * Tiny Dictionary
 *
 * @author murchik <murchik@nigma.ru>
 * @version 0.0.2
 */
abstract class TinyDict {

	const COUNTING_TAG_PATTERN = '/^(?<count>[0-9]+)(?<name>[a-z]*)$/isUx';

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
		$this->_input = mb_strtolower(trim($input));

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
			foreach ($fromArr as &$fromSym) {
				$this->_normalizationMatrixReady['from'][] = $fromSym;
				$this->_normalizationMatrixReady['to'][] = $toSym;
			}
		}
	}

	/**
	 * Try to find all the matches
	 *
	 * @param Array $words
	 * @return Array
	 */
	public function greedySearch() {
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
				$chars[0] = array_key_exists(0, $chars) ? $chars[0] : '';
				$chars[1] = array_key_exists(1, $chars) ? $chars[1] : '';
				$chars[2] = array_key_exists(2, $chars) ? $chars[2] : '';
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
	 * Get tag list
	 *
	 * @return String $out
	 */
	public function getTagList() {
		$file = file_get_contents($this->_dict);
		$dict = explode("\n", $file);

		$tagList = array();
		foreach ($dict as &$row) {
			$pieces = explode("\t", $row);
			$tags = array_pop($pieces);
			$wordTags = explode(',', $tags);

			foreach ($wordTags as &$tag) {
				if (!$tag) {
					continue;
				}
				elseif (!array_key_exists($tag, $tagList)) {
					$tagList[$tag] = 1;
				} else {
					$tagList[$tag]++;
				}
			}
		}

		uksort($tagList, __CLASS__ . '::tagCompare');

		$out = '';
		foreach ($tagList as $tag => &$count) {
			$out .= $tag . "\n";
		}

		return $out;
	}

	/**
	 * Test user
	 *
	 * @param Int $count
	 * @access public
	 * @return void
	 * @author Vsevolod Velichko <torkvemada@nigma.ru>
	 */
	public function testUser() {
		
		$count = (((int) $this->_input) < 2) ? 20 : ((int) $this->_input);

		$countOrig = $count / 2;
		$count -= $countOrig;

		// reading dictionary in original way
		$fh = fopen($this->_dict, "rb");
		while (!feof($fh))
		{
			$row = trim(fgets($fh));
			if (empty($row)) {
				continue;
			}

			$parts = explode("\t", $row);
			if (count($parts) < 3) {
				continue;
			}

			$tags = explode(',', trim($parts[2]));
			$word0 = $this->_normalize(trim($parts[0]));
			$word1 = $this->_normalize(trim($parts[1]));
			if (!isset($dictContents[$word0])) {
				$dictContents[$word0] = array();
			}
			if (!isset($dictContents[$word1])) {
				$dictContents[$word1] = array();
			}
			$dictContents[$word0][] = array(
				'word' => trim($parts[0]),
				'translation' => trim($parts[1]),
				'tags' => $tags,
				'direction' => 0
			);
			$dictContents[$word1][] = array(
				'word' => trim($parts[1]),
				'translation' => trim($parts[0]),
				'tags' => $tags,
				'direction' => 1
			);
		}
		fclose($fh);

		$dictCopy = array();
		foreach($dictContents as $rows) {
			$dictCopy = array_merge(
				$dictCopy,
				array_filter($rows, array($this, "_filterRowByTags"))
			);
		}

		if (count($dictCopy) == 0) {
			echo "No words matching tag list\n";
			return;
		} elseif (count($dictCopy) < ($count + $countOrig)) 	{
			echo "Too many questions for such small word list\n";
			return;
		}

		$right = 0;
		foreach (array(0 => $count, 1 => $countOrig) as $direction => $cnt) {
			while ($cnt > 0) {
				$found = false;
				$attempts = 30;
				while (!$found && $attempts > 0) {
					$key = array_rand($dictCopy);
					if ($dictCopy[$key]['direction'] == $direction) {
						$found = true;
					}
					$attempts--;
				}
				if ($attempts == 0) {
					echo "Can't find, what to ask, continuing.";
					break;
				}
				fwrite(STDOUT, $dictCopy[$key]['word'] . "\n");
				$answer = trim(fgets(STDIN));
				if ($answer == $dictCopy[$key]['translation']) {
					$right++;
					fwrite(STDOUT, "OK!\n\n");
				} else {
					fwrite(STDOUT, "Правильный вариант: "
						. $dictCopy[$key]['translation'] . "\n\n");
				}
				unset($dictCopy[$key]);
				$cnt--;
			}
		}

		echo "Statistics\n"
			. "Right:\t" . $right . ".\n"
			. "Wrong:\t" . ($count + $countOrig - $right) . ".\n";
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
	 * Check if row contents contain neccessary tags
	 *
	 * @param Array $row
	 * @access protected
	 * @return Boolean
	 * @author Vsevolod Velichko <torkvemada@nigma.ru>
	 */
	protected function _filterRowByTags($row) {
		if (count($this->_tags) > 0
			&& count(array_intersect($row['tags'], $this->_tags))
				!= count($this->_tags)) {
			return false;
		}
		return true;
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
		$wordPattern = '/[^a-zа-яё' . $anormalSymbols . ']+/sui';

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
					foreach ($dump as $k => &$d) {
						if ($k > 1) {
							break;
						}
						if ($input == $d) {
							$result[$k][] = $pieces;
							break;
						}						
						$quasiWords = explode(' ', $d);
						foreach ($quasiWords as &$q) {
							$q = mb_strtolower(preg_replace($wordPattern, '', $q));
							if ($input == $q) {
								$result[$k][] = $pieces;
								break;
							}
						}
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Tag comparation resource function
	 *
	 * @param Mixed $a
	 * @param Mixed $b
	 * @return Integer
	 */
	public static function tagCompare($a, $b) {
		if ($a == $b) {
			return 0;
		}

		if (preg_match(self::COUNTING_TAG_PATTERN, $a, $aPieces)
			&& preg_match(self::COUNTING_TAG_PATTERN, $b, $bPieces)) {
			if (($aPieces['name'] && $bPieces['name'])
				|| (!$aPieces['name'] && !$bPieces['name'])) {
				return $aPieces['count'] < $bPieces['count'] ? -1 : 1;
			}
			elseif ($aPieces['name']) {
				return 1;
			}
			elseif ($bPieces['name']) {
				return -1;
			}
		}
		elseif (preg_match(self::COUNTING_TAG_PATTERN, $a)) {
			return -1;
		}
		elseif (preg_match(self::COUNTING_TAG_PATTERN, $b)) {
			return 1;
		}

		return $a < $b ? -1 : 1;
	}

}
