<?php

namespace Tests\Unit;

use App\Enums\ErrorCode;
use App\Models\ApiError;
use PHPUnit\Framework\TestCase;

class ApiErrorTest extends TestCase
{
    public function test_api_error_creation()
    {
        $error = new ApiError(1001, 'Test message', 'Test details');

        $this->assertEquals(1001, $error->code);
        $this->assertEquals('Test message', $error->message);
        $this->assertEquals('Test details', $error->details);
    }

    public function test_api_error_to_array()
    {
        $error = new ApiError(1001, 'Test message', 'Test details');
        $array = $error->toArray();

        $expected = [
            'code' => 1001,
            'message' => 'Test message',
            'details' => 'Test details',
        ];

        $this->assertEquals($expected, $array);
    }

    public function test_api_error_to_json()
    {
        $error = new ApiError(1001, 'Test message', 'Test details');
        $json = $error->toJson();

        $expected = json_encode([
            'code' => 1001,
            'message' => 'Test message',
            'details' => 'Test details',
        ]);

        $this->assertEquals($expected, $json);
    }

    public function test_error_code_enum_values()
    {
        $this->assertEquals(1001, ErrorCode::CATEGORY_NOT_FOUND->value);
        $this->assertEquals(1002, ErrorCode::PACKAGE_NOT_FOUND->value);
        $this->assertEquals(2001, ErrorCode::VALIDATION_ERROR->value);
        $this->assertEquals(3001, ErrorCode::UNAUTHORIZED->value);
    }

    public function test_error_code_http_status()
    {
        $this->assertEquals(404, ErrorCode::CATEGORY_NOT_FOUND->getHttpStatus());
        $this->assertEquals(422, ErrorCode::VALIDATION_ERROR->getHttpStatus());
        $this->assertEquals(401, ErrorCode::UNAUTHORIZED->getHttpStatus());
        $this->assertEquals(403, ErrorCode::FORBIDDEN->getHttpStatus());
        $this->assertEquals(500, ErrorCode::INTERNAL_SERVER_ERROR->getHttpStatus());
    }

    public function test_error_code_user_messages()
    {
        $this->assertEquals(
            'The requested category could not be found.',
            ErrorCode::CATEGORY_NOT_FOUND->getUserMessage()
        );
        $this->assertEquals(
            'The provided data is invalid.',
            ErrorCode::VALIDATION_ERROR->getUserMessage()
        );
        $this->assertEquals(
            'You are not authorized to access this resource.',
            ErrorCode::UNAUTHORIZED->getUserMessage()
        );
    }
}
