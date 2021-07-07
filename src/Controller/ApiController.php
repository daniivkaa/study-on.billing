<?php

namespace App\Controller;

use App\Dto\UserDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
    public function register(Request $request, UserPasswordHasherInterface $hash, JWTTokenManagerInterface $JWTManager, ValidatorInterface $validator): Response
    {

        //$dto = new UserDto();

        $serializer = SerializerBuilder::create()->build();

        $userDto = $serializer->deserialize($request->getContent(), UserDto::class, 'json');

        $errors = $validator->validate($userDto);

        $em = $this->getDoctrine()->getManager();
        $email = $em->getRepository(User::class)->findBy(["email" => $userDto->username]);
        //$email = [];
        $errorsResponse = [];
        if (count($email) > 0) {
            $errorsResponse[] = "This email are used";
        }
        if (count($errors) > 0 || count($email) > 0) {
            foreach ($errors as $error) {
                $errorsResponse[] = (string)$error->getMessage();
            }
            return new JsonResponse($errorsResponse, 500);
        }

        $user = User::fromDto($userDto, $hash);
        $em->persist($user);
        $em->flush();
        echo "1";

        $response = [
            'token' => $JWTManager->create($user),
        ];

        return new JsonResponse($response, 200);
    }
}
