<?php

declare(strict_types=1);

namespace Decision\Extensions\Doctrine;

use Application\Extensions\Doctrine\BackedEnumType;
use Decision\Model\Enums\MeetingTypes;

/**
 * @extends BackedEnumType<MeetingTypes>
 */
class MeetingTypesType extends BackedEnumType
{
    public string $enumClass = MeetingTypes::class;

    public const string NAME = 'meeting_types';

    public function getName(): string
    {
        return self::NAME;
    }
}
