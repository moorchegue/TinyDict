<?php

require_once 'TinyDict/TinyDict.php';

/**
 * Tiny vietnamese dictionary
 *
 * @author murchik <murchik@nigma.ru>
 */
class TinyDictViet extends TinyDict {

	protected $_dict = 'viet.dict';

	/**
	 * @see normalizeInput()
	 */
	protected $_normalizationMatrix = array(
		'a'	=> array('á', 'à', 'ã', 'ả', 'ạ', 'ă', 'ắ', 'ằ',
					'ẵ', 'ẳ', 'ặ', 'â', 'ấ', 'ầ', 'ẫ', 'ẩ', 'ậ'),
		'A'	=> array('Á', 'À', 'Ã', 'Ả', 'Ạ', 'Ă', 'Ắ', 'Ằ',
					'Ẵ', 'Ẳ', 'Ặ', 'Â', 'Ấ', 'Ầ', 'Ẫ', 'Ẩ', 'Ậ'),
		'd'	=> array('đ'),
		'D'	=> array('Đ'),
		'e'	=> array('é', 'è', 'ẽ', 'ẻ', 'ẹ', 'ê', 'ế', 'ề', 'ễ', 'ể', 'ệ'),
		'E'	=> array('É', 'È', 'Ẽ', 'Ẻ', 'Ẹ', 'Ê', 'Ế', 'Ề', 'Ễ', 'Ể', 'Ệ'),
		'i'	=> array('í', 'ì', 'ĩ', 'ỉ', 'ị'),
		'I'	=> array('Í', 'Ì', 'Ĩ', 'Ỉ', 'Ị'),
		'o'	=> array('ó', 'ò', 'õ', 'ỏ', 'ọ', 'ô', 'ố', 'ồ', 'ỗ',
					'ổ', 'ộ', 'ơ', 'ớ', 'ờ', 'ỡ', 'ở', 'ợ'),
		'O'	=> array('Ó', 'Ò', 'Õ', 'Ỏ', 'Ọ', 'Ô', 'Ố', 'Ồ', 'Ỗ',
					'Ổ', 'Ộ', 'Ơ', 'Ớ', 'Ờ', 'Ỡ', 'Ở', 'Ợ'),
		'u'	=> array('ú', 'ù', 'ũ', 'ủ', 'ụ', 'ư', 'ứ', 'ừ', 'ữ', 'ử', 'ự'),
		'U'	=> array('Ú', 'Ù', 'Ũ', 'Ủ', 'Ụ', 'Ư', 'Ứ', 'Ừ', 'Ữ', 'Ử', 'Ự'),
		'y'	=> array('ý', 'ỳ', 'ỹ', 'ỷ', 'ỵ'),
		'Y'	=> array('Ý', 'Ỳ', 'Ỹ', 'Ỷ', 'Ỵ'),

		// @TODO: 'tis nasty. how about true double-dimensional dictionary?
		'е'	=> array('ё'),
		'Е'	=> array('Ё'),
	);

}
