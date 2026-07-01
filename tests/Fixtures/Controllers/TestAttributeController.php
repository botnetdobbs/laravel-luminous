<?php

namespace Botnetdobbs\Luminous\Tests\Fixtures\Controllers;

use Botnetdobbs\Luminous\Attributes\ApiBody;
use Botnetdobbs\Luminous\Attributes\ApiDeprecated;
use Botnetdobbs\Luminous\Attributes\ApiExample;
use Botnetdobbs\Luminous\Attributes\ApiHeader;
use Botnetdobbs\Luminous\Attributes\ApiIgnore;
use Botnetdobbs\Luminous\Attributes\ApiNoSecurity;
use Botnetdobbs\Luminous\Attributes\ApiOperation;
use Botnetdobbs\Luminous\Attributes\ApiParam;
use Botnetdobbs\Luminous\Attributes\ApiQuery;
use Botnetdobbs\Luminous\Attributes\ApiResponse;
use Botnetdobbs\Luminous\Attributes\ApiSecurity;
use Botnetdobbs\Luminous\Attributes\ApiStream;
use Botnetdobbs\Luminous\Attributes\ApiTag;
use Botnetdobbs\Luminous\Tests\Fixtures\LedgerEntry;
use Botnetdobbs\Luminous\Tests\Fixtures\PaymentEvent;
use Botnetdobbs\Luminous\Tests\Fixtures\Requests\FileUploadRequest;

#[ApiTag('Test', summary: 'Test endpoints', kind: 'internal')]
#[ApiSecurity('bearerAuth')]
class TestAttributeController
{
    #[ApiTag('Test', description: 'Method override')]
    #[ApiOperation('Create resource', 'Creates a new resource')]
    #[ApiQuery('filter', 'Filter results')]
    #[ApiQuery('limit', 'Page limit', 'integer')]
    #[ApiHeader('Idempotency-Key', required: true)]
    #[ApiResponse(201, description: 'Created')]
    #[ApiResponse(409, description: 'Conflict')]
    #[ApiResponse(422, description: 'Validation failed')]
    #[ApiExample('example-1', 'Basic example', ['key' => 'value'], description: 'Longer explanation of this example')]
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

    #[ApiStream(PaymentEvent::class, description: 'Real-time payment updates')]
    #[ApiExample('payment-event', 'Sample event', ['event' => 'payment.succeeded'], type: 'response', status: 200, mediaType: 'text/event-stream')]
    public function eventStream(): void {}

    #[ApiStream(LedgerEntry::class, 'application/jsonl', description: 'JSONL stream')]
    public function jsonlStream(): void {}

    #[ApiQuery('filters', 'Complex filter expression', location: 'querystring')]
    #[ApiResponse(200, description: 'Filtered results')]
    public function filteredIndex(): void {}

    #[ApiResponse(200, description: 'Regular JSON response')]
    #[ApiStream(PaymentEvent::class, description: 'Stream at same status')]
    public function streamWithConflict(): void {}

    #[ApiQuery('q', 'Search query', location: 'invalid')]
    #[ApiResponse(200, description: 'Search results')]
    public function queryWithInvalidLocation(): void {}

    #[ApiParam('id', 'Deprecated path param', deprecated: true)]
    #[ApiResponse(200, description: 'OK')]
    public function deprecatedPathParam(): void {}
}
