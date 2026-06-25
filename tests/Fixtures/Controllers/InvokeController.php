<?php

namespace Botnetdobbs\Luminous\Tests\Fixtures\Controllers;

use Botnetdobbs\Luminous\Attributes\ApiOperation;
use Botnetdobbs\Luminous\Attributes\ApiResponse;

class InvokeController
{
    #[ApiOperation('Invokable action')]
    #[ApiResponse(200, description: 'OK')]
    public function __invoke(): void {}
}
