<?php

declare(strict_types=1);

namespace App\Form\Application;

use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function strval;
use function trim;

/**
 * Shared checks for a revision form whose localised fields are gated by `languageDutch` / `languageEnglish` toggles.
 *
 * The toggles drive the `localised-fields` Stimulus controller, which disables the variants of a language that is off;
 * a disabled field is not submitted, so an unchecked language keeps whatever it already had and can never be required.
 * That leaves two things worth asserting on the server, where a tampered submission also lands.
 */
trait RequiresEnabledLanguagesTrait
{
    /**
     * With both languages off nothing below is required, so a revision with no content at all would save, contradicting
     * what the form promises. The toggles are always submitted, so this reads reliably.
     *
     * @param FormInterface<mixed> $revision
     */
    private function requireAtLeastOneLanguage(
        FormInterface $revision,
        TranslatorInterface $translator,
    ): void {
        if (
            true === $revision->get('languageDutch')->getData()
            || true === $revision->get('languageEnglish')->getData()
        ) {
            return;
        }

        $revision->get('languageDutch')->addError(new FormError(
            $translator->trans(
                'At least one language must be used.',
                [],
                'validators',
            ),
        ));
    }

    /**
     * Require each named localised field to be filled in for every enabled language.
     *
     * @param FormInterface<mixed> $revision
     * @param string[]             $fields
     */
    private function requireEnabledLanguages(
        FormInterface $revision,
        array $fields,
        TranslatorInterface $translator,
    ): void {
        $dutchOn = true === $revision->get('languageDutch')->getData();
        $englishOn = true === $revision->get('languageEnglish')->getData();

        foreach ($fields as $field) {
            $this->requireLocalisedText(
                $revision->get($field),
                $dutchOn,
                $englishOn,
                $translator,
            );
        }
    }

    /**
     * One localised field, filled in for every enabled language. Separate from the loop above because a form can hold
     * localised text well below the revision, as an activity's sign-up lists do.
     *
     * @param FormInterface<mixed> $localised
     */
    private function requireLocalisedText(
        FormInterface $localised,
        bool $dutchOn,
        bool $englishOn,
        TranslatorInterface $translator,
    ): void {
        if (
            $dutchOn
            && '' === trim(strval($localised->get('valueNL')->getData()))
        ) {
            $localised->get('valueNL')->addError(new FormError(
                $translator->trans(
                    'Fill in the Dutch text.',
                    [],
                    'validators',
                ),
            ));
        }

        if (
            !$englishOn
            || '' !== trim(strval($localised->get('valueEN')->getData()))
        ) {
            return;
        }

        $localised->get('valueEN')->addError(new FormError(
            $translator->trans(
                'Fill in the English text.',
                [],
                'validators',
            ),
        ));
    }
}
