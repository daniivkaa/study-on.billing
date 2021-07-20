<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CourseDto
{
    /**
    * @Assert\NotBlank(message="Code is mandatory")
    * @Assert\Length(
    *      min = 5,
    *      max = 255,
    *      minMessage = "The code has to be at least {{ limit }} chars long",
    *      maxMessage = "The code must be no longer than {{ limit }} more"
    * )
    */
    private string $code;

    /**
    * @Assert\NotBlank(message="Type is mandatory")
    */
    private int $type;

    /**
    * @Assert\NotBlank(message="Price is mandatory")
    */
    private float $price;

    /**
    * @Assert\NotBlank(message="Token not found")
    */
    private string $token;

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }
}