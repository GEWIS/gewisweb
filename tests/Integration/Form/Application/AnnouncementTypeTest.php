<?php

declare(strict_types=1);

namespace App\Tests\Integration\Form\Application;

use App\Entity\Application\Announcement;
use App\Entity\Application\ApplicationLocalisedText;
use App\Entity\Application\Enums\AlertTypes;
use App\Entity\Application\Enums\Languages;
use App\Form\Application\AnnouncementType;
use App\Tests\Integration\DatabaseTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

final class AnnouncementTypeTest extends DatabaseTestCase
{
    public function testAnEmptyDutchValueFallsBackToEnglish(): void
    {
        [
            $announcement, $form
        ] = $this->submit([
            'level' => AlertTypes::Info->value,
            'title' => [
                'valueEN' => 'Hello',
                'valueNL' => '',
            ],
            'body' => [
                'valueEN' => 'The body',
                'valueNL' => '',
            ],
        ]);

        self::assertTrue($form->isValid());
        // Empty Dutch is normalised to null, so it falls back to the English value.
        self::assertNull($announcement->getTitle()->getValueNL());
        self::assertSame(
            'Hello',
            $announcement->getTitle()->getText(Languages::Dutch),
        );
    }

    public function testAMissingEnglishValueIsRejected(): void
    {
        [, $form
        ] = $this->submit([
            'level' => AlertTypes::Info->value,
            'title' => [
                'valueEN' => '',
                'valueNL' => 'Hallo',
            ],
            'body' => [
                'valueEN' => 'The body',
                'valueNL' => '',
            ],
        ]);

        self::assertFalse($form->isValid());
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array{0: Announcement, 1: FormInterface<Announcement>}
     */
    private function submit(array $data): array
    {
        $announcement = new Announcement();
        $announcement->setTitle(new ApplicationLocalisedText());
        $announcement->setBody(new ApplicationLocalisedText());

        $form = self::getContainer()->get(FormFactoryInterface::class)->create(
            AnnouncementType::class,
            $announcement,
            ['csrf_protection' => false],
        );
        $form->submit($data);

        return [
            $announcement,
            $form,
        ];
    }
}
