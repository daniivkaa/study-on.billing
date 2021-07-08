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
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\AuthorizationHeaderTokenExtractor;

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

        $serializer = SerializerBuilder::create()->build();

        $userDto = $serializer->deserialize($request->getContent(), UserDto::class, 'json');

        $em = $this->getDoctrine()->getManager();

        $errorsResponse = [];

        /** @var ConstraintViolation $violation */
        foreach ($validator->validate($userDto) as $violation) {
            $errorsResponse[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
        }

        $users = $em->getRepository(User::class)->findBy([
            'email' => $userDto->getEmail(),
        ]);

        if (count($users) > 0) {
            $errorsResponse[] = sprintf('This email %s are used', $userDto->getEmail());
        }

        if (!empty($errorsResponse)) {
            return new JsonResponse($errorsResponse, 500);
        }

        $user = User::fromDto($userDto, $hash);
        $em->persist($user);
        $em->flush();

        $response = [
            'token' => $JWTManager->create($user),
        ];

        return new JsonResponse($response, 201);
    }

    /**
     * @Route("/api/v1/users/current", name="user")
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
     */
    public function getUserByToken(Request $request, EntityManagerInterface $em): Response
    {
        try {
            $extractor = new AuthorizationHeaderTokenExtractor(
                'Bearer',
                'Authorization'
            );

            $token = $extractor->extract($request);

            $dir = $this->container->get('parameter_bag')->get('public_key');
            $public_key = file_get_contents($dir);

            $algorithm = $this->container->get('parameter_bag')->get('jwt_algorithm');
            try {
                $jwt = (array)JWT::decode(
                    $token,
                    $public_key,
                    [$algorithm]
                );
            } catch (\Exception $exception) {
                return new JsonResponse(["Error" => "JWT not valid"], 400);
            }

            $users = $em->getRepository(User::class)->findOneBy([
                'email' => $jwt['username']
            ]);
            $balance = $users->getBalance();

            return new JsonResponse([
                "username" => $jwt['username'],
                "roles" => $jwt['roles'],
                "balance" => $balance,
            ], 200);
        } catch (\Exception $exception) {
            return new JsonResponse(["error" => "Server error"], 500);
        }
    }
}
