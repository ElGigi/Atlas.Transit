<?php
namespace Atlas\Transit\Domain\Entity\Author;

use Atlas\Transit\Domain\Entity\Entity;
use Atlas\Transit\Domain\Value\EmailValue;

class Author extends Entity
{
    protected $authorId;
    protected $name;
    protected $email;

    public function __construct(
        string $name,
        EmailValue $email,
        $fakeField = 'fake', // makes sure that defaults get populated
        int $authorId = null
    ) {
        $this->name = $name;
        $this->email = $email;
        $this->authorId = $authorId;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}