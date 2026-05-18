<?php

return [
    /*
    | Free-tier monthly caps (calendar month, UTC). Pro and admin users are unlimited.
    */
    'free_monthly' => [
        'enhance_text' => (int) env('FREE_AI_ENHANCE_PER_MONTH', 5),
        'tailor_resume' => (int) env('FREE_AI_TAILOR_PER_MONTH', 1),
        'ats_score' => (int) env('FREE_AI_ATS_PER_MONTH', 3),
    ],
];
