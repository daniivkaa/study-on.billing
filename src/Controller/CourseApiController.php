<?php

namespace App\Controller;

use App\Dto\CourseDto;
use App\Entity\Course;
use App\Entity\Transaction;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\DBAL\Exception;

class CourseApiController extends AbstractController
{

    private UserService $userService;

    public function __construct(UserService $userService){
        $this->userService = $userService;
    }

    /**
     *  @OA\Parameter(
     *     name="token",
     *     in="header",
     *     description="The field used to order rewards"
     * )
     * @OA\Tag(name="course")
     * @Route("/api/v1/courses", name="courses_api", methods = "GET")
     */
    public function index(Request $request, EntityManagerInterface $em): Response
    {
            $courses = $em->getRepository(Course::class)->findAll();
            $courseForRequest = [];
            foreach($courses as $course){
                $courseForRequest[] = [
                    "code" => $course->getCode(),
                    "type" => $course->getType(),
                    "price" => $course->getPrice(),
                ];
            }
            return new JsonResponse($courseForRequest, 200);
    }


    /**
     *  @OA\Parameter(
     *     name="token",
     *     in="header",
     *     description="The field used to order rewards"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Course show",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="code", type="string"),
     *        @OA\Property(property="type", type="smallint"),
     *        @OA\Property(property="price", type="float"),
     *     )
     * )
     * @OA\Tag(name="course")
     * @Route("/api/v1/courses/{code}", name="courses_by_code", methods = "GET")
     */
    public function showCoursesByCode(Request $request, EntityManagerInterface $em): Response
    {
        $code =  $request->attributes->get(['_route_params'][0])['code'];

            $courses = $em->getRepository(Course::class)->findBy(['code' => $code]);
            $courseForRequest = [];
            foreach($courses as $course){
                $courseForRequest[] = [
                    "code" => $course->getCode(),
                    "type" => $course->getType(),
                    "price" => $course->getPrice(),
                ];
            }
            return new JsonResponse($courseForRequest, 200);
    }

    /**
     * @OA\RequestBody(
     *     request="order",
     *     description="Order data in JSON format",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="token", type="string"),
     *        @OA\Property(property="code", type="string"),
     *        @OA\Property(property="type", type="smallint"),
     *        @OA\Property(property="price", type="float"),
     *     ),
     * ),
     *
     * @OA\Response(
     *     response=201,
     *     description="Course created",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="message", type="string"),
     *     )
     * )
     * @OA\Tag(name="course")
     * @Route("/api/v1/courses/create", name="course_create",  methods={"POST"})
     */
    public function createCourse(Request $request, ValidatorInterface $validator, EntityManagerInterface $em)
    {
        $serializer = SerializerBuilder::create()->build();
        $dto = $serializer->deserialize($request->getContent(), CourseDto::class, 'json');

        $errorsResponse = [];

        /* @var ConstraintViolation $violation */
        foreach ($validator->validate($dto) as $violation) {
            $errorsResponse[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
        }

        $course = $em->getRepository(Course::class)->findBy([
            'code' => $dto->getCode(),
        ]);

        $token = $dto->getToken();

        $response = $this->userService->UserByToken($token);
        if(is_array($response)){
            return new JsonResponse($response, 500);
        }

        if(!in_array('ROLE_SUPER_ADMIN', $response->getRoles())){
            $errorsResponse['message'] = 'У вас недостаточно прав!';
        }

        if (count($course) > 0) {
            $errorsResponse['message'] = sprintf('Курс с таким кодом %s уже существует', $dto->getCode());
        }

        if (!empty($errorsResponse)) {
            return new JsonResponse($errorsResponse, 500);
        }



        $course = Course::fromDto($dto);

        $em->persist($course);
        $em->flush();

        $response = [
            'message' => 'Create course',
        ];

        return new JsonResponse($response, 201);
    }


    /**
     * @OA\RequestBody(
     *     request="order",
     *     description="Order data in JSON format",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="token", type="string"),
     *        @OA\Property(property="code", type="string"),
     *        @OA\Property(property="type", type="smallint"),
     *        @OA\Property(property="price", type="float"),
     *     @OA\Property(property="title", type="string"),
     *     ),
     * ),
     *
     * @OA\Response(
     *     response=201,
     *     description="Course created",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="message", type="string"),
     *     )
     * )
     * @OA\Tag(name="course")
     * @Route("/api/v1/courses/edit/{code}", name="course_edit",  methods={"POST"})
     */
    public function editCourse(Request $request, ValidatorInterface $validator, EntityManagerInterface $em)
    {
        $serializer = SerializerBuilder::create()->build();
        $dto = $serializer->deserialize($request->getContent(), CourseDto::class, 'json');

        $errorsResponse = [];

        /* @var ConstraintViolation $violation */
        foreach ($validator->validate($dto) as $violation) {
            $errorsResponse[] = sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage());
        }

        $token = $dto->getToken();

        $response = $this->userService->UserByToken($token);
        if(is_array($response)){
            return new JsonResponse($response, 500);
        }

        if(!in_array('ROLE_SUPER_ADMIN', $response->getRoles())){
            $errorsResponse['message'] = 'У вас недостаточно прав!';
        }

        $code =  $request->attributes->get(['_route_params'][0])['code'];
        if($code != $dto->getCode()) {
            $course = $em->getRepository(Course::class)->findBy([
                'code' => $dto->getCode(),
            ]);

            if (count($course) > 0) {
                $errorsResponse['message'] = sprintf('Курс с таким кодом %s уже существует', $dto->getCode());
            }
        }

        if (!empty($errorsResponse)) {
            return new JsonResponse($errorsResponse, 500);
        }


        $course = $em->getRepository(Course::class)->findOneBy([
            'code' => $code,
        ]);

        $course = Course::fromDto($dto, $course);

        $em->persist($course);
        $em->flush();

        $response = [
            'message' => 'Edit course',
        ];

        return new JsonResponse($response, 201);
    }

    /**
     * @OA\RequestBody(
     *     request="order",
     *     description="Order data in JSON format",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="token", type="string"),
     *     ),
     * ),
     *
     * @OA\Response(
     *     response=201,
     *     description="Course created",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="message", type="string"),
     *     )
     * )
     * @OA\Tag(name="course")
     * @Route("/api/v1/courses/delete/{code}", name="course_delete",  methods={"POST"})
     */
    public function deleteCourse(Request $request, EntityManagerInterface $em): Response
    {
        $trans = json_decode($request->getContent(), true);
        $code =  $request->attributes->get(['_route_params'][0])['code'];

        $response = $this->userService->UserByToken($trans['token']);

        if(is_array($response)){
            return new JsonResponse($response, 500);
        }

        if(!in_array('ROLE_SUPER_ADMIN', $response->getRoles())){
            $errorsResponse['message'] = 'У вас недостаточно прав!';
            return new JsonResponse($errorsResponse, 500);
        }

        if ($response->getEmail() !== null){
            $course = $em->getRepository(Course::class)->findOneBy([
                'code' => $code,
            ]);
            $em->remove($course);
            $em->flush();

            return new JsonResponse(["Message" => "Курс удален"], 201);
        }

        return new JsonResponse(["Error" => "Неизвестная ошибка"], 500);
    }


    /**
     * @OA\RequestBody(
     *     request="order",
     *     description="Order data in JSON format",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="token", type="string"),
     *     ),
     * ),
     *
     * @OA\Response(
     *     response=201,
     *     description="Course created",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="message", type="string"),
     *     )
     * )
     * @OA\Tag(name="course")
     * @Route("/api/v1/courses/{code}/pay", name="course_pay",  methods={"POST"})
     */
    public function payCourse(Request $request, EntityManagerInterface $em): Response
    {
        $token = json_decode($request->getContent(), true)["token"];
        $code =  $request->attributes->get(['_route_params'][0])['code'];

        $response = $this->userService->UserByToken($token);

        if(is_array($response)){
            return new JsonResponse($response, 500);
        }

        if ($response->getEmail() !== null){
            $course = $em->getRepository(Course::class)->findOneBy(['code' => $code]);

            if($course->getType() === 0){
                return new JsonResponse(["Error" => "Курс бесплатен, его не возможно купить"], 200);
            }

            if($course->getPrice() > $response->getBalance()){
                return new JsonResponse(["Error"=> "Денег нет"], 406);
            }

            $transactionExist = $em->getRepository(Transaction::class)->findOneBy(['course' => $course, 'billing_user' => $response]);

            if($transactionExist){
                if($course->getType() === 1){
                    return new JsonResponse(["Error" => "Курс уже куплен, невозможно оплатить повторно"], 200);
                }
                if($transactionExist->getPayTime() > new \DateTime('now')){
                    return new JsonResponse(["Error" => "Аренда ещё не истекла, невозможно оплатить повторно"], 200);
                }
            }

            $payTime = (new \DateTime())->modify('+1 month');
            $em->getConnection()->beginTransaction();

            try {
                $courseType = "buy";
                $transaction = new Transaction();
                $transaction->setBillingUser($response);
                $transaction->setCourse($course);
                $transaction->setValue($course->getPrice());
                $transaction->setOperationType(0);
                $transaction->setCreatedAt(new \DateTime('now'));
                if($course->getType() === 2){
                    $courseType = "rent";
                    $transaction->setPayTime((new \DateTime())->modify('+1 month'));
                }
                $em->persist($transaction);

                $newBalance = $response->getBalance() - $transaction->getValue();
                $response->setBalance($newBalance);
                $em->persist($response);

                $em->flush();
                $em->getConnection()->commit();

                $response = [
                    'success' => 'true',
                    'course_type' => $courseType,
                    'expires_at' => $payTime
                ];

                return new JsonResponse($response, 200);
            }
            catch(Exception $e){
                $em->getConnection()->rollBack();
                return new JsonResponse(["Error" => "Невозможно купить курс"], 500);
            }
        }

        return new JsonResponse(["Error" => "Error"], 200);
    }

    /**
     * @OA\Parameter(
     *     name="token",
     *     in="header",
     *     description="The field used to order rewards"
     * )
     * @OA\Parameter(
     *     name="filter[type]",
     *     in="query",
     *     description="The field used to order rewards"
     * )
     * @OA\Parameter(
     *     name="filter[course_code]",
     *     in="query",
     *     description="The field used to order rewards"
     * )
     * @OA\Parameter(
     *     name="filter[skip_expired]",
     *     in="query",
     *     description="The field used to order rewards"
     * )
     *
     * @OA\Response(
     *     response=201,
     *     description="Course created",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="message", type="string"),
     *     )
     * )
     * @OA\Tag(name="transaction")
     * @Route("/api/v1/transactions", name="transactions",  methods={"GET"})
     */
    public function getTransactions(Request $request, EntityManagerInterface $em): Response
    {
        $filters = $request->query->all();

        $token = $request->headers->get("token");
        return new Response($token, 200);

        $response = $this->userService->UserByToken($token);

        if(is_array($response)){
            return new JsonResponse($response, 500);
        }

        if ($response->getEmail() !== null){
            $filters['filter']['user'] = $response;

            $transactions = $em->getRepository(Transaction::class)->findByFilters($filters['filter'], $em);

            $trunsForRequest = [];
            foreach($transactions as $key => $transaction){
                $trunsForRequest[$key] = [
                    "id" => $transaction->getId(),
                    "created_at" => $transaction->getCreatedAt(),
                    "type" => $transaction->getOperationType(),
                    "amount" => $transaction->getValue(),
                ];
                if($transaction->getOperationType() == 0){
                    $trunsForRequest[$key]['course_code'] = $transaction->getCourse()->getCode();
                }
            }
            return new JsonResponse($trunsForRequest, 200);
        }
        return new JsonResponse(["Error" => "Error"], 500);
    }

    /**
     * @OA\RequestBody(
     *     request="order",
     *     description="Order data in JSON format",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="token", type="string"),
     *        @OA\Property(property="among", type="integer"),
     *     ),
     * ),
     *
     * @OA\Response(
     *     response=201,
     *     description="Course created",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="message", type="string"),
     *     )
     * )
     * @OA\Tag(name="user")
     * @Route("/api/v1/deposite", name="deposite",  methods={"POST"})
     */
    public function deposite(Request $request, EntityManagerInterface $em): Response
    {
        $token = json_decode($request->getContent(), true)["token"];
        $among =  json_decode($request->getContent(), true)["among"];

        $response = $this->userService->UserByToken($token);

        if(is_array($response)){
            return new JsonResponse($response, 500);
        }

        if ($response->getEmail() !== null){
            $em->getConnection()->beginTransaction();

            try{
                $transaction = new Transaction();
                $transaction->setBillingUser($response);
                $transaction->setValue($among);
                $transaction->setOperationType(1);
                $transaction->setCreatedAt(new \DateTime('now'));
                $em->persist($transaction);

                $newBalance = $response->getBalance() + $among;
                $response->setBalance($newBalance);
                $em->persist($response);

                $em->flush();
                $em->getConnection()->commit();

                $response = [
                    'success' => 'true',
                    'course_type' => 'depos',
                    'created_at' => new \DateTime('now')
                ];

                return new JsonResponse($response, 200);
            }
            catch(Exception $e) {
                $em->getConnection()->rollBack();
                return new JsonResponse(["Error" => "Невозможно произвести операцию"], 200);
            }
        }

        return new JsonResponse(["Error" => "Error"], 200);
    }



    /**
     * @OA\RequestBody(
     *     request="order",
     *     description="Order data in JSON format",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="token", type="string"),
     *     ),
     * ),
     *
     * @OA\Response(
     *     response=201,
     *     description="Course created",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="message", type="string"),
     *     )
     * )
     * @OA\Tag(name="course")
     * @Route("/api/v1/courses/check/{code}", name="course_check",  methods={"POST"})
     */
    public function checkCourse(Request $request, EntityManagerInterface $em): Response
    {
        $token = json_decode($request->getContent(), true)["token"];
        $code =  $request->attributes->get(['_route_params'][0])['code'];

        $response = $this->userService->UserByToken($token);

        if(is_array($response)){
            return new JsonResponse($response, 500);
        }

        if ($response->getEmail() !== null){
            $course = $em->getRepository(Course::class)->findOneBy(['code' => $code]);

            if($course->getType() === 0){
                return new JsonResponse(["message" => "Курс бесплатен", "check" => true], 200);
            }

            $transactionExist = $em->getRepository(Transaction::class)->findOneBy(['course' => $course, 'billing_user' => $response]);

            if($transactionExist){
                if($course->getType() === 1){
                    return new JsonResponse(["message" => "Курс куплен", "check" => true], 200);
                }
                if($transactionExist->getPayTime() > new \DateTime('now')){
                    return new JsonResponse(["message" => "Аренда ещё не истекла", "check" => true], 200);
                }
            }

            if(!$transactionExist){
                if($course->getType() === 1){
                    return new JsonResponse(["message" => "Курс не куплен", "check" => false], 200);
                }
                if($course->getType() === 2){
                    return new JsonResponse(["message" => "Курс не арендован", "check" => false], 200);
                }
           }

        }

        return new JsonResponse(["Error" => "Error"], 500);
    }


}
