<?php
session_start();

require_once  'vendor/autoload.php';

$config = [
    'favicon' => '🚀',
    'title' => 'JsonTinker',
    'description' => 'A simple editor for the json files.',
    'version' => '1.0.0b',
    'visible' => false,
    'keys' => []
];

require_once 'JsonFile.php';
require_once 'Helper.php';
