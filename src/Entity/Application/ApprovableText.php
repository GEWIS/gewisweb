<?php

declare(strict_types=1);

namespace App\Entity\Application;

use App\Entity\Application\Traits\IdentifiableTrait;
use Doctrine\DBAL\Types\Types;
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
        #[Column(type: Types::STRING)]
        private string $message,
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
