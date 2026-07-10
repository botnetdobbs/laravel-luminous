<?php

namespace Botnetdobbs\Luminous\Tests\Fixtures\Enums;

enum DocumentedStatus: string
{
    /**
     * @description Payment is awaiting confirmation
     */
    case Pending = 'pending';

    /**
     * Payment settled successfully
     */
    case Settled = 'settled';

    case Archived = 'archived';
}
