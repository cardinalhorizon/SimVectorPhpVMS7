<?php

namespace Modules\SimVector\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Class VerifySSLSignature
 * @package Modules\SimVector\Http\Middleware
 */
class VerifySSLSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if the request is signed
        if (!$request->hasHeader('x-signature')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get the signature from the request header
        $signature = $request->header('x-signature');
        // Get the public key from the key file stored on the root of the module
        $publicKey = file_get_contents(base_path('modules/SimVector/simvector_data.pub'));

        // Verify the signature using the public key
        $isValid = openssl_verify($request->getContent(), base64_decode($signature), $publicKey, OPENSSL_ALGO_SHA256);

        if ($isValid !== 1) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        // If the signature is valid, proceed with the request
        return $next($request);
    }
}
