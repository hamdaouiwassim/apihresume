<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

/**
 * Resume template pages (ResumeTemplates.jsx, templates.jsx, TemplatePreviewPage.jsx).
 */
class TemplatePageController extends Controller
{
    public function gallery(): View
    {
        return view('pages.templates.gallery');
    }

    public function index(): View
    {
        return view('pages.templates.index', ['templates' => Template::all()]);
    }

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
