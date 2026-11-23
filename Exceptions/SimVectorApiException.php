<?php

namespace Modules\SimVector\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * Raised when the SimVector API returns an error. Renders as a JSON body
 * of {error: {code, message}} using the upstream HTTP status.
 */
class SimVectorApiException extends Exception
{
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message = ''
    ) {
        parent::__construct($message !== '' ? $message : $errorCode, $status);
    }

    public function render(): JsonResponse
    {
        // Never leak upstream 401s as a VA-session logout; surface as 502-ish for auth problems
        $status = $this->status >= 400 && $this->status < 600 ? $this->status : 502;
        if ($status === 401) {
            $status = 502;
        }

        return response()->json([
            'error' => ['code' => $this->errorCode, 'message' => $this->getMessage()],
        ], $status);
    }
}
