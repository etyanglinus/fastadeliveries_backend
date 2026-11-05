<?php

return [
    // Optional rollout restriction. Empty means all stores with an eligible active package.
    'pilot_stores' => array_filter(array_map('trim', explode(',', (string) env('BRANDSITE_PILOT_STORES', '')))),
    'package_ids' => array_values(array_filter(array_map('intval', explode(',', (string) env('BRANDSITE_PACKAGE_IDS', ''))), fn (int $id) => $id > 0)),
    'ingress_host' => strtolower(rtrim((string) env('BRANDSITE_INGRESS_HOST'), '.')),
    'ingress_ip' => env('BRANDSITE_INGRESS_IP'),
    'ingress_secret' => env('BRANDSITE_INGRESS_SECRET'),
    'web_origin' => env('BRANDSITE_WEB_ORIGIN'),
];
