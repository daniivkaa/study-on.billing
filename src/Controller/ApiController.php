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
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Firebase\JWT\JWT;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

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
     */
    public function getUserByToken(UserProviderInterface $userProvider, Request $request, EntityManagerInterface $em, ContainerBagInterface $params): Response
    {
        try {
            $credentials = $request->headers->get('Authorization');
            $credentials = str_replace('Bearer ', '', $credentials);
            $tokenParts = explode(".", $credentials);
            $tokenHeader = base64_decode($tokenParts[0]);
            $tokenPayload = base64_decode($tokenParts[1]);

            $jwtHeader = json_decode($tokenHeader);
            $jwtPayload = json_decode($tokenPayload);

            $public_key = "-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA5U17kstwZ1xlwrHGuaZ2
9x4W2qAIRT91mQo7zVluwRObJlmzDQgBHDZihYaWTZnrKhOXhxlnR88ul562NiGr
k1s9e21cn7iZRjnPGrCKtHBz+kGnKaspDT3J4L0q6x/EJZCgJBLl9VRVsabJkZQA
AyAQuqNCVJIASjpRl+7W1/bC+E20Qlm2taDjgUR8YQuFeN7eeDBrbluA7m8I6OtB
SOJLYnwD1NBHfLVMZSSEp6WLYZlyWP4gD8YQqxB647+jpNCTBdXLENn36ogpYRBz
oTAfFOtdmAZyysXWoLUiRzZ3AW+H2aFYDxDw8MOmHgeK+uRU54BU8Q8GmFGZmZFR
Hsw0cHqpiKIAL5Jw93gS+jZMYliPWpOJu/71fBAECZ4Wih0WM66PifblySmoFBGA
mTez71QxBhj12SYzxx4Bp2iyWiX4e6+hoKcZxoT1VgTd4BZBMDMtDrbhwMnECNC7
6PtBIxEWJ1Xus/eSnk/B5RDqSxjoHHPqQ2Ln7vqLuXRh0OJ7j5PKFEk7Jchwt3NJ
gSx0GSYM+EOWpriECs43vsNn2VujKjX9S0hT9QFtwh1UvxfYQ3H/a9zJeRPckTN+
V36qauUjJudFpxQmGEoiRu9XfiCi3WQPmcdPyJkKtPu0vLeXsAcX0DkaRh+7qa2r
p/vcpalpB1k46g5G75hPgLcCAwEAAQ==
-----END PUBLIC KEY-----";

            $jwt = (array)JWT::decode(
                $credentials,
                $public_key,
                ['RS256']
            );

            $users = $em->getRepository(User::class)->findOneBy([
                'email' => $jwtPayload->username,
            ]);
            $balance = $users->getBalance();

            return new JsonResponse([
                "username" => $jwtPayload->username,
                "roles" => $jwtPayload->roles,
                "balance" => $balance,
            ]);
        } catch (\Exception $exception) {
            return new JsonResponse(["error" => "error"], 200);
        }
    }
}
