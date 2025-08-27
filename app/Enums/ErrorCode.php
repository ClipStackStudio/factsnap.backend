<?php

namespace App\Enums;

enum ErrorCode: int
{
    // Resource not found errors (404)
    case CATEGORY_NOT_FOUND = 1001;
    case PACKAGE_NOT_FOUND = 1002;
    case FACT_NOT_FOUND = 1003;
    case USER_NOT_FOUND = 1004;

    // Validation errors (422)
    case VALIDATION_ERROR = 2001;
    case INVALID_UUID = 2002;
    case MISSING_REQUIRED_FIELD = 2003;
    case INVALID_ACCESS_LEVEL = 2004;

    // User package subscription errors (400)
    case ALREADY_SUBSCRIBED = 2005;
    case NOT_SUBSCRIBED = 2006;

    // Authentication errors (401)
    case UNAUTHORIZED = 3001;
    case INVALID_TOKEN = 3002;
    case TOKEN_EXPIRED = 3003;

    // Authorization errors (403)
    case FORBIDDEN = 4001;
    case INSUFFICIENT_PERMISSIONS = 4002;
    case ACCESS_LEVEL_REQUIRED = 4003;

    // Server errors (500)
    case INTERNAL_SERVER_ERROR = 5001;
    case DATABASE_ERROR = 5002;
    case CACHE_ERROR = 5003;

    /**
     * Get the HTTP status code for this error.
     */
    public function getHttpStatus(): int
    {
        return match ($this) {
            self::CATEGORY_NOT_FOUND, self::PACKAGE_NOT_FOUND, self::FACT_NOT_FOUND, self::USER_NOT_FOUND => 404,
            self::VALIDATION_ERROR, self::INVALID_UUID, self::MISSING_REQUIRED_FIELD, self::INVALID_ACCESS_LEVEL => 422,
            self::ALREADY_SUBSCRIBED, self::NOT_SUBSCRIBED => 400,
            self::UNAUTHORIZED, self::INVALID_TOKEN, self::TOKEN_EXPIRED => 401,
            self::FORBIDDEN, self::INSUFFICIENT_PERMISSIONS, self::ACCESS_LEVEL_REQUIRED => 403,
            self::INTERNAL_SERVER_ERROR, self::DATABASE_ERROR, self::CACHE_ERROR => 500,
        };
    }

    /**
     * Get a user-friendly message for this error.
     */
    public function getUserMessage(): string
    {
        return match ($this) {
            self::CATEGORY_NOT_FOUND => 'The requested category could not be found.',
            self::PACKAGE_NOT_FOUND => 'The requested package could not be found.',
            self::FACT_NOT_FOUND => 'The requested fact could not be found.',
            self::USER_NOT_FOUND => 'The requested user could not be found.',
            self::VALIDATION_ERROR => 'The provided data is invalid.',
            self::INVALID_UUID => 'The provided identifier is not valid.',
            self::MISSING_REQUIRED_FIELD => 'A required field is missing.',
            self::INVALID_ACCESS_LEVEL => 'The specified access level is not valid.',
            self::ALREADY_SUBSCRIBED => 'You are already subscribed to this package.',
            self::NOT_SUBSCRIBED => 'You are not subscribed to this package.',
            self::UNAUTHORIZED => 'You are not authorized to access this resource.',
            self::INVALID_TOKEN => 'The provided authentication token is invalid.',
            self::TOKEN_EXPIRED => 'Your authentication token has expired.',
            self::FORBIDDEN => 'You do not have permission to access this resource.',
            self::INSUFFICIENT_PERMISSIONS => 'Your current permissions do not allow this operation.',
            self::ACCESS_LEVEL_REQUIRED => 'A higher access level is required for this resource.',
            self::INTERNAL_SERVER_ERROR => 'An internal server error occurred.',
            self::DATABASE_ERROR => 'A database error occurred.',
            self::CACHE_ERROR => 'A cache error occurred.',
        };
    }
}
