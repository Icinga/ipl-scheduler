<?php

namespace ipl\Scheduler\Common;

use Ramsey\Uuid\UuidInterface;

trait TaskProperties
{
    /** @var string */
    protected $description;

    /** @var string Name of this task */
    protected $name;

    /** @var UuidInterface Unique identifier of this task */
    protected $uuid;

    /**
     * Set the description of this task
     *
     * @param ?string $desc
     *
     * @return $this
     */
    public function setDescription(?string $desc): self
    {
        $this->description = $desc;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name of this Task
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    /**
     * Set the UUID of this task
     *
     * @param UuidInterface $uuid
     *
     * @return $this
     */
    public function setUuid(UuidInterface $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }
}
