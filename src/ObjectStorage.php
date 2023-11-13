<?php

namespace ipl\Scheduler;

use ipl\Scheduler\Contract\Task;
use Ramsey\Uuid\UuidInterface;
use SplObjectStorage;

/**
 * ObjectStorage provides custom implementation of the internal PHP hash method and doesn't depend on the
 * `spl_object_hash()` function used by `SplObjectStorage` class.
 *
 * @extends SplObjectStorage<object, object|mixed>
 */
class ObjectStorage extends SplObjectStorage
{
    public function getHash($object): string
    {
        if ($object instanceof Task) {
            return $object->getUuid()->toString();
        }

        if ($object instanceof UuidInterface) {
            return $object->toString();
        }

        return parent::getHash($object);
    }
}
