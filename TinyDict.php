<?php

require_once 'CharSeparatedValues/CharSeparatedValues.php';

/**
 * Tiny Dictionary
 *
 * @author murchik <murchik@nigma.ru>
 * @version 0.0.2
 */
abstract class TinyDict {

	const COUNTING_TAG_PATTERN = '/^(?<count>[0-9]+)(?<name>[a-z]*)$/isUx';

	protected $_dict = '';
	protected $_searchIn = array('original', 'translation');
	protected $_testColumns = array('original', 'translation');

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

		return $this->_formatOutput($result);
	}

	protected function _formatOutput($result) {
		$out = '';
		foreach ($result as &$chars) {
			$out .= $chars->original . "\t—\t" . $chars->translation;
			$out .= ' (' . str_replace(',', ', ', $chars->tags) . ")\n";
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
		$column0 = $this->_testColumns[0];
		$column1 = $this->_testColumns[1];
		
		$count = (((int) $this->_input) < 2) ? 20 : ((int) $this->_input);

		$countOrig = $count / 2;
		$count -= $countOrig;

		// reading dictionary in original way
		$dict = new CharSeparatedValues($this->_dict, true, "\t");

		foreach ($dict as $pieces) {
			$word0 = $this->_normalize($dict->$column0);
			$word1 = $this->_normalize($dict->$column1);
			if (!isset($dictContents[$word0])) {
				$dictContents[$word0] = array();
			}
			if (!isset($dictContents[$word1])) {
				$dictContents[$word1] = array();
			}
			$dictContents[$word0][] = array(
				'word' => trim($dict->$column0),
				'translation' => trim($dict->$column1),
				'tags' => explode(',', $dict->tags),
				'direction' => 0
			);
			$dictContents[$word1][] = array(
				'word' => trim($dict->$column1),
				'translation' => trim($dict->$column0),
				'tags' => explode(',', $dict->tags),
				'direction' => 1
			);
		}

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
					fwrite(STDOUT, "Correct answer: "
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

		$dict = new CharSeparatedValues($this->_dict, true, "\t");

		foreach ($dict as $pieces) {
			// searchin only by tags
			if (!empty($tags) && empty($input)) {
				if ($dict->tags) {
					$dictTags = explode(',', $dict->tags);
					$allTagsFound = true;
					foreach ($tags as &$t) {
						if (array_search($t, $dictTags) === false) {
							$allTagsFound = false;
						}
					}
					if ($allTagsFound) {
						$result[] = clone $dict;
					}
				}
			// simple or mixed search
			} else {
				$normalized = array();
				if ($normalize) {
					foreach ($this->_searchIn as $searchIn) {
						if ($dict->$searchIn) {
							$normalized[$searchIn] = $this->_normalize($dict->$searchIn);
						}
					}
				}
				if (empty($tags) || (!empty($tags) && ($dict->tags
					&& array_intersect($tags, explode(',', $dict->tags))))) {

					$found = false;
					foreach ($normalized as $searchIn => &$d) {
						if ($input == $d) {
							$result[] = clone $dict;
							break;
						}						
						$quasiWords = $this->_getQuasiWords($searchIn, $d);
						foreach ($quasiWords as &$q) {
							$q = $this->_cleanQuasiWord($q);
							if ($input == $q) {
								$result[] = clone $dict;
								$found = true;
								break;
							}
						}
						if ($found) {
							break;
						}
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Get a minimal sense-containing parts of the phrase.
	 *
	 * Some languages has space character as a word separator, some doesn't.
	 * Some doesn't even have words as we know it.
	 */
	protected function _getQuasiWords($column, $phrase) {
		return explode(' ', $phrase);
	}

	/**
	 * Getting rid of meaningless characters.
	 *
	 * Like punctuation or any other improper characters of chosen language.
	 */
	protected function _cleanQuasiWord($word) {
		$abnormalSymbols = implode('', $this->_normalizationMatrixReady['from']);
		$wordPattern = '/[^a-zа-яё' . $abnormalSymbols . ']+/sui';
		return mb_strtolower(preg_replace($wordPattern, '', $word));
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
