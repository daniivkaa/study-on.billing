<?php

namespace App\Tests;

use App\DataFixtures\UserFixtures;
use ApiTestCase\JsonApiTestCase;
use App\Entity\User;


class UserTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [UserFixtures::class];
    }

    public function testRegister(): void
    {
        $client = AbstractTest::getClient();
        $url = '/api/v1/register';

        $crawler = $client->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],], [], [],
            '{
                "username":"user4@user.com",
                "password":"123456"
            }'
        );
        $this->assertResponseCode(201);
    }

    public function testRegisterError(): void
    {
        $client = AbstractTest::getClient();
        $url = '/api/v1/register';

        $crawler = $client->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json'
            ],], [], [],
            '{
                "username":"notvalidemail",
                "password":"123456"
            }'
        );
        $this->assertResponseCode(500);
    }


    public function testCurrentUser(){
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

        $client->request('GET', '/api/v1/users/current', [], [], ['HTTP_AUTHORIZATION' => 'Bearer '. $response['token']]);

        $this->assertResponseCode(200);
    }

    public function testCurrentUserErrorJwt(){
        $client = AbstractTest::getClient();
        $url = '/api/v1/users/current';

        $notValidToken = "notvalidtoken";
        $client->request('GET', $url, [], [], ['HTTP_AUTHORIZATION' => 'Bearer '. $notValidToken]);

        $this->assertResponseCode(400);
    }
}
