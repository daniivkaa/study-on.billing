<?php

namespace App\Tests;

use App\DataFixtures\CourseFixtures;
use App\DataFixtures\Test\TransactionFixture;
use App\DataFixtures\UserFixtures;

class TransactionTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [CourseFixtures::class, UserFixtures::class, TransactionFixture::class];
    }

    public function testDeposite(){
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

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals("true", $response["success"]);
    }
}
