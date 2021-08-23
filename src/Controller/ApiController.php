<?php

namespace App\Controller;


use App\Service\UserService;
use Gesdinet\JWTRefreshTokenBundle\Service\RefreshToken;
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
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;

/**
* Class ApiController
 * @package App\Controller
*/

class ApiController extends AbstractController
{

    private UserService $userService;

    public function __construct(UserService $userService){
        $this->userService = $userService;
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
     *     response=200,
     *     description="Register successfull",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="token", type="string"),
     *        @OA\Property(property="refreshToken", type="string"),
     *     )
     * )
     * @OA\Tag(name="user")
     * @Route("/api/v1/auth", name="login", methods={"POST"})
     */
    public function login(): ?Response
    {
    }

    /**
     * @OA\Post (
     * @OA\RequestBody(
     *     request="order",
     *     description="Order data in JSON format",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="refresh_token", type="string"),
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
     *        @OA\Property(property="refreshToken", type="string"),
     *     )
     * )
     * @OA\Tag(name="token")
     * @Route("/api/v1/token/refresh", name="refresh", methods={"POST"})
     */
    public function refresh(Request $request, RefreshToken $refreshService)
    {
        return $refreshService->refresh($request);
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
     *        @OA\Property(property="refreshToken", type="string"),
     *     )
     * )
     * @OA\Tag(name="user")
     * @Route("/api/v1/register", name="register",  methods={"POST"})
     */

    public function register(Request $request, UserPasswordHasherInterface $hash,
                             JWTTokenManagerInterface $JWTManager, ValidatorInterface $validator,
                             EntityManagerInterface $em,
                             RefreshTokenManagerInterface $refreshTokenManager): JsonResponse
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
            $errorsResponse[] = sprintf('Эта почта %s уже используется', $dto->getEmail());
        }

        if (!empty($errorsResponse)) {
            return new JsonResponse($errorsResponse, 500);
        }

        $user = User::fromDto($dto);
        $user->setPassword($hash->hashPassword($user, $dto->getPassword()));
        $em->persist($user);
        $em->flush();

        $refreshToken = $refreshTokenManager->create();
        $refreshToken->setUsername($user->getEmail());
        $refreshToken->setRefreshToken();
        $refreshToken->setValid((new \DateTime())->modify('+1 month'));
        $refreshTokenManager->save($refreshToken);

        $response = [
            'token' => $JWTManager->create($user),
            'refresh_token' => $refreshToken->getRefreshToken(),
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

            $response = $this->userService->UserByToken($token);
            if(is_array($response)){
                return new JsonResponse($response, 400);
            }

            $balance = $response->getBalance();

            return new JsonResponse([
                "username" => $response->getEmail(),
                "roles" => $response->getRoles(),
                "balance" => $balance,
            ], 200);
        } catch (\Exception $exception) {
            return new JsonResponse(["Error" => "Server error"], 500);
        }
    }
}