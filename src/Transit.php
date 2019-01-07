<?php
declare(strict_types=1);

namespace Atlas\Transit;

use ArrayObject;
use Atlas\Orm\Atlas;
use Atlas\Mapper\Record;
use Atlas\Mapper\RecordSet;
use Atlas\Transit\CaseConverter;
use Atlas\Transit\Casing\SnakeCase;
use Atlas\Transit\Casing\CamelCase;
use Atlas\Transit\Handler\AggregateHandler;
use Atlas\Transit\Handler\CollectionHandler;
use Atlas\Transit\Handler\EntityHandler;
use Atlas\Transit\Handler\Handler;
use Atlas\Transit\Handler\HandlerFactory;
use Atlas\Transit\Handler\HandlerLocator;
use Closure;
use ReflectionParameter;
use ReflectionProperty;
use SplObjectStorage;

/**
 *
 * Toward a standard vocabulary:
 *
 * We think most broadly in terms of the domain (aggregate, entity, collection,
 * value object) and the source (mapper, record, recordset).
 *
 * Domain objects have properties, parameters, and arguments; source objects
 * have fields. Or perhaps we talk in terms of "elements" ?
 *
 * Want to keep away from the word "value" because it can be conflated with
 * Value Object; use $data for arrays and $datum for elements.
 *
 * ---
 *
 * Also want to standardize on parameter precedence:
 *
 * handler, param/property, domain/domainClass, record, data/datum
 *
 * @todo getAtlas()
 *
 * @todo Have TransitSelect extend MapperSelect, and configure Atlas to
 * factory *that* instead of MapperSelect? Would provide "transparent"
 * access to all select methods. Maybe leave select($whereEquals) and make
 * fetchDomain($domainClass) -- no, need to know the $domainClass early to
 * figure which MapperSelect to use.
 *
 * @todo Consider persist/delete/flush instead of store/discard/persist.
 *
 * @todo Expose Atlas via __call() ? Would affect the store/flush/etc. naming.
 *
 */
class Transit
{
    protected $atlas;

    protected $handlerLocator;

    protected $storage;

    protected $refresh;

    protected $plan;

    public static function new(
        Atlas $atlas,
        string $sourceNamespace,
        string $domainNamespace,
        string $sourceCasingClass = SnakeCase::CLASS,
        string $domainCasingClass = CamelCase::CLASS
    ) {
        return new static(
            $atlas,
            new HandlerLocator(
                $atlas,
                $sourceNamespace,
                $domainNamespace,
                new CaseConverter(
                    new $sourceCasingClass(),
                    new $domainCasingClass()
                )
            )
        );
    }

    public function __construct(
        Atlas $atlas,
        HandlerLocator $handlerLocator
    ) {
        $this->atlas = $atlas;
        $this->handlerLocator = $handlerLocator;
        $this->storage = new SplObjectStorage();
        $this->refresh = new SplObjectStorage();
        $this->plan = new SplObjectStorage();
    }

    public function select(string $domainClass, array $whereEquals = []) : TransitSelect
    {
        $handler = $this->handlerLocator->getOrThrow($domainClass);

        $select = new TransitSelect(
            $handler,
            $this->storage
        );

        return $select->whereEquals($whereEquals);
    }

    protected function updateSource(object $domain)
    {
        $handler = $this->handlerLocator->getOrThrow($domain);
        return $handler->updateSource($domain, $this->storage, $this->refresh);
    }

    protected function deleteSource(object $domain)
    {
        if (! $this->storage->contains($domain)) {
            throw new Exception("no source for domain");
        }

        $source = $this->storage[$domain];
        $source->setDelete();
        return $source;
    }

    // PLAN TO insert/update
    public function store(object $domain) : void
    {
        if ($this->plan->contains($domain)) {
            $this->plan->detach($domain);
        }
        $this->plan->attach($domain, 'updateSource');
    }

    // PLAN TO delete
    public function discard(object $domain) : void
    {
        if ($this->plan->contains($domain)) {
            $this->plan->detach($domain);
        }
        $this->plan->attach($domain, 'deleteSource');
    }

    public function persist() : void
    {
        foreach ($this->plan as $domain) {
            $method = $this->plan->getInfo();
            $source = $this->$method($domain);
            if ($source instanceof RecordSet) {
                $this->atlas->persistRecordSet($source);
            } else {
                $this->atlas->persist($source);
            }
        }

        foreach ($this->refresh as $domain) {
            $handler = $this->handlerLocator->get($domain);
            $record = $this->storage[$domain];
            $handler->refreshDomain($domain, $record, $this->storage, $this->refresh);
        }

        // unset/detach deleted as we go

        // and: how to associate records, esp. failed records, with
        // domain objects? or do we care about the domain objects at
        // this point?

        // reset the plan
        $this->plan = new SplObjectStorage();
    }
}
