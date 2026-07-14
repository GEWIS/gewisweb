<?php

declare(strict_types=1);

namespace App\Tests\Entity\Photo;

use App\Entity\Photo\MemberTag;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * The point-in-image position guard lives on the {@see \App\Entity\Photo\Tag} base and is pure logic, so it is verified
 * without a database. A concrete {@see MemberTag} stands in for the abstract base.
 */
final class TagPositionTest extends TestCase
{
    public function testAcceptsNormalizedCoordinates(): void
    {
        $tag = new MemberTag();
        $tag->setPosition(
            0.25,
            0.75,
        );

        self::assertTrue($tag->hasPosition());
        self::assertSame(
            0.25,
            $tag->getPositionX(),
        );
        self::assertSame(
            0.75,
            $tag->getPositionY(),
        );
    }

    public function testAcceptsTheExactBounds(): void
    {
        $tag = new MemberTag();
        $tag->setPosition(
            0.0,
            1.0,
        );

        self::assertTrue($tag->hasPosition());
    }

    public function testClearingBothCoordinatesLeavesAWholePhotoTag(): void
    {
        $tag = new MemberTag();
        $tag->setPosition(
            0.5,
            0.5,
        );
        $tag->setPosition(
            null,
            null,
        );

        self::assertFalse($tag->hasPosition());
        self::assertNull($tag->getPositionX());
        self::assertNull($tag->getPositionY());
    }

    public function testRejectsACoordinateAboveTheRange(): void
    {
        $tag = new MemberTag();

        $this->expectException(InvalidArgumentException::class);
        $tag->setPosition(
            1.5,
            0.5,
        );
    }

    public function testRejectsACoordinateBelowTheRange(): void
    {
        $tag = new MemberTag();

        $this->expectException(InvalidArgumentException::class);
        $tag->setPosition(
            0.5,
            -0.1,
        );
    }

    public function testRejectsASingleCoordinate(): void
    {
        $tag = new MemberTag();

        $this->expectException(InvalidArgumentException::class);
        $tag->setPosition(
            0.5,
            null,
        );
    }
}
