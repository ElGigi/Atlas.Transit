<?php
declare(strict_types=1);

namespace Atlas\Transit\Domain\Entity\Response;

use Atlas\Transit\Domain\Entity\Entity;
use Atlas\Transit\Domain\Entity\Author\Author;

/**
 * @Atlas\Transit\Entity
 * @Atlas\Transit\Mapper Atlas\Testing\DataSource\Reply\Reply
 * @Atlas\Transit\Parameter $responseId reply_id
 */
class Response extends Entity
{
    protected $responseId;
    protected $author;
    protected $body;

    public function __construct(
        Author $author,
        string $body,
        int $responseId = null
    ) {
        $this->author = $author;
        $this->body = $body;
        $this->responseId = $responseId;
    }
}
