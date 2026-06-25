<?php

namespace Botnetdobbs\Luminous\Tests\Fixtures\Enums;

enum PaymentStatus: string
{
    case Initiated = 'initiated';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case TimeoutPending = 'timeout_pending';
}
