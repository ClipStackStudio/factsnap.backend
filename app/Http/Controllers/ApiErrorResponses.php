<?php

namespace App\Http\Controllers;

use App\Enums\ErrorCode;
use App\Models\ApiError;
use Illuminate\Http\JsonResponse;

trait ApiErrorResponses
{
    /**
     * Return a standardized error response using ApiError model.
     *
     * @param ErrorCode $errorCode The error code enum
     * @param string $details Technical details and context for developers
     * @return JsonResponse
     */
    protected function errorResponse(ErrorCode $errorCode, string $details): JsonResponse
    {
        $apiError = new ApiError(
            $errorCode->value,
            $errorCode->getUserMessage(),
            $details
        );

        return response()->json($apiError->toArray(), $errorCode->getHttpStatus());
    }

    /**
     * Return a "not found" error response.
     *
     * @param string $resource The resource type (e.g., 'category', 'package')
     * @param string $id The identifier that wasn't found
     * @param string $additionalContext Additional context for the error
     * @return JsonResponse
     */
    protected function notFoundResponse(string $resource, string $id, string $additionalContext = ''): JsonResponse
    {
        $errorCode = match (strtolower($resource)) {
            'category' => ErrorCode::CATEGORY_NOT_FOUND,
            'package' => ErrorCode::PACKAGE_NOT_FOUND,
            'fact' => ErrorCode::FACT_NOT_FOUND,
            'user' => ErrorCode::USER_NOT_FOUND,
            default => ErrorCode::CATEGORY_NOT_FOUND,
        };

        $details = "{$resource} with UUID '{$id}' does not exist in the database.";
        
        if ($additionalContext) {
            $details .= " {$additionalContext}";
        }

        return $this->errorResponse($errorCode, $details);
    }

    /**
     * Return a validation error response.
     *
     * @param array $errors Validation errors
     * @param string $additionalContext Additional context
     * @return JsonResponse
     */
    protected function validationErrorResponse(array $errors, string $additionalContext = ''): JsonResponse
    {
        $details = 'Validation failed for the following fields: ' . implode(', ', array_keys($errors));
        
        if ($additionalContext) {
            $details .= " {$additionalContext}";
        }

        $apiError = new ApiError(
            ErrorCode::VALIDATION_ERROR->value,
            ErrorCode::VALIDATION_ERROR->getUserMessage(),
            $details
        );

        // Add validation errors to the response
        $response = $apiError->toArray();
        $response['errors'] = $errors;

        return response()->json($response, ErrorCode::VALIDATION_ERROR->getHttpStatus());
    }

    /**
     * Return an unauthorized error response.
     *
     * @param string $additionalContext Additional context
     * @return JsonResponse
     */
    protected function unauthorizedResponse(string $additionalContext = ''): JsonResponse
    {
        $details = 'Authentication is required to access this endpoint.';
        
        if ($additionalContext) {
            $details .= " {$additionalContext}";
        }

        return $this->errorResponse(ErrorCode::UNAUTHORIZED, $details);
    }

    /**
     * Return a forbidden error response.
     *
     * @param string $additionalContext Additional context
     * @return JsonResponse
     */
    protected function forbiddenResponse(string $additionalContext = ''): JsonResponse
    {
        $details = 'Your current access level does not allow this operation.';
        
        if ($additionalContext) {
            $details .= " {$additionalContext}";
        }

        return $this->errorResponse(ErrorCode::FORBIDDEN, $details);
    }
}
