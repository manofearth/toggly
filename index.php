<?php

define('ROOT_PATH', __DIR__);

require_once ROOT_PATH . '/vendor/autoload.php';

$autoloader = new \Zend\Loader\StandardAutoloader();
$autoloader->registerNamespace('TogglSync', ROOT_PATH);
$autoloader->register();

$config = \Zend\Config\Factory::fromFile(ROOT_PATH . '/config.json');

date_default_timezone_set($config['common']['timezone']);

$toggl = new \TogglSync\Toggl\Gateway(new Zend\Uri\Http('https://www.toggl.com'), $config['toggl']);
$youtrack = new \TogglSync\Youtrack\Gateway($config['youtrack']);

// Check for an overridden syncer. Namespace should be fully-qualified.
if (!empty($config['common']['syncClass']) && class_exists($config['common']['syncClass'])) {
    $syncer = new $config['common']['syncClass']($toggl, $youtrack);
}
else {
    $syncer = new \TogglSync\VastSyncer($toggl, $youtrack);
}

// strtotime()-compatible strings. Use the ones from config.json if we have them.
$fromDate = !empty($config['toggl']['fromDate']) ? $config['toggl']['fromDate'] : 'now';
$toDate = !empty($config['toggl']['toDate']) ? $config['toggl']['toDate'] : 'now';
$syncer->syncForPeriod(
	(new TogglSync\DateTime($fromDate))->setTime(0,0,0),
	(new TogglSync\DateTime($toDate))->setTime(23,59,59)
);
