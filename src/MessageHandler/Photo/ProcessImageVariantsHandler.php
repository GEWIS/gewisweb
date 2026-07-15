<?php

declare(strict_types=1);

namespace App\MessageHandler\Photo;

use App\Message\Photo\ProcessImageVariantsMessage;
use App\Service\Application\VariantGenerator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ProcessImageVariantsHandler
{
    public function __construct(
        private readonly VariantGenerator $variantGenerator,
    ) {
    }

    public function __invoke(ProcessImageVariantsMessage $message): void
    {
        $this->variantGenerator->generate(
            $message->getSourcePath(),
            $message->getProfile(),
        );
    }
}
