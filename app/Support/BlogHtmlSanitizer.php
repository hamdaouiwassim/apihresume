<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

class BlogHtmlSanitizer
{
    private static ?HTMLPurifier $purifier = null;

    public static function clean(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        if (self::$purifier === null) {
            $cacheDir = storage_path('framework/cache/htmlpurifier');
            if (! is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }

            $config = HTMLPurifier_Config::createDefault();
            $config->set('Cache.SerializerPath', $cacheDir);
            $config->set('HTML.Allowed', 'p,br,strong,b,em,i,u,a[href|title|rel|target],ul,ol,li,h1,h2,h3,h4,blockquote,pre,code,img[src|alt|width|height],table,thead,tbody,tr,th,td');
            $config->set('URI.AllowedSchemes', ['https' => true, 'http' => true, 'mailto' => true]);
            $config->set('Attr.AllowedFrameTargets', ['_blank']);
            $config->set('HTML.TargetBlank', true);

            self::$purifier = new HTMLPurifier($config);
        }

        return self::$purifier->purify($html);
    }
}
