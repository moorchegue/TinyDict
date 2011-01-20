<?php

require_once 'TinyDict/TinyDict.php';

/**
 * Tiny nadsat dictionary
 *
 * @author murchik <murchik@nigma.ru>
 */
class TinyDictNadsat extends TinyDict {

	protected $_dict = 'nadsat.dict';

	/**
	 * @see normalizeInput()
	 */
	protected $_normalizationMatrix = array(
		'е'	=> array('ё'),
		'Е'	=> array('Ё'),
	);

}
