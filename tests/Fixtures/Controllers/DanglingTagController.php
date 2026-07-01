<?php

namespace Botnetdobbs\Luminous\Tests\Fixtures\Controllers;

use Botnetdobbs\Luminous\Attributes\ApiResponse;
use Botnetdobbs\Luminous\Attributes\ApiTag;

#[ApiTag('Invoices', parent: 'Billing')]
class DanglingTagController
{
    #[ApiResponse(200, description: 'OK')]
    public function index(): void {}
}
