<?php
require __DIR__ . '/../vendor/autoload.php';

use Goutte\Client;

// Set default and variable
$client = new Client;
$main_url = 'http://academic.nutc.edu.tw/curriculum/show_subject/show_subject_form.asp?show_vol=1';

// Send request
$main_page = $client->request('GET', $main_url);

// Select the form and send request for get all name of courses
$main_form = $main_page->selectButton('　下　一　步　')->form();
$courses_page = $client->submit($main_form, ['show_radio' => '2']);

// Pring all name of courses
$courses = $courses_page->filter('select > option')->each(function ($course) {
    return $course->attr('value');
});

print_r($courses);
