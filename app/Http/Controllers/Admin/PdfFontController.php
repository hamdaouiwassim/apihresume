<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PdfFont;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PdfFontController extends Controller
{
    /**
     * List all uploaded fonts.
     */
    public function index()
    {
        try {
            $fonts = PdfFont::orderBy('family_name')->get();

            return response()->json([
                'status' => true,
                'message' => 'Fonts fetched successfully',
                'data' => $fonts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload a new font family.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'family_name' => 'required|string|max:100|unique:pdf_fonts,family_name',
            'regular' => 'required|file|max:10240',
            'bold' => 'nullable|file|max:10240',
            'italic' => 'nullable|file|max:10240',
            'bold_italic' => 'nullable|file|max:10240',
        ]);

        // Additional validation: check file extensions
        foreach (['regular', 'bold', 'italic', 'bold_italic'] as $variant) {
            if ($request->hasFile($variant)) {
                $ext = strtolower($request->file($variant)->getClientOriginalExtension());
                if (!in_array($ext, ['ttf', 'otf'])) {
                    return response()->json([
                        'status' => false,
                        'message' => "The {$variant} file must be a TTF or OTF font file.",
                        'errors' => [$variant => ["Only .ttf and .otf files are accepted."]],
                    ], 422);
                }
            }
        }

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $slug = Str::slug($request->family_name, '_');
            $dir = 'fonts/' . $slug;

            // Store each variant
            $paths = [];
            foreach (['regular', 'bold', 'italic', 'bold_italic'] as $variant) {
                if ($request->hasFile($variant)) {
                    $file = $request->file($variant);
                    $filename = $slug . '_' . $variant . '.' . $file->getClientOriginalExtension();
                    $file->storeAs($dir, $filename, 'local');
                    $paths[$variant . '_path'] = $dir . '/' . $filename;
                }
            }

            $font = PdfFont::create([
                'family_name' => $request->family_name,
                'regular_path' => $paths['regular_path'],
                'bold_path' => $paths['bold_path'] ?? null,
                'italic_path' => $paths['italic_path'] ?? null,
                'bold_italic_path' => $paths['bold_italic_path'] ?? null,
                'is_active' => true,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Font uploaded successfully',
                'data' => $font,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to upload font',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle active state of a font.
     */
    public function toggleActive(PdfFont $pdfFont)
    {
        try {
            $pdfFont->update(['is_active' => !$pdfFont->is_active]);

            return response()->json([
                'status' => true,
                'message' => $pdfFont->is_active ? 'Font activated' : 'Font deactivated',
                'data' => $pdfFont,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a font and its files.
     */
    public function destroy(PdfFont $pdfFont)
    {
        try {
            // Delete font files from storage
            foreach (['regular_path', 'bold_path', 'italic_path', 'bold_italic_path'] as $field) {
                if ($pdfFont->{$field}) {
                    Storage::disk('local')->delete($pdfFont->{$field});
                }
            }

            // Delete the directory if empty
            $slug = Str::slug($pdfFont->family_name, '_');
            $dir = 'fonts/' . $slug;
            if (Storage::disk('local')->exists($dir)) {
                $remaining = Storage::disk('local')->files($dir);
                if (empty($remaining)) {
                    Storage::disk('local')->deleteDirectory($dir);
                }
            }

            $pdfFont->delete();

            return response()->json([
                'status' => true,
                'message' => 'Font deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete font',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Public endpoint: list active fonts for the font dropdown.
     */
    public static function activeFonts()
    {
        $fonts = PdfFont::active()->orderBy('family_name')->get(['id', 'family_name', 'regular_path']);

        $data = $fonts->map(function ($font) {
            $ext = $font->regular_path ? strtolower(pathinfo($font->regular_path, PATHINFO_EXTENSION)) : 'ttf';
            return [
                'id' => $font->id,
                'family_name' => $font->family_name,
                'format' => $ext === 'otf' ? 'opentype' : 'truetype',
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    /**
     * Serve the regular font file for preview (authenticated users).
     */
    public function serveFontFile($id)
    {
        $font = PdfFont::active()->findOrFail($id);

        if (!$font->regular_path) {
            abort(404);
        }

        // Files are stored on 'local' disk (root = storage/app/private)
        $path = Storage::disk('local')->path($font->regular_path);

        if (!file_exists($path)) {
            abort(404);
        }

        $mime = pathinfo($path, PATHINFO_EXTENSION) === 'otf' ? 'font/otf' : 'font/ttf';

        return response()->file($path, [
            'Content-Type' => $mime,
        ]);
    }
}
