<?php

namespace App\DataFixtures\Test;

use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;


class TransactionFixture extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $user = $manager->getRepository(User::class)->findOneBy(['email' => 'user@user.com']);
        $admin = $manager->getRepository(User::class)->findOneBy(['email' => 'admin@admin.com']);

        $couses = $manager->getRepository(Course::class)->findAll();

        foreach($couses as $course){
            if($course->getType() == 1){
                $manager->persist($this->buyCourse($user, $course));

                $manager->persist($this->buyCourse($admin, $course));
            }
            else if($course->getType() == 2){
                for($i = 0; $i < 3; $i++) {
                    $manager->persist($this->rentCourse($i, $user, $course));

                    $manager->persist($this->rentCourse($i, $admin, $course));
                }
            }
        }

        $manager->flush();
    }

    public function rentCourse($i, $user, $course){
        if($i == 1) {
            $transaction = new Transaction();
            $transaction->setBillingUser($user);
            $transaction->setCourse($course);
            $transaction->setCreatedAt((new \DateTime())->modify('-1 month'));
            $transaction->setPayTime((new \DateTime())->modify('+1 month'));
            $transaction->setOperationType(0);
            $transaction->setValue($course->getPrice());

            return $transaction;
        }
        if($i == 2) {
            $transaction = new Transaction();
            $transaction->setBillingUser($user);
            $transaction->setCourse($course);
            $transaction->setCreatedAt(new \DateTime('now'));
            $transaction->setPayTime((new \DateTime())->modify('+1 day'));
            $transaction->setOperationType(0);
            $transaction->setValue($course->getPrice());

            return $transaction;
        }
        else{
            $transaction = new Transaction();
            $transaction->setBillingUser($user);
            $transaction->setCourse($course);
            $transaction->setCreatedAt(new \DateTime('now'));
            $transaction->setPayTime((new \DateTime())->modify('+1 month'));
            $transaction->setOperationType(0);
            $transaction->setValue($course->getPrice());

            return $transaction;
        }
    }

    public function buyCourse($user, $course){
        $transaction = new Transaction();
            $transaction->setBillingUser($user);
            $transaction->setCourse($course);
            $transaction->setCreatedAt(new \DateTime('now'));
            $transaction->setOperationType(0);
            $transaction->setValue($course->getPrice());

        return $transaction;
    }
}
