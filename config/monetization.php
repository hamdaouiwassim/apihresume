<?php

return [
    /*
    | Free-tier monthly caps (calendar month, UTC). Pro users have higher token budget; admins unlimited.
    */
    'free_monthly' => [
        'enhance_text' => (int) env('FREE_AI_ENHANCE_PER_MONTH', 5),
        'tailor_resume' => (int) env('FREE_AI_TAILOR_PER_MONTH', 1),
        'ats_score' => (int) env('FREE_AI_ATS_PER_MONTH', 3),
    ],

    /*
    | Default monthly LLM token budget for free users (calendar month).
    | Set to 0 in .env to disable free-tier AI until admin assigns a limit.
    */
    'default_monthly_token_limit' => (int) env('FREE_AI_MONTHLY_TOKEN_LIMIT', 1000),

    /*
    | Default monthly LLM token budget for Pro users (calendar month).
    | Admins remain unlimited unless ai_monthly_token_limit is set on the user.
    */
    'pro_monthly_token_limit' => (int) env('PRO_AI_MONTHLY_TOKEN_LIMIT', 50000),
];
