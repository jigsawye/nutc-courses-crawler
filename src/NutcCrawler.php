<?php

namespace AbysmalCamp;

use Goutte\Client;
use Symfony\Component\BrowserKit\Cookie;

class NutcCrawler
{
    private $start_time;

    private $client = null;

    private $menu_url;

    private $detail_url;

    private $courses_list_page;

    private $courses_list = [];

    public function __construct($type, $preselect, $semester)
    {
        $this->start_time = time();

        $this->setUrls($type, $preselect, $semester);

        $this->client = new Client;

        print "Type: $type\n";
        print "Preselect: $preselect\n";
        print "Semester: $semester\n";
        print "Menu url: $this->menu_url\n";
        print "Detail url: $this->detail_url\n";
    }

    public function run()
    {
        $this->courses_list_page = $this->getCoursesListPage();

        $this->courses_list = $this->getCoursesList();

        $this->parseCourses();

        $this->printUseTime();
    }

    private function printUseTime()
    {
        print 'Use time: ';
        print time() - $this->start_time;
        print " second.\n";
    }

    private function parseCourses()
    {
        foreach ($this->courses_list as $course) {
            $this->setCourseToCookie($course);
            $course_detail = $this->client->request('post', $this->detail_url);

            // parsing data of course
            $course_node = $course_detail->filter('tr');
            $node_num = count($course_node) - 1;

            // Print each data of course
            $course_node->each(function ($course, $i) use ($node_num) {
                // When the data is not first and last
                if (($i != 0) and ($i != $node_num)) {
                    $course_arr = $this->courseDataFormat($course);

                    print join("\t", $course_arr) . "\n";
                }
            });
        }
    }

    private function courseDataFormat($course)
    {
        // Parse data of course
        $course_arr = $course->filter('td font')->each(function ($node, $i) {
            $data = null;

            switch ($i) {
                case 11:
                    // Get the id of 教學大綱
                    // full url: http://academic.nutc.edu.tw/registration/
                    // next_teach_flow/rot_show_teach_flow.asp?flow_no=xxxxxxxxxxx
                    $url = $node->selectLink('教學大綱')->link()->getUri();
                    $data = substr($url, 89);
                    break;
                case 13:
                    break;
                default:
                    $data = $node->text();
                    break;
            }

            return $data;
        });

        return $course_arr;
    }

    private function setCourseToCookie($course)
    {
        $cookie = new Cookie('show%5Fselect', iconv('UTF-8', 'big5', $course));
        $this->client->getCookieJar()->set($cookie);
    }

    private function setUrls($type, $preselect, $semester)
    {
        $urls = [
            'DAY' => [
                'sub_domain' => 'academic',
                'folder' => 'curriculum',
                'params' => 'show_vol',
            ],
            'NIGHT' => [
                'sub_domain' => 'night',
                'folder' => 'student_database',
                'params' => 'a',
            ],
        ];

        $base = 'http://%s.nutc.edu.tw/%s/%sshow_subject/';

        $base_url = sprintf(
            $base,
            $urls[$type]['sub_domain'],
            $urls[$type]['folder'],
            $preselect ? 'next_' : ''
        );

        $this->menu_url = $base_url . sprintf(
            'show_subject_%s.asp?%s=%d',
            'form',
            $urls[$type]['params'],
            $semester
        );

        $this->detail_url = $base_url . 'show_subject_choose.asp';
    }

    private function getCoursesListPage()
    {
        // Send request
        $main_page = $this->client->request('GET', $this->menu_url);

        // Select the form and send request for get all name of courses
        $main_form = $main_page->selectButton('　下　一　步　')->form();

        $courses_list_page = $this->client->submit($main_form, ['show_radio' => '2']);

        return $courses_list_page;
    }

    private function getCoursesList()
    {
        // get all name of courses
        $courses_list = $this->courses_list_page->filter('select > option')->each(function ($course) {
            return $course->attr('value');
        });

        return $courses_list;
    }
}
