<?php

declare(strict_types=1);

namespace App\Tests\Twig\Extensions;

use App\Entity\Decision\Member;
use App\Entity\User\CompanyUser;
use App\Entity\User\User;
use App\Twig\Extensions\StudentEmailExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Who is told their address will stop working. A member on a university address is; anybody else is not, and being
 * told wrongly is worse than not being told, since the notice asks them to go and change something.
 */
final class StudentEmailExtensionTest extends TestCase
{
    #[DataProvider('addresses')]
    public function testAMemberIsToldOnlyWhenTheirAddressWillExpire(
        ?string $email,
        bool $expected,
    ): void {
        self::assertSame(
            $expected,
            $this->extension($this->member($email))->showStudentEmailNotice(),
        );
    }

    /**
     * @return iterable<string, array{?string, bool}>
     */
    public static function addresses(): iterable
    {
        yield 'a university address' => [
            'somebody@student.tue.nl',
            true,
        ];

        // Whoever typed it in decides the case, not the check.
        yield 'the same one shouted' => [
            'SOMEBODY@STUDENT.TUE.NL',
            true,
        ];

        yield 'an association address' => [
            'somebody@gewis.nl',
            false,
        ];

        // The staff domain outlives a degree, so its holder has nothing to change.
        yield 'a staff address' => [
            'somebody@tue.nl',
            false,
        ];

        yield 'one that merely mentions it' => [
            'student.tue.nl@example.com',
            false,
        ];

        yield 'no address at all' => [
            null,
            false,
        ];
    }

    public function testACompanyUserIsNeverTold(): void
    {
        self::assertFalse($this->extension(self::createStub(CompanyUser::class))->showStudentEmailNotice());
    }

    public function testAPasserByIsNeverTold(): void
    {
        self::assertFalse($this->extension(null)->showStudentEmailNotice());
    }

    private function extension(?UserInterface $user): StudentEmailExtension
    {
        $security = self::createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        return new StudentEmailExtension(
            $security,
            '@student.tue.nl',
        );
    }

    private function member(?string $email): User
    {
        $member = self::createStub(Member::class);
        $member->method('getEmail')->willReturn($email);

        $user = self::createStub(User::class);
        $user->method('getMember')->willReturn($member);

        return $user;
    }
}
