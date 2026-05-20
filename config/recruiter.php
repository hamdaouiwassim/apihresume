<?php

return [
    'limits' => [
        'enabled' => (bool) env('RECRUITER_LIMITS_ENABLED', false),
        'monthly_resume_views' => (int) env('RECRUITER_MONTHLY_VIEWS', 500),
        'max_open_jobs' => (int) env('RECRUITER_MAX_OPEN_JOBS', 10),
    ],
];
