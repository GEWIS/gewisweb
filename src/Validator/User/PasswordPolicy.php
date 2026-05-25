<?php

declare(strict_types=1);

namespace App\Validator\User;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Constraints\PasswordStrength;

final class PasswordPolicy
{
    public const int MIN_LENGTH = 16;
    public const int MAX_LENGTH = 2048;
    public const int MIN_STRENGTH = PasswordStrength::STRENGTH_STRONG;

    /**
     * @return list<Constraint>
     */
    public static function constraints(): array
    {
        return [
            new NotBlank(message: 'Please enter a password.'),
            new Length(
                min: self::MIN_LENGTH,
                max: self::MAX_LENGTH,
                minMessage: 'Your password should be at least {{ limit }} characters.',
                maxMessage: 'Your password should be at most {{ limit }} characters.',
            ),
            new PasswordStrength(minScore: self::MIN_STRENGTH),
            new NotCompromisedPassword(),
        ];
    }
}
