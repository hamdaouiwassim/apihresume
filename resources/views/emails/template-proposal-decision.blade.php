<p>Hello,</p>

<p>
    Your template proposal <strong>{{ $proposal->name }}</strong> has been
    <strong>{{ $decision }}</strong>.
</p>

@if ($adminNotes)
    <p>Notes from our team:</p>
    <p>{{ $adminNotes }}</p>
@endif

<p>Thank you for contributing to {{ config('app.name', 'HResume') }}.</p>
