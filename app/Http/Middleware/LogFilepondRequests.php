<?php

namespace Arbify\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogFilepondRequests
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('FilePond request received', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
            'has_file' => $request->hasFile(config('filepond.input_name', 'file')),
            'all_files' => $request->allFiles(),
            'input' => $request->except(['_token']),
            'content_type' => $request->header('Content-Type'),
        ]);

        $response = $next($request);

        Log::info('FilePond response', [
            'status' => $response->getStatusCode(),
            'content' => $response->getContent(),
        ]);

        return $response;
    }
}
