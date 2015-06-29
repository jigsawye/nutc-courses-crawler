<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/NutcCoursesCrawler.php';

$crawler = new src\NutcCoursesCrawler('DAY', 'NO', 1);

$crawler->run();
