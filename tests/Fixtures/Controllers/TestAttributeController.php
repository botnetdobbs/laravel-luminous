<?php

namespace Botnetdobbs\Luminous\Tests\Fixtures\Controllers;

use Botnetdobbs\Luminous\Attributes\ApiBody;
use Botnetdobbs\Luminous\Attributes\ApiDeprecated;
use Botnetdobbs\Luminous\Attributes\ApiExample;
use Botnetdobbs\Luminous\Attributes\ApiHeader;
use Botnetdobbs\Luminous\Attributes\ApiIgnore;
use Botnetdobbs\Luminous\Attributes\ApiNoSecurity;
use Botnetdobbs\Luminous\Attributes\ApiOperation;
use Botnetdobbs\Luminous\Attributes\ApiQuery;
use Botnetdobbs\Luminous\Attributes\ApiResponse;
use Botnetdobbs\Luminous\Attributes\ApiSecurity;
use Botnetdobbs\Luminous\Attributes\ApiTag;
use Botnetdobbs\Luminous\Tests\Fixtures\Requests\FileUploadRequest;

#[ApiTag('Test')]
#[ApiSecurity('bearerAuth')]
class TestAttributeController
{
    #[ApiOperation('Create resource', 'Creates a new resource')]
    #[ApiQuery('filter', 'Filter results')]
    #[ApiQuery('limit', 'Page limit', 'integer')]
    #[ApiHeader('Idempotency-Key', required: true)]
    #[ApiResponse(201, description: 'Created')]
    #[ApiResponse(409, description: 'Conflict')]
    #[ApiResponse(422, description: 'Validation failed')]
    #[ApiExample('example-1', 'Basic example', ['key' => 'value'])]
    public function store(): void {}

    #[ApiNoSecurity]
    #[ApiOperation('Public status')]
    #[ApiResponse(200, description: 'OK')]
    public function publicStatus(): void {}

    #[ApiDeprecated('Use v2 instead', 'POST /v2/resources')]
    #[ApiOperation('Legacy create')]
    public function legacyStore(): void {}

    #[ApiIgnore]
    public function internalMethod(): void {}

    #[ApiBody(FileUploadRequest::class)]
    #[ApiResponse(200, description: 'Uploaded')]
    public function uploadAvatar(): void {}

    #[ApiResponse(200, description: 'OK')]
    #[ApiExample('absent-status', 'targets absent 999', [], type: 'response', status: 999)]
    public function ghostExample(): void {}
}
