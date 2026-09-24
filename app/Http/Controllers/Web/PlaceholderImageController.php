<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Local replacement for via.placeholder.com: /placeholder/600x800?bg=0f172a&fg=ffffff&text=Classic
 * Returns a cacheable SVG, so no external image service is needed.
 */
class PlaceholderImageController extends Controller
{
    public function __invoke(Request $request, int $width, int $height): Response
    {
        $width = max(1, min($width, 2000));
        $height = max(1, min($height, 2000));
        $bg = $this->color($request->query('bg'), 'e2e8f0');
        $fg = $this->color($request->query('fg'), '64748b');
        $text = e(mb_substr((string) $request->query('text', "{$width}×{$height}"), 0, 60));
        $fontSize = max(12, (int) round(min($width, $height) / 10));

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
<rect width="100%" height="100%" fill="#{$bg}"/>
<text x="50%" y="50%" fill="#{$fg}" font-family="ui-sans-serif, system-ui, sans-serif" font-size="{$fontSize}" font-weight="600" text-anchor="middle" dominant-baseline="middle">{$text}</text>
</svg>
SVG;

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    private function color(?string $value, string $default): string
    {
        return is_string($value) && preg_match('/^[0-9a-fA-F]{3,8}$/', $value) ? $value : $default;
    }
}
