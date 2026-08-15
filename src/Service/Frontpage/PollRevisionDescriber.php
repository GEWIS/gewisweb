<?php

declare(strict_types=1);

namespace App\Service\Frontpage;

use App\Entity\Application\RevisionInterface;
use App\Entity\Frontpage\FrontpageLocalisedText;
use App\Entity\Frontpage\PollRevision;
use App\Service\Application\AbstractRevisionDescriber;
use App\ViewModel\Application\Review\RevisionSection;
use Override;
use Symfony\Component\Translation\TranslatableMessage;

use function assert;
use function count;
use function max;
use function Symfony\Component\Translation\t;

/**
 * A poll is its question and the answers it can be given, so that is the whole of what the board reads.
 *
 * A question that is asked again after being turned down is compared answer by answer in the order they were written,
 * which is the only order there is to compare them in: an option carries no identity across revisions, because every
 * revision writes its own. An answer that was dropped therefore reads as one that was emptied.
 */
final class PollRevisionDescriber extends AbstractRevisionDescriber
{
    #[Override]
    protected function revisionClass(): string
    {
        return PollRevision::class;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function sections(
        RevisionInterface $revision,
        ?RevisionInterface $previous,
        bool $comparable,
    ): array {
        assert($revision instanceof PollRevision);
        assert(null === $previous || $previous instanceof PollRevision);

        $options = $revision->getOptions()->getValues();
        $previousOptions = $previous?->getOptions()->getValues() ?? [];

        $answers = [];
        $count = max(
            count($options),
            count($previousOptions),
        );

        for ($index = 0; $index < $count; ++$index) {
            $answers[] = $this->localisedField(
                new TranslatableMessage(
                    'Answer %number%',
                    ['%number%' => $index + 1],
                ),
                ($previousOptions[$index] ?? null)?->getText(),
                ($options[$index] ?? null)?->getText() ?? new FrontpageLocalisedText(),
                $comparable,
            );
        }

        return [
            new RevisionSection(
                t('Question'),
                [
                    $this->localisedField(
                        t('Question'),
                        $previous?->getQuestion(),
                        $revision->getQuestion(),
                        $comparable,
                    ),
                ],
            ),
            new RevisionSection(
                t('Answers'),
                $answers,
            ),
        ];
    }
}
