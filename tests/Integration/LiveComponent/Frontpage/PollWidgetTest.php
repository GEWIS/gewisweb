<?php

declare(strict_types=1);

namespace App\Tests\Integration\LiveComponent\Frontpage;

use App\Entity\Frontpage\Poll;
use App\Entity\User\User;
use App\Repository\Frontpage\PollRepository;
use App\Tests\Integration\DatabaseTestCase;
use App\Twig\Components\Frontpage\PollWidget;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * The widget is the one poll surface a passer-by sees, so it renders for anybody and gates only the answering. These
 * exercise the real component with its real services; the class has no `#[IsGranted]` to lean on, which is the point.
 */
final class PollWidgetTest extends DatabaseTestCase
{
    public function testAnsweringRevealsTheResults(): void
    {
        $widget = $this->widget(8110);
        self::assertTrue($widget->canAnswer());
        self::assertNull($widget->chosen());

        $option = $widget->poll->getOptions()->getValues()[0];
        $widget->vote($option->getId() ?? 0);

        self::assertNull($widget->problem);
        self::assertSame(
            $option,
            $widget->chosen(),
        );
        self::assertFalse($widget->canAnswer());
    }

    public function testASecondAnswerIsRefused(): void
    {
        $widget = $this->widget(8111);
        $options = $widget->poll->getOptions()->getValues();

        $widget->vote($options[0]->getId() ?? 0);

        $this->expectException(AccessDeniedException::class);
        $widget->vote($options[1]->getId() ?? 0);
    }

    public function testAnAnswerFromAnotherPollIsRefused(): void
    {
        $widget = $this->widget(8112);

        $this->expectException(AccessDeniedException::class);
        $widget->vote(0);
    }

    /**
     * A passer-by reads the poll and nothing more, so there is nothing on the panel for them to press.
     */
    public function testAGuestSeesThePollButCannotAnswerIt(): void
    {
        $widget = self::getContainer()->get(PollWidget::class);
        $widget->poll = $this->livePoll();

        self::assertFalse($widget->canAnswer());
        self::assertNull($widget->chosen());

        $this->expectException(AccessDeniedException::class);
        $widget->vote($widget->poll->getOptions()->getValues()[0]->getId() ?? 0);
    }

    private function widget(int $lidnr): PollWidget
    {
        $user = $this->entityManager->getRepository(User::class)->find($lidnr);
        self::assertInstanceOf(
            User::class,
            $user,
        );

        self::getContainer()->get('security.token_storage')->setToken(new UsernamePasswordToken(
            $user,
            'main',
            ['ROLE_USER'],
        ));

        $widget = self::getContainer()->get(PollWidget::class);
        $widget->poll = $this->livePoll();

        return $widget;
    }

    private function livePoll(): Poll
    {
        $poll = self::getContainer()->get(PollRepository::class)->findCurrentPoll();
        self::assertInstanceOf(
            Poll::class,
            $poll,
            'The seed is expected to contain a running poll.',
        );

        return $poll;
    }
}
