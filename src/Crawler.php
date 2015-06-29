<?php
require __DIR__ . '/../vendor/autoload.php';

use Goutte\Client;
use Symfony\Component\BrowserKit\Cookie;

// Initialize
$start_time = time();
$client = new Client;
$main_url = 'http://academic.nutc.edu.tw/curriculum/show_subject/show_subject_form.asp?show_vol=1';
$courses_detail_url = 'http://academic.nutc.edu.tw/curriculum/show_subject/show_subject_choose.asp';

// Send request
$main_page = $client->request('GET', $main_url);

// Select the form and send request for get all name of courses
$main_form = $main_page->selectButton('　下　一　步　')->form();
$courses_page = $client->submit($main_form, ['show_radio' => '2']);

// get all name of courses
$courses = $courses_page->filter('select > option')->each(function ($course) {
    return $course->attr('value');
});

foreach ($courses as $course) {
    // test once
    $cookie = new Cookie('show%5Fselect', iconv('UTF-8', 'big5', $course));
    $client->getCookieJar()->set($cookie);
    $course_detail = $client->request('post', $courses_detail_url);

    // parsing data of course
    $course_node = $course_detail->filter('tr');
    $node_num = count($course_node) - 1;

    // Print each data of course
    $course_node->each(function ($course, $i) use ($node_num) {
        // When the data that not first and last
        if (($i != 0) and ($i != $node_num)) {
            // Parse data of course
            $course_arr = $course->filter('td font')->each(function ($node, $i) {
                // filter the suck code of data
                if ($i == 13) {
                    return null;
                // Get the uri of 教學大綱
                // full url: http://academic.nutc.edu.tw/registration/
                // next_teach_flow/rot_show_teach_flow.asp?flow_no=xxxxxxxxxxx
                } elseif ($i == 11) {
                    $url = $node->selectLink('教學大綱')->link()->getUri();
                    return substr($url, 89);
                }
                return $node->text();
            });

            print join("\t", $course_arr);
            print "\n";
        }
    });
}

var_dump(time() - $start_time);
