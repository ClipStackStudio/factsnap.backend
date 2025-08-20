<?php

namespace App\Models;

class ApiError
{
    public int $code;
    public string $message;
    public string $details;

    public function __construct(int $code, string $message, string $details)
    {
        $this->code = $code;
        $this->message = $message;
        $this->details = $details;
    }

    /**
     * Convert the error to an array for JSON response.
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'details' => $this->details,
        ];
    }

    /**
     * Convert the error to JSON.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
