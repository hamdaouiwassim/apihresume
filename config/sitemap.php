<?php

$frontend = rtrim((string) env('FRONTEND_APP_URL', 'https://hresume.pro'), '/');

return [

  /*
  |--------------------------------------------------------------------------
  | Site base URL (SPA origin)
  |--------------------------------------------------------------------------
  */
  'base_url' => $frontend,

  /*
  |--------------------------------------------------------------------------
  | Cache TTL (seconds) for generated XML
  |--------------------------------------------------------------------------
  */
  'cache_ttl' => (int) env('SITEMAP_CACHE_TTL', 3600),

  /*
  |--------------------------------------------------------------------------
  | Optional paths to write sitemap.xml when content changes
  |--------------------------------------------------------------------------
  */
  'write_paths' => array_values(array_filter([
    env('SITEMAP_WRITE_PATH'),
    base_path('../frontend/public/sitemap.xml'),
  ])),

  /*
  |--------------------------------------------------------------------------
  | Static marketing / auth pages (path => [changefreq, priority])
  |--------------------------------------------------------------------------
  */
  'static_paths' => [
    '/' => ['weekly', '1.0'],
    '/cover-letter-builder' => ['monthly', '0.85'],
    '/work-certificate' => ['monthly', '0.85'],
    '/pricing' => ['monthly', '0.9'],
    '/templates/public' => ['monthly', '0.9'],
    '/faq' => ['monthly', '0.8'],
    '/contact' => ['monthly', '0.7'],
    '/privacy' => ['yearly', '0.6'],
    '/terms' => ['yearly', '0.6'],
    '/refund' => ['yearly', '0.6'],
    '/blog' => ['weekly', '0.8'],
    '/login' => ['monthly', '0.5'],
    '/register' => ['monthly', '0.8'],
    '/register/recruiter' => ['monthly', '0.7'],
  ],

];
