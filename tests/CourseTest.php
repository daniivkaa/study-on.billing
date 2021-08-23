<?php

namespace App\Tests;

use App\DataFixtures\CourseFixtures;
use App\DataFixtures\UserFixtures;

class CourseTest extends AbstractTest
{

    protected function getFixtures(): array
    {
        return [CourseFixtures::class, UserFixtures::class];
    }

    public function testIndex(){
        $client = AbstractTest::getClient();
        $url = "/api/v1/courses";

        $crawler = $client->request("GET", $url);
        $result = json_decode($client->getResponse()->getContent(), true);
        $countCourse = count($result);

        $this->assertEquals(10, $countCourse);
    }

    public function testShow(){
        $client = AbstractTest::getClient();
        $url = "/api/v1/courses/11111";

        $crawler = $client->request("GET", $url);
        $result = json_decode($client->getResponse()->getContent(), true)[0];

        $this->assertEquals(11111, $result["code"]);
        $this->assertEquals(0, $result["type"]);
        $this->assertEquals(0, $result["price"]);
    }

    public function testBuyCourseOk(){
        $client = AbstractTest::getClient();

        $url = '/api/v1/register';

        $client->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],], [], [],
            '{
                "username":"user5@user.com",
                "password":"123456"
            }'
        );
        $response = json_decode($client->getResponse()->getContent(), true);

        $token = $response['token'];

        $url = "/api/v1/deposite";

        $crawler = $client->request("POST", $url, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ], [], [],
            "{
                \"token\":\"$token\",
                \"among\":\"1000\"
            }"
        );

        $url = "/api/v1/courses/55555/pay";

        $crawler = $client->request("POST", $url, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ], [], [],
            "{
                \"token\":\"$token\"
            }"
        );

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals("true", $response["success"]);
    }

    public function testBuyCourseError(){
        $client = AbstractTest::getClient();

        $url = '/api/v1/register';

        $client->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],], [], [],
            '{
                "username":"user5@user.com",
                "password":"123456"
            }'
        );
        $response = json_decode($client->getResponse()->getContent(), true);

        $token = $response['token'];

        $url = "/api/v1/deposite";

        $crawler = $client->request("POST", $url, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ], [], [],
            "{
                \"token\":\"$token\",
                \"among\":\"1000\"
            }"
        );

        $url = "/api/v1/courses/66666/pay";

        $crawler = $client->request("POST", $url, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ], [], [],
            "{
                \"token\":\"$token\"
            }"
        );

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals("Денег нет", $response["Error"]);
    }
}
