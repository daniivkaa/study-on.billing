<?php

namespace App\DataFixtures;

use App\Entity\Course;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // $product = new Product();
        // $manager->persist($product);
        $courses = [
            "codes" => [
                "11111",
                "22222",
                "33333",
                "44444",
                "55555",
                "66666",
                "77777",
                "88888",
                "99999",
                "00000",
            ],
            "types" => [
                "0",
                "0",
                "0",
                "1",
                "1",
                "1",
                "1",
                "2",
                "2",
                "2",
            ],
            "prices" => [
                "0",
                "0",
                "0",
                "100",
                "500",
                "1500",
                "5000",
                "99",
                "199",
                "43",
            ],
            "titles" => [
                "Докер",
                "Минимум Линукс",
                "Минимум ГИТ",
                "Изучение верстки",
                "Основы PHP за 24 часа",
                "Symfony: От новичка до мастера",
                "Докер, Линукс и ГИТ до мастера",
                "Как разговаривать с заказчиком",
                "Полный курс по разработке с Symfony на 125 часов",
                "Как правильно оформить резюме"
            ],
        ];

        $course = [];

        for ($i = 0; $i < 10; $i++) {
            $course[$i] = new Course();
        }

        foreach ($course as $courseKey => $itemCourse) {
            $itemCourse->setCode($courses['codes'][$courseKey]);
            $itemCourse->setType($courses['types'][$courseKey]);
            $itemCourse->setPrice($courses['prices'][$courseKey]);
            $itemCourse->setTitle($courses['titles'][$courseKey]);
            $manager->persist($itemCourse);
        }

        $manager->flush();
    }
}
