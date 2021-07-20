<?php


namespace App\Service;


use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;

class UserService
{
    private $em;

    public function __construct(EntityManagerInterface $em){
        $this->em = $em;
    }

    public function UserByToken(string $token)
    {
        $dir = $_ENV['public_key'];
        $public_key = file_get_contents($dir);


        $algorithm = "RS256";
        try {
            $jwt = (array)JWT::decode(
                $token,
                $public_key,
                [$algorithm]
            );
        } catch (\Exception $exception) {
            return ["Error" => "JWT not valid"];
        }

        $user = $this->em->getRepository(User::class)->findOneBy([
            'email' => $jwt['username']
        ]);

        return $user;
    }

}