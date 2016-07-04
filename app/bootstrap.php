<?php

require __DIR__ . '/../vendor/autoload.php';

$configurator = new Nette\Configurator;

define('WWW_DIR', __DIR__);

//$configurator->setDebugMode('23.75.345.200'); // enable for your remote IP
//$configurator->setDebugMode(TRUE); // enables TRACY
$configurator->enableDebugger(__DIR__ . '/../log');
$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->createRobotLoader()
	->addDirectory(__DIR__)
	->register();

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');

$container = $configurator->createContainer();

RadekDostal\NetteComponents\DateTimePicker\DatePicker::register();
RadekDostal\NetteComponents\DateTimePicker\DateTimePicker::register();
RadekDostal\NetteComponents\DateTimePicker\TbDatePicker::register();
RadekDostal\NetteComponents\DateTimePicker\TbDateTimePicker::register();

return $container;
