<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\FormInterface;

/**
 * Shared helper for form types that render a field read-only based on runtime state (the activity has started, the
 * revision is frozen, ...). Symfony has no in-place "disable"; re-adding the field with the `disabled` option flipped,
 * preserving its type and options, is the supported way, and a disabled field is ignored on submit.
 */
trait DisablesFieldsTrait
{
    /**
     * Re-add a field to the form as `disabled`, preserving its type and options.
     *
     * @param FormInterface<mixed> $form
     */
    private function disableField(
        FormInterface $form,
        string $name,
    ): void {
        $config = $form->get($name)->getConfig();
        $options = $config->getOptions();
        $options['disabled'] = true;

        $form->add(
            $name,
            $config->getType()->getInnerType()::class,
            $options,
        );
    }
}
