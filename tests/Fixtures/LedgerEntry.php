<?php

namespace Botnetdobbs\Luminous\Tests\Fixtures;

use Botnetdobbs\Luminous\Attributes\ApiShape;
use Botnetdobbs\Luminous\Support\Shape;

#[ApiShape]
class LedgerEntry
{
    public static function schema(): Shape
    {
        return Shape::object([
            'id' => Shape::uuid()->readOnly(),
            'amount' => Shape::integer()->readOnly(),
            'direction' => Shape::string()->readOnly()->values(['debit', 'credit']),
            'currency' => Shape::string()->readOnly(),
            'created_at' => Shape::dateTime()->readOnly(),
        ]);
    }
}
