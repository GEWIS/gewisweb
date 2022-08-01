<?php

namespace Decision\Model\Enums;

/**
 * Enum for the different address types.
 */
enum MeetingTypes: string
{
    case BV = 'BV'; // bestuursvergadering
    case AV = 'AV'; // algemene leden vergadering
    case VV = 'VV'; // voorzitters vergadering
    case VIRT = 'Virt'; // virtual meeting
}
