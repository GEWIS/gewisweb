<?php

declare(strict_types=1);

namespace App\Form\Application;

use App\Entity\Application\Enums\SocialPlatform;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Regex;

use function sprintf;

/**
 * The social links on a revision, as one box per platform rather than a list somebody adds rows to. A body is either on
 * a platform or it is not, and asking which platform a row is for is a question with an obvious answer, so the platform
 * is fixed per box and an empty box means "not there".
 *
 * The form's data is the handle per platform, which is how a revision reads and writes them
 * ({@see \App\Entity\Application\Traits\HasSocialLinksTrait}); turning that map into rows is the revision's business,
 * because that is the side the foreign key lives on.
 *
 * A pasted profile link is reduced to a handle before it is judged, so the box that comes back after an error already
 * says what would be stored.
 *
 * @extends AbstractType<array<string, string>>
 */
class SocialLinksType extends AbstractType
{
    #[Override]
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        foreach (SocialPlatform::cases() as $platform) {
            $builder->add(
                $platform->value,
                TextType::class,
                [
                    // A brand name reads the same in both languages, so it is not looked up.
                    'label' => $platform->name,
                    'translation_domain' => false,
                    'required' => false,
                    'attr' => ['placeholder' => $platform->placeholder()],
                    'constraints' => [$this->handleConstraint($platform)],
                ],
            );

            // The constraint has to judge what will be stored rather than what was pasted, so the reduction to a handle
            // happens here and not in the entity's setter alone.
            $builder->get($platform->value)->addModelTransformer(new CallbackTransformer(
                static fn (?string $handle): string => $handle ?? '',
                static fn (?string $submitted): string => $platform->normaliseHandle($submitted ?? ''),
            ));
        }
    }

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => false,
            // The children write into a map keyed by platform, so there is no class behind this form.
            'data_class' => null,
            'empty_data' => [],
        ]);
    }

    /**
     * What a handle for this platform has to look like, refused in the platform's own terms. The messages are written
     * out per arm rather than picked out of a variable so they stay statically extractable.
     */
    private function handleConstraint(SocialPlatform $platform): Regex
    {
        $pattern = sprintf(
            '/%s/',
            $platform->handlePattern(),
        );

        return match ($platform) {
            SocialPlatform::Discord => new Regex(
                pattern: $pattern,
                message: 'This is not a valid Discord invite code.',
            ),
            SocialPlatform::Mastodon => new Regex(
                pattern: $pattern,
                message: 'Write a Mastodon account as username@instance.',
            ),
            default => new Regex(
                pattern: $pattern,
                message: 'This is not a valid username.',
            ),
        };
    }
}
