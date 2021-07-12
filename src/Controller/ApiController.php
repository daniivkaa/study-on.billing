<?php

namespace App\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\AuthorizationHeaderTokenExtractor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\User;
use App\Dto\UserDto;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use JMS\Serializer\SerializerBuilder;
use Firebase\JWT\JWT;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Doctrine\ORM\EntityManagerInterface;

/**
* Class ApiController
 * @package App\Controller
*/

class ApiController extends AbstractController
{

    /**
     * @OA\Post (
     * @OA\RequestBody(
     *     request="order",
     *     description="Order data in JSON format",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="username", type="string"),
     *        @OA\Property(property="password", type="string"),
     *     ),
     * ),
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Register successfull",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="token", type="string"),
     *     )
     * )
     * @OA\Tag(name="user")
     * @Route("/api/v1/auth", name="login", methods={"POST"})
     */
    public function login(): Response
    {
    }

    /**
     * @OA\Post (
     * @OA\RequestBody(
     *     request="order",
     *     description="Order data in JSON format",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="username", type="string"),
     *        @OA\Property(property="password", type="string"),
     *     ),
     * ),
     * )
     *
     * @OA\Response(
     *     response=201,
     *     description="Register successfull",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="token", type="string"),
     *     )
     * )
     * @OA\Tag(name="user")
     * @Route("/api/v1/register", name="register",  methods={"POST"})
     */

    public function register(Request $request, UserPasswordHasherInterface $hash, JWTTokenManagerInterface $JWTManager, ValidatorInterface $validator, EntityManagerInterface $em): JsonResponse
    {
        $serializer = SerializerBuilder::create()->build();
        $dto = $serializer->deserialize($request->getContent(), UserDto::class, 'json');

        $errorsResponse = [];

        /* @var ConstraintViolation $violation */
        foreach ($validator->validate($dto) as $violation) {
            $errorsResponse[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
        }

        $users = $em->getRepository(User::class)->findBy([
            'email' => $dto->getEmail(),
        ]);

        if (count($users) > 0) {
            $errorsResponse[] = sprintf('This email %s are used', $dto->getEmail());
        }

        if (!empty($errorsResponse)) {
            return new JsonResponse($errorsResponse, 500);
        }

        $user = User::fromDto($dto);
        $user->setPassword($hash->hashPassword($user, $dto->getPassword()));
        $em->persist($user);
        $em->flush();

        $response = [
            'token' => $JWTManager->create($user),
        ];

        return new JsonResponse($response, 201);
    }

    /**
     * @OA\Response(
     *     response=200,
     *     description="Register successfull",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="username", type="string"),
     *        @OA\Property(property="role", type="string"),
     *        @OA\Property(property="balance", type="string")
     *     )
     * )
     * @Security(name="bearerAuth")
     * @OA\Tag(name="user")
     * @Route("/api/v1/users/current", name="user", methods = "GET")
     */
    public function getUserByToken(Request $request, EntityManagerInterface $em): Response
    {
        try {
            $extractor = new AuthorizationHeaderTokenExtractor(
                'Bearer',
                'Authorization'
            );
            $token = $extractor->extract($request);

            $dir = $this->container->get('parameter_bag')->get('jwt_public_key');
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
            return new JsonResponse(["Error" => "Server error"], 500);
        }
    }
}