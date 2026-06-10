<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecruiterJobResource;
use App\Models\JobApplication;
use App\Models\JobEducationCatalog;
use App\Models\JobSkillCatalog;
use App\Models\RecruiterJob;
use App\Services\RecruiterJobClosureService;
use App\Services\RecruiterResumeAccessService;
use Carbon\Carbon;
use App\Support\AdminPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class JobController extends Controller
{
    public function __construct(
        private readonly RecruiterResumeAccessService $access,
        private readonly RecruiterJobClosureService $jobClosure,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->jobClosure->closeExpiredOpenJobs();

        $perPage = AdminPagination::resolve($request);
        $userId = $request->user()->id;

        $baseQuery = RecruiterJob::query()->where('created_by_user_id', $userId);

        $statusCounts = (clone $baseQuery)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $query = (clone $baseQuery)
            ->withCount('applications')
            ->orderByDesc('updated_at');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = trim((string) $request->input('search'))) {
            $term = '%'.$search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('company_name', 'like', $term)
                    ->orWhere('location', 'like', $term);
            });
        }

        $paginator = $query->paginate($perPage);

        $counts = [
            'all' => (int) (clone $baseQuery)->count(),
            'draft' => (int) ($statusCounts['draft'] ?? 0),
            'open' => (int) ($statusCounts['open'] ?? 0),
            'closed' => (int) ($statusCounts['closed'] ?? 0),
        ];

        return response()->json([
            'status' => true,
            'data' => [
                'data' => RecruiterJobResource::collection($paginator->items())->resolve(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'counts' => $counts,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateJob($request);

        $status = $validated['status'] ?? 'draft';
        if ($status === 'open' && ($limit = $this->access->openJobLimitExceeded($request->user()))) {
            return response()->json(['status' => false, 'message' => $limit], 429);
        }

        $job = RecruiterJob::create([
            'created_by_user_id' => $request->user()->id,
            'slug' => RecruiterJob::uniqueSlug($validated['title']),
            ...$this->jobAttributesFromValidated($validated),
        ]);

        $this->handleLogoUpload($request, $job);
        if ($job->isDirty('company_logo')) {
            $job->save();
        }

        return response()->json([
            'status' => true,
            'data' => new RecruiterJobResource($job),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $job = RecruiterJob::query()
            ->where('created_by_user_id', $request->user()->id)
            ->withCount('applications')
            ->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => new RecruiterJobResource($job),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $job = RecruiterJob::query()
            ->where('created_by_user_id', $request->user()->id)
            ->findOrFail($id);

        $validated = $this->validateJob($request, true);

        if (
            isset($validated['status'])
            && $validated['status'] === 'open'
            && $job->status !== 'open'
            && ($limit = $this->access->openJobLimitExceeded($request->user()))
        ) {
            return response()->json(['status' => false, 'message' => $limit], 429);
        }

        if (isset($validated['title']) && $validated['title'] !== $job->title) {
            $job->slug = RecruiterJob::uniqueSlug($validated['title']);
        }

        $job->fill($this->jobAttributesFromValidated($validated, $job));
        $this->handleLogoUpload($request, $job);
        $job->save();

        return response()->json([
            'status' => true,
            'data' => new RecruiterJobResource($job->fresh()),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $job = RecruiterJob::query()
            ->where('created_by_user_id', $request->user()->id)
            ->findOrFail($id);

        if ($job->company_logo && Storage::disk('public')->exists($job->company_logo)) {
            Storage::disk('public')->delete($job->company_logo);
        }

        $job->delete();

        return response()->json(['status' => true, 'message' => 'Job deleted.']);
    }

    public function applications(Request $request, int $id): JsonResponse
    {
        $job = RecruiterJob::query()
            ->where('created_by_user_id', $request->user()->id)
            ->findOrFail($id);

        $perPage = AdminPagination::resolve($request);
        $query = JobApplication::query()
            ->where('job_id', $job->id)
            ->with(['user:id,name,email,avatar', 'resume.template', 'resume.basicInfo'])
            ->orderByDesc('applied_at');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $paginator = $query->paginate($perPage);

        return response()->json([
            'status' => true,
            'data' => [
                'job' => new RecruiterJobResource($job),
                'applications' => [
                    'data' => $paginator->items(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
        ]);
    }

    private function validateJob(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        $rules = [
            'title' => "{$required}|string|max:255",
            'description' => "{$required}|string|min:30",
            'status' => 'nullable|in:draft,open,closed',
            'company_name' => 'nullable|string|max:255',
            'company_industry' => 'nullable|string|max:120',
            'company_size' => 'nullable|string|max:40',
            'company_description' => 'nullable|string|max:2000',
            'company_website' => 'nullable|url|max:500',
            'location_type' => ['nullable', Rule::in(RecruiterJob::LOCATION_TYPES)],
            'location_city' => 'nullable|string|max:120',
            'location_country' => 'nullable|string|max:120',
            'office_details' => 'nullable|string|max:2000',
            'employment_type' => ['nullable', Rule::in(RecruiterJob::EMPLOYMENT_TYPES)],
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0',
            'salary_currency' => 'nullable|string|size:3',
            'required_skills' => 'nullable|array|max:50',
            'required_skills.*' => ['string', 'max:120', Rule::in(JobSkillCatalog::activeNames())],
            'experience_min_years' => 'nullable|integer|min:0|max:60',
            'experience_max_years' => 'nullable|integer|min:0|max:60',
            'education_requirements' => 'nullable|array|max:15',
            'education_requirements.*' => ['string', 'max:200', Rule::in(JobEducationCatalog::activeNames())],
            'location' => 'nullable|string|max:255',
            'application_closes_at' => 'nullable|date',
            'company_logo' => 'nullable|image|max:2048',
        ];

        if ($request->input('location_type') === 'onsite') {
            $rules['location_city'] = 'required|string|max:120';
            $rules['location_country'] = 'required|string|max:120';
        }

        if ($request->has('required_skills') && is_string($request->input('required_skills'))) {
            $decoded = json_decode($request->input('required_skills'), true);
            if (is_array($decoded)) {
                $request->merge(['required_skills' => $decoded]);
            }
        }

        if ($request->has('education_requirements') && is_string($request->input('education_requirements'))) {
            $decoded = json_decode($request->input('education_requirements'), true);
            if (is_array($decoded)) {
                $request->merge(['education_requirements' => $decoded]);
            }
        }

        $validated = Validator::make($request->all(), $rules)->validate();

        if (
            isset($validated['salary_min'], $validated['salary_max'])
            && $validated['salary_max'] < $validated['salary_min']
        ) {
            throw ValidationException::withMessages([
                'salary_max' => ['Maximum salary must be greater than or equal to minimum salary.'],
            ]);
        }

        if (
            isset($validated['experience_min_years'], $validated['experience_max_years'])
            && $validated['experience_max_years'] < $validated['experience_min_years']
        ) {
            throw ValidationException::withMessages([
                'experience_max_years' => ['Maximum experience must be greater than or equal to minimum.'],
            ]);
        }

        if ($request->has('application_closes_at') && $request->input('application_closes_at') === '') {
            $validated['application_closes_at'] = null;
        } elseif (array_key_exists('application_closes_at', $validated) && $validated['application_closes_at']) {
            $validated['application_closes_at'] = Carbon::parse($validated['application_closes_at'])->endOfDay();
        }

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function jobAttributesFromValidated(array $validated, ?RecruiterJob $existing = null): array
    {
        $keys = [
            'title',
            'description',
            'status',
            'application_closes_at',
            'company_name',
            'company_industry',
            'company_size',
            'company_description',
            'company_website',
            'location_type',
            'location_city',
            'location_country',
            'office_details',
            'employment_type',
            'salary_min',
            'salary_max',
            'salary_currency',
            'required_skills',
            'experience_min_years',
            'experience_max_years',
            'education_requirements',
            'location',
        ];

        $attrs = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $validated)) {
                $attrs[$key] = $validated[$key];
            }
        }

        if (! array_key_exists('salary_currency', $attrs) && ! $existing) {
            $attrs['salary_currency'] = 'USD';
        }

        if (array_key_exists('required_skills', $attrs) && is_array($attrs['required_skills'])) {
            $attrs['required_skills'] = array_values(array_filter(array_map('trim', $attrs['required_skills'])));
        }

        if (array_key_exists('education_requirements', $attrs) && is_array($attrs['education_requirements'])) {
            $attrs['education_requirements'] = array_values(array_filter(array_map('trim', $attrs['education_requirements'])));
        }

        return $attrs;
    }

    private function handleLogoUpload(Request $request, RecruiterJob $job): void
    {
        if (! $request->hasFile('company_logo')) {
            return;
        }

        try {
            $file = $request->file('company_logo');
            if (! $file || ! $file->isValid()) {
                return;
            }

            $dir = 'job-logos';
            if (! Storage::disk('public')->exists($dir)) {
                Storage::disk('public')->makeDirectory($dir, 0755, true);
            }

            if ($job->company_logo && Storage::disk('public')->exists($job->company_logo)) {
                Storage::disk('public')->delete($job->company_logo);
            }

            $filename = 'job-'.$job->id.'-'.time().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs($dir, $filename, 'public');
            if ($path) {
                $job->company_logo = $path;
            }
        } catch (\Throwable $e) {
            Log::error('Job logo upload failed', ['error' => $e->getMessage()]);
        }
    }
}
