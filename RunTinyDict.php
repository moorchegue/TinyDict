#!/usr/bin/php
<?php

$paths = array(
	get_include_path(),
	realpath(dirname(__FILE__) . '/../'),
);

set_include_path(implode(PATH_SEPARATOR, $paths));
mb_internal_encoding('UTF-8');
date_default_timezone_set(@date_default_timezone_get());

require_once 'Launcher/SimpleLauncher.php';
require_once 'TinyDict/TinyDictViet.php';

// параметры
$config = array();
$params = array(
	'i|input'	=> '',
	't|tags'	=> '',
	'c|class'	=> 'Viet',
	'a|action'	=> 'run',
);

// обработать консольный ввод
$slConf = new SimpleLauncher;
$config = $slConf->getConfig($params, $argv);

unset($argv[0]);
$inputWords = array();
while(count($argv)) {
	$word = array_pop($argv);
	if (substr($word, 0, 1) == '-') {
		break;
	}
	$inputWords[] = $word;
}
$config['input'] = implode(' ', array_reverse($inputWords));

// run
$out = '';
$class = 'TinyDict' . $config['class'];
$action = $config['action'];

$dict = new $class($config['input'], $config['tags']);

$out = $dict->$action();

echo $out;
