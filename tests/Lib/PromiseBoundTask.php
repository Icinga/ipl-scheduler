<?php

namespace ipl\Tests\Scheduler\Lib;

use ipl\Scheduler\Common\TaskProperties;
use ipl\Scheduler\Contract\Task;
use Ramsey\Uuid\Uuid;
use React\Promise\ExtendedPromiseInterface;

class PromiseBoundTask implements Task
{
    use TaskProperties;

    /** @var ExtendedPromiseInterface */
    protected $promise;

    /** @var int  */
    protected $startedPromises = 0;

    public function __construct(ExtendedPromiseInterface $promise)
    {
        $this->promise = $promise;

        $uuid = Uuid::uuid4();
        $this->setName($uuid->toString());
        $this->setUuid($uuid);
    }

    public function getStartedPromises(): int
    {
        return $this->startedPromises;
    }

    public function run(): ExtendedPromiseInterface
    {
        $this->startedPromises++;

        return $this->promise;
    }
}
