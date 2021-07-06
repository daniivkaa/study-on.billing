<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    /**
     * @Route("/api/v1/auth", name="api")
     */
    public function login(): Response
    {

    }

    /**
     * @Route("/api/v1/register", name="register")
     */
    public function register(): Response
    {
        $userDto = $this->deserialize($request, \App\Model\Request\User::class);
        $errors = $validator->validate($userDto);

        $user = \App\Entity\User::fromDto($userDto);
    }
}
