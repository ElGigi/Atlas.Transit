<?php
declare(strict_types=1);

namespace Atlas\Transit\Domain\Entity\Thread;

use Atlas\Mapper\Record;
use Atlas\Transit\DataConverter;
use Atlas\Transit\Domain\Value\DateTime;

class ThreadDataConverter extends DataConverter
{
    public function __createdAtFromSource(Record $record)
    {
        return new DateTime('1970-08-08');
    }
}
