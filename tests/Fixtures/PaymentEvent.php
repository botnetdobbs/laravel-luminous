<?php

namespace Botnetdobbs\Luminous\Tests\Fixtures;

use Botnetdobbs\Luminous\Attributes\ApiShape;
use Botnetdobbs\Luminous\Support\Shape;
use Botnetdobbs\Luminous\Tests\Fixtures\Enums\PaymentStatus;

#[ApiShape]
class PaymentEvent
{
    public static function schema(): Shape
    {
        return Shape::object([
            'event' => Shape::string()->readOnly()->description('Event type identifier'),
            'payment_id' => Shape::uuid()->readOnly(),
            'status' => Shape::enum(PaymentStatus::class)->readOnly(),
            'timestamp' => Shape::dateTime()->readOnly(),
        ]);
    }
}
