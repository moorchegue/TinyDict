#!/bin/php
<?php

$paths = array(
	get_include_path(),
	realpath(dirname(__FILE__) . '/../'),
);

set_include_path(implode(PATH_SEPARATOR, $paths));

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

$config['input'] = array_pop($argv);

// run
$out = '';
$class = 'TinyDict' . $config['class'];
$action = $config['action'];

$dict = new $class($config['input'], $config['tags']);

$out = $dict->$action();

echo $out;
