<?php

namespace App\Dto;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Annotation as Serialization;
use Symfony\Component\Validator\Constraints as Assert;

class UserDto
{
    /**
     * @Assert\NotBlank(message="Email is mandatory")
     * @Assert\Email(
     *     message="Invalid email address"
     * )
     */
    public string $username;

    /**
     * @Assert\NotBlank(message="Password is mandatory")
     * @Assert\Length(
     *      min = 6,
     *      max = 1000,
     *      minMessage = "The password has to be at least {{ limit }} chars long",
     *      maxMessage = "The password must be no longer than {{ limit }} more"
     * )
     */
    public string $password;
}
