<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private $hash;

    public function __construct(UserPasswordHasherInterface $hash)
    {
        $this->hash = $hash;
        $this->balance = 0;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('user@user.com');
        $user->setRoles(["ROLE_USER"]);
        $password = $this->hash->hashPassword($user, '123456');
        $user->setPassword($password);
        $user->setBalance(100);
        $manager->persist($user);

        $admin = new User();
        $admin->setEmail('admin@admin.com');
        $admin->setRoles(["ROLE_SUPER_ADMIN"]);
        $password = $this->hash->hashPassword($admin, '123456');
        $admin->setPassword($password);
        $admin->setBalance(10000);
        $manager->persist($admin);

        $manager->flush();
    }
}
