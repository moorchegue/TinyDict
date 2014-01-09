<?php

require_once 'TinyDict/TinyDict.php';

/**
 * Tiny Chinese dictionary
 *
 * @author murchik <mixturchik@gmail.com>
 */
class TinyDictChinese extends TinyDict {

	protected $_dict = 'npcr.dict';
	protected $_searchIn = array('simplified', 'traditional', 'pinyin', 'translation');
	protected $_testColumns = array('simplified', 'translation');

	private $_chineseCharacters = array('simplified', 'traditional');

	/**
	 * @see normalizeInput()
	 */
	protected $_normalizationMatrix = array(
		'a'	=> array('á', 'à', 'ā', 'ǎ'),
		'A'	=> array('Á', 'À', 'Ā', 'Ǎ'),
		'e'	=> array('é', 'è', 'ē', 'ě'),
		'E'	=> array('É', 'È', 'Ē', 'Ě'),
		'i'	=> array('í', 'ì', 'ī', 'ǐ'),
		'I'	=> array('Í', 'Ì', 'Ī', 'Ǐ'),
		'o'	=> array('ó', 'ò', 'ǒ', 'ō'),
		'O'	=> array('Ó', 'Ò', 'Ǒ', 'Ō'),
		'u'	=> array('ú', 'ù', 'ū', 'ǔ', 'ü', 'ǘ', 'ǚ', 'ǜ'),
		'U'	=> array('Ú', 'Ù', 'Ū', 'Ǔ', 'Ü', 'Ǘ', 'Ǚ', 'Ǜ'),

	);

	protected function _getQuasiWords($column, $phrase) {
		if (in_array($column, $this->_chineseCharacters)) {
			$words = array();
			for ($i = 0; mb_strlen($phrase) > $i; $i++) {
				$words[] = mb_substr($phrase, $i, 1);
			}
			return $words;
		} else {
			return parent::_getQuasiWords($column, $phrase);
		}
	}

	/**
	 * These are just from NCPR.
	 *
	 * TODO: find out about chinese characters ranges and put it in regexp.
	 */
	protected function _cleanQuasiWord($q) {
		$wordPattern = '/[-0-9\s!"\'\(\),\.\/;\?，…]+/sui';
		return preg_replace($wordPattern, '', $q);
	}
	
	protected function _formatOutput($result) {
		$out = '';
		foreach ($result as &$chars) {
			$out .= $chars->simplified . "\t(" . $chars->pinyin . ")\t—\t" . $chars->translation;
			$out .= ' (' . str_replace(',', ', ', $chars->tags) . ")\n";
		}

		return $out;
	}
}
