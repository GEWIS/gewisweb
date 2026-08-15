<?php

declare(strict_types=1);

namespace App\Form\Frontpage;

use App\Form\Application\ReviewDecisionType;
use DateTime;
use Override;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;

use function Symfony\Component\Translation\t;

/**
 * The decision on a poll question. Agreeing to a question is also scheduling it, so approving carries the closing
 * date the board picks. The date's constraints ride the approve button's validation group, the same way the base
 * type makes feedback mandatory to reject: the other decisions never ask for a date.
 */
class PollReviewDecisionType extends ReviewDecisionType
{
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        parent::buildForm(
            $builder,
            $options,
        );

        if (!$builder->has('approve')) {
            return;
        }

        $builder->add(
            'expiryDate',
            DateType::class,
            [
                'widget' => 'single_text',
                'label' => t('Closing date'),
                'help' => t('The closing date schedules the poll; it closes on this date.'),
                // A poll closes on its date, so today would publish a poll that is already over.
                'data' => new DateTime('tomorrow'),
                'attr' => ['min' => new DateTime('tomorrow')->format('Y-m-d')],
                'constraints' => [
                    new NotBlank(
                        message: 'Fill in a closing date before approving this poll.',
                        groups: ['closing_date_required'],
                    ),
                    new GreaterThan(
                        value: 'today',
                        message: 'The closing date must be in the future.',
                        groups: ['closing_date_required'],
                    ),
                ],
            ],
        );

        // The base type gives approve nothing to validate; here the closing date must hold before a poll goes live.
        $builder->add(
            'approve',
            SubmitType::class,
            [
                'label' => t('Approve'),
                'validation_groups' => ['closing_date_required'],
            ],
        );
    }

    /**
     * On the wire this stays the shared decision form: the same name, the same fields, plus the date.
     */
    #[Override]
    public function getBlockPrefix(): string
    {
        return 'review_decision';
    }
}
