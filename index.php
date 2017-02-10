<?php

date_default_timezone_set('Asia/Yakutsk');

define('ROOT_PATH', __DIR__);

require_once ROOT_PATH . '/vendor/autoload.php';

$autoloader = new \Zend\Loader\StandardAutoloader();
$autoloader->registerNamespace('TogglSync', ROOT_PATH);
$autoloader->register();

$config = \Zend\Config\Factory::fromFile(ROOT_PATH . '/config.json');

$toggl = new \TogglSync\Toggl\Gateway(new Zend\Uri\Http('https://www.toggl.com'), $config['toggl']);
$youtrack = new \TogglSync\Youtrack\Gateway($config['youtrack']);

$syncer = new \TogglSync\VastSyncer($toggl, $youtrack);
$syncer->syncForPeriod(
	(new TogglSync\DateTime('now'))->setTime(0,0,0),
	(new TogglSync\DateTime('now'))->setTime(23,59,59)
);
