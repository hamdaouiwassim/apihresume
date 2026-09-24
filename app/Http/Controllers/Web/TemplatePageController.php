<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * Resume template pages (ResumeTemplates.jsx, templates.jsx, TemplatePreviewPage.jsx).
 */
class TemplatePageController extends Controller
{
    public function gallery(): View
    {
        return view('pages.templates.gallery', ['templates' => Template::orderBy('id')->get()]);
    }

    public function index(): View
    {
        return view('pages.templates.index', ['templates' => Template::all()]);
    }

    /** Public, indexable template page: /templates/{slug} and /fr/templates/{slug}. */
    public function show(string $slug)
    {
        $template = Template::where('slug', $slug)->first();

        if (! $template) {
            return response()->view('pages.templates.show', ['template' => null, 'others' => collect()], Response::HTTP_NOT_FOUND);
        }

        return view('pages.templates.show', [
            'template' => $template,
            'others' => Template::whereKeyNot($template->id)->orderBy('id')->get(),
        ]);
    }

    /** /templates/public/preview/{id} (old public URL, still in search results and shared links). */
    public function legacyPreview(string $id): RedirectResponse
    {
        $template = Template::find($id);
        abort_unless($template && $template->slug, Response::HTTP_NOT_FOUND);

        return redirect()->to(localized_route('templates.show', $template->slug), Response::HTTP_MOVED_PERMANENTLY);
    }

    /** In-app preview (/templates/preview/{id}, signed in). */
    public function preview(string $id)
    {
        $template = Template::find($id);

        return response()->view(
            'pages.templates.preview',
            ['template' => $template],
            $template ? Response::HTTP_OK : Response::HTTP_NOT_FOUND
        );
    }
}
