<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Public template pages live at /templates/{slug} (e.g. /templates/classic) instead of an id URL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->string('slug', 120)->nullable()->unique()->after('name');
        });

        $used = [];
        foreach (DB::table('templates')->orderBy('id')->get(['id', 'name']) as $template) {
            $base = Str::slug($template->name) ?: 'template';
            $slug = in_array($base, ['public', 'preview'], true) ? $base.'-template' : $base;
            for ($i = 2; in_array($slug, $used, true); $i++) {
                $slug = $base.'-'.$i;
            }
            $used[] = $slug;
            DB::table('templates')->where('id', $template->id)->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
