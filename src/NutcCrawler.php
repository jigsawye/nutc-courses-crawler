<?php

namespace AbysmalCamp;

use Goutte\Client;
use Symfony\Component\BrowserKit\Cookie;

class NutcCrawler
{
    private $start_time;

    private $client = null;

    private $urls = [
        'DAY' => [
            'base' => 'http://academic.nutc.edu.tw/curriculum/',
            'params' => 'show_vol=',
        ],
        'NIGHT' => [
            'base' => 'http://night.nutc.edu.tw/student_database/',
            'menu' => 'a=',
        ],
        'preselect' => [
            'NO' => 'show_subject/',
            'YES' => 'next_show_subject/',
        ],
        'menu' => 'show_subject_form.asp?',
        'detail' => 'show_subject_choose.asp',
    ];

    private $menu_url;

    private $detail_url;

    private $courses_list_page;

    private $courses_list = [];

    public function __construct($type, $preselect, $semester)
    {
        $this->start_time = time();

        $this->setUrls($type, $preselect, $semester);

        $this->client = new Client;

        print "Day of Night: $type\n";
        print "Preselect: $preselect\n";
        print "Semester: $semester\n";
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
                // When the data that not first and last
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

        return $course_arr;
    }

    private function setCourseToCookie($course)
    {
        $cookie = new Cookie('show%5Fselect', iconv('UTF-8', 'big5', $course));
        $this->client->getCookieJar()->set($cookie);
    }

    private function setUrls($type, $preselect, $semester)
    {
        $base_url = $this->urls[$type]['base'] .
            $this->urls['preselect'][$preselect];

        $this->menu_url = $base_url .
            $this->urls['menu'] .
            $this->urls[$type]['params'] .
            $semester;

        $this->detail_url = $base_url .
            $this->urls['detail'];
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
