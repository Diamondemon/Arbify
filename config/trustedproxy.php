<?php

return [
    // Comma-separated proxy IPs/CIDRs, or "*" to trust the calling proxy. Unset = trust none.
    'proxies' => env('TRUSTED_PROXIES'),
];
