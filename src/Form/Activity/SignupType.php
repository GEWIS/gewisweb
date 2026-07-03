<?php

declare(strict_types=1);

namespace App\Form\Activity;

use App\Entity\Activity\Enums\SignupFieldTypes;
use App\Entity\Activity\Signup;
use App\Entity\Activity\SignupField;
use App\Entity\Activity\SignupList;
use App\Entity\Application\Enums\Languages;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tito10047\AltchaBundle\Type\AltchaType;

use function Symfony\Component\Translation\t;

/**
 * The public sign-up form for a single {@see SignupList}: the list's custom fields built dynamically by type, plus, for
 * non-members, a name + email and (for anonymous externals) an Altcha captcha, and a required policy-agreement
 * checkbox for self sign-ups. Not bound to an entity ({@see Signup} has two subtypes and externals have no object yet),
 * so it produces a plain array keyed by {@see self::fieldKey()} that {@see \App\Service\Activity\SignupManager} maps.
 *
 * @extends AbstractType<array<string, mixed>>
 */
class SignupType extends AbstractType
{
    /** A logged-in member signing themselves up: no name/email, no captcha. */
    public const string MODE_MEMBER = 'member';

    /** An anonymous external signing themselves up: name/email + captcha + email verification. */
    public const string MODE_EXTERNAL = 'external';

    /** An organiser adding an external by hand: name/email, but no captcha and no agreement checkbox. */
    public const string MODE_ORGANISER = 'organiser';

    /** An external editing their own sign-up via the emailed manage link: name + answers, no editable email. */
    public const string MODE_MANAGE = 'manage';

    private const string ACTIVITY_POLICY_URL = 'https://gewis.nl/data/regulations/activity-policy.pdf';
    private const string ALCOHOL_POLICY_URL = 'https://gewis.nl/data/regulations/alcohol-policy.pdf';

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * The form-child name for a sign-up field. Prefixed (rather than the bare numeric id) so the child name is always a
     * valid, non-numeric form name.
     */
    public static function fieldKey(int $fieldId): string
    {
        return 'field_' . $fieldId;
    }

    /**
     * Reshape submitted form data into the field-id-keyed map {@see \App\Service\Activity\SignupManager} expects.
     *
     * @param array<string, mixed> $data
     *
     * @return array<int, mixed>
     */
    public static function extractFieldData(
        SignupList $signupList,
        array $data,
    ): array {
        $fieldData = [];
        foreach ($signupList->getFields() as $field) {
            $fieldData[(int) $field->getId()] = $data[self::fieldKey((int) $field->getId())] ?? null;
        }

        return $fieldData;
    }

    /**
     * Pre-fill values for editing, keyed like the form. {@see Signup::toFormArray()} already encodes choice/yes-no;
     * only numbers need casting back to int for {@see IntegerType}.
     *
     * @return array<string, mixed>
     */
    public static function fieldPrefill(
        SignupList $signupList,
        Signup $signup,
    ): array {
        $answers = $signup->toFormArray();
        $prefill = [];
        foreach ($signupList->getFields() as $field) {
            $raw = $answers[$field->getId()] ?? null;
            $prefill[self::fieldKey((int) $field->getId())] = SignupFieldTypes::Number === $field->getType()
                ? (null === $raw ? null : (int) $raw)
                : $raw;
        }

        return $prefill;
    }

    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $signupList = $options['signupList'];
        $mode = $options['mode'];

        if (self::MODE_MEMBER !== $mode) {
            $builder->add(
                'fullName',
                TextType::class,
                [
                    'label' => t('Full name'),
                    'constraints' => [
                        new NotBlank(message: 'Enter a full name.'),
                        new Length(
                            max: 255,
                            maxMessage: 'The full name may not be longer than {{ limit }} characters.',
                        ),
                    ],
                ],
            );

            // The email is the verified identity of an external sign-up, so on the manage page it is shown but
            // disabled: a disabled field is also ignored on submit, so it can never be changed there (which would
            // bypass the verification the sign-up was confirmed with). To use a different address the subscriber
            // unsubscribes and signs up again.
            $builder->add(
                'email',
                EmailType::class,
                [
                    'label' => t('Email address'),
                    'disabled' => self::MODE_MANAGE === $mode,
                    'constraints' => [
                        new NotBlank(message: 'Enter an email address.'),
                        new Email(message: 'Enter a valid email address.'),
                        new Length(
                            max: 255,
                            maxMessage: 'The email address may not be longer than {{ limit }} characters.',
                        ),
                    ],
                ],
            );
        }

        $language = Languages::current();
        foreach ($signupList->getFields() as $field) {
            $this->addSignupField(
                $builder,
                $field,
                $language,
            );
        }

        // Anonymous external sign-ups are gated by a self-hosted proof-of-work captcha (members and organiser-added
        // externals are trusted and skip it).
        if (self::MODE_EXTERNAL === $mode) {
            $builder->add(
                'security',
                AltchaType::class,
            );
        }

        // Self sign-ups must actively accept the policies; an organiser adding someone cannot consent on their behalf,
        // and a self-service edit of an existing (already-agreed) sign-up does not re-prompt.
        if (
            self::MODE_ORGANISER === $mode
            || self::MODE_MANAGE === $mode
        ) {
            return;
        }

        $builder->add(
            'agree',
            CheckboxType::class,
            [
                // HTML label (with the policy links) rendered by the form theme, so the checkbox and its text stay in
                // one aligned .form-check. The fragments are our own translations, hence safe to mark as HTML.
                'label' => $this->agreementLabel(),
                'label_html' => true,
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new IsTrue(message: 'You must agree to the activity and alcohol policies to sign up.'),
                ],
            ],
        );
    }

    private function agreementLabel(): string
    {
        return $this->translator->trans('I agree to the')
            . ' <a href="' . self::ACTIVITY_POLICY_URL . '" target="_blank" rel="noopener">'
            . $this->translator->trans('Activity Policy') . '</a> '
            . $this->translator->trans('and')
            . ' <a href="' . self::ALCOHOL_POLICY_URL . '" target="_blank" rel="noopener">'
            . $this->translator->trans('Alcohol Policy') . '</a>.';
    }

    /**
     * @param FormBuilderInterface<array<string, mixed>> $builder
     */
    private function addSignupField(
        FormBuilderInterface $builder,
        SignupField $field,
        Languages $language,
    ): void {
        $name = self::fieldKey((int) $field->getId());
        // The field label is author-provided localised content, so it is rendered verbatim (translation_domain false)
        // and the sensitive marker is appended, matching the footnote shown below the form.
        $label = ($field->getName()->getText($language) ?? '') . ($field->isSensitive() ? '¹' : '');
        $shared = [
            'label' => $label,
            'translation_domain' => false,
        ];

        switch ($field->getType()) {
            case SignupFieldTypes::Text:
                $builder->add(
                    $name,
                    TextType::class,
                    [
                        ...$shared,
                        'constraints' => [new NotBlank(message: 'This field is required.')],
                    ],
                );

                break;
            case SignupFieldTypes::YesNo:
                $builder->add(
                    $name,
                    ChoiceType::class,
                    [
                        ...$shared,
                        'choices' => [
                            $this->translator->trans('Yes') => '1',
                            $this->translator->trans('No') => '0',
                        ],
                        'choice_translation_domain' => false,
                        'expanded' => true,
                        'multiple' => false,
                        'placeholder' => false,
                        'constraints' => [new NotNull(message: 'This field is required.')],
                    ],
                );

                break;
            case SignupFieldTypes::Number:
                $constraints = [new NotNull(message: 'This field is required.')];
                if (
                    null !== $field->getMinimumValue()
                    || null !== $field->getMaximumValue()
                ) {
                    $constraints[] = new Range(
                        notInRangeMessage: 'Enter a value between {{ min }} and {{ max }}.',
                        min: $field->getMinimumValue(),
                        max: $field->getMaximumValue(),
                    );
                }

                $builder->add(
                    $name,
                    IntegerType::class,
                    [
                        ...$shared,
                        'constraints' => $constraints,
                    ],
                );

                break;
            case SignupFieldTypes::Choice:
                $choices = [];
                foreach ($field->getOptions() as $option) {
                    $choices[$option->getValue()->getText($language) ?? ''] = $option->getId();
                }

                $builder->add(
                    $name,
                    ChoiceType::class,
                    [
                        ...$shared,
                        'choices' => $choices,
                        'choice_translation_domain' => false,
                        'placeholder' => $this->translator->trans('Choose an option'),
                        'constraints' => [new NotNull(message: 'This field is required.')],
                    ],
                );

                break;
        }
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'mode' => self::MODE_MEMBER,
        ]);
        $resolver->setRequired('signupList');
        $resolver->setAllowedTypes(
            'signupList',
            SignupList::class,
        );
        $resolver->setAllowedValues(
            'mode',
            [
                self::MODE_MEMBER,
                self::MODE_EXTERNAL,
                self::MODE_ORGANISER,
                self::MODE_MANAGE,
            ],
        );
    }
}
