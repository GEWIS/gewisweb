<?php

declare(strict_types=1);

namespace Application\Model;

use Application\Model\Traits\IdentifiableTrait;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class ApprovableText
{
    use IdentifiableTrait;

    public function __construct(
        /**
         * The message accompanying the state of the approval.
         */
        #[Column(type: 'string')]
        protected string $message,
    ) {
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }
}
