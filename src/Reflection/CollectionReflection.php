<?php
declare(strict_types=1);

namespace Atlas\Transit\Reflection;

use Atlas\Transit\Inflector\Inflector;
use ReflectionClass;

class CollectionReflection extends Reflection
{
    protected $type = 'Collection';
    protected $mapperClass;

    public function __construct(
        ReflectionClass $r,
        ReflectionLocator $reflectionLocator
    ) {
        parent::__construct($r, $reflectionLocator);
        $this->mapperClass = $this->getAnnotatedMaperClass();
        if ($this->mapperClass === null) {
            $final = strrchr($this->domainClass, '\\');
            if (substr($final, -10) === 'Collection') {
                $final = substr($final, 0, -10);
            }
            $this->mapperClass = $reflectionLocator->getSourceNamespace() . $final . $final;
        }
    }
}
