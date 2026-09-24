<?php

namespace App\Console\Commands;

use App\Support\LegacyUrls;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-time data fix: stored file URLs that still point at a former domain of this app
 * (config app.legacy_urls, e.g. https://apihresume.hamdaouiacademy.com/storage/...) are rewritten to APP_URL.
 */
class RewriteLegacyUrls extends Command
{
    protected $signature = 'app:rewrite-legacy-urls
        {--dry-run : Show how many values would change without writing anything}';

    protected $description = 'Rewrite stored upload URLs from former app domains to APP_URL';

    /** Columns that store absolute URLs of uploaded files (or HTML containing them). */
    private const COLUMNS = [
        'blog_posts' => ['featured_image', 'content'],
        'users' => ['avatar'],
        'basic_infos' => ['avatar'],
        'recruiters' => ['brand_avatar'],
        'templates' => ['preview_image_url'],
    ];

    public function handle(): int
    {
        $target = LegacyUrls::currentOrigin();
        $origins = LegacyUrls::origins();

        if ($origins === []) {
            $this->info('No legacy URLs configured (LEGACY_APP_URLS).');

            return self::SUCCESS;
        }

        $this->line('Rewriting '.implode(', ', $origins).' -> '.$target);
        $total = 0;

        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                foreach ($origins as $origin) {
                    $query = DB::table($table)->where($column, 'like', '%'.$origin.'/%');
                    $count = $query->count();

                    if ($count === 0) {
                        continue;
                    }

                    if (! $this->option('dry-run')) {
                        $query->update([
                            $column => DB::raw('REPLACE('.DB::getQueryGrammar()->wrap($column).', '
                                .DB::getPdo()->quote($origin.'/').', '.DB::getPdo()->quote($target.'/').')'),
                        ]);
                    }

                    $total += $count;
                    $this->line(sprintf('  %s.%s: %d row(s) %s', $table, $column, $count, $this->option('dry-run') ? 'would change' : 'updated'));
                }
            }
        }

        $this->info($this->option('dry-run') ? "Dry run: {$total} value(s) would change." : "Done: {$total} value(s) updated.");

        return self::SUCCESS;
    }
}
