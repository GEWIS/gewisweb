<?php

namespace Application\Model;

use Application\Model\Traits\IdentifiableTrait;
use Doctrine\ORM\Mapping\{
    Column,
    Entity,
};

#[Entity]
class ApprovableText
{
    use IdentifiableTrait;

    /**
     * The message accompanying the state of the approval.
     */
    #[Column(type: "string")]
    protected string $message;

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}
