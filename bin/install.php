#!/usr/bin/env php
<?php
use Shlinkio\Shlink\CLI\Command\Install\InstallCommand;
use Symfony\Component\Console\Application;
use Zend\Config\Writer\PhpArray;

chdir(dirname(__DIR__));

require __DIR__ . '/../vendor/autoload.php';

$app = new Application();
$app->add(new InstallCommand(new PhpArray()));
$app->setDefaultCommand('shlink:install');
$app->run();