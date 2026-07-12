<?php

return [
    'provisional_quotas' => [
        'campaign_jobs' => (int) env('ORIGINPAY_CONNECT_CAMPAIGN_JOBS_LIMIT', 10000),
        'providers' => (int) env('ORIGINPAY_CONNECT_PROVIDERS_LIMIT', 10),
        'uploads' => (int) env('ORIGINPAY_CONNECT_UPLOADS_LIMIT', 100),
        'upload_size_kb' => (int) env('ORIGINPAY_CONNECT_UPLOAD_SIZE_KB', 10240),
    ],
];
