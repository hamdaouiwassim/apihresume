<x-mail::message>
# Thanks for reaching out, {{ $name }}!

We received your request to activate recruiter access{{ $companyName ? ' for ' . $companyName : '' }}. Our team will review your details shortly and notify you as soon as your workspace is live.

### What happens next?
- We verify your company and hiring focus
- Once approved, you’ll be able to browse resumes and propose templates immediately
- If we need anything else, we’ll reach out to this email address

<x-mail::button :url="config('app.url') . '/login'">
Track My Request
</x-mail::button>

Need faster approval or have updates? Contact us at [support@cvbuilder.app](mailto:support@cvbuilder.app).

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
