<x-mail::message>
# You've been invited to collaborate! 🎉

**{{ $owner->name }}** has invited you to collaborate on their resume: **{{ $resume->name }}**

## Join HResume to Get Started

To accept this collaboration invitation, you'll need to create a free account on HResume first. Don't worry - it only takes a minute!

### Why Join HResume?

- ✨ **100% Free** - No subscriptions, no hidden fees, no credit card required
- 📝 **Professional Resume Builder** - Create unlimited resumes with ATS-friendly templates
- 💼 **Easy Collaboration** - Work together on resumes with others
- 🔒 **Secure & Private** - Your data is safe and secure
- 🌍 **Multi-language** - Support for English and French

### What Happens Next?

1. **Sign up** for a free HResume account using the button below
2. **Verify your email** (we'll send you a verification link)
3. **Accept the collaboration invitation** to start editing the resume
4. **Start building** your own professional resumes too!

<x-mail::button :url="$registerUrl" color="success">
Sign Up Free - Get Started
</x-mail::button>

Once you've signed up and verified your email, you can accept the collaboration invitation:

<x-mail::button :url="$acceptUrl">
Accept Collaboration Invitation
</x-mail::button>

## About the Invitation

**{{ $owner->name }}** wants your help editing their resume. Once you join and accept, you'll be able to:
- View and edit the resume together
- Make suggestions and improvements
- Download the resume as a PDF
- Collaborate in real-time

## Create Your Own Resume Too!

While you're at it, why not create your own professional resume? HResume makes it easy to build a standout resume that gets you noticed.

<x-mail::button :url="$createResumeUrl">
Create Your First Resume
</x-mail::button>

If you didn't expect this invitation, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }} Team

---

<small>This invitation was sent to {{ $invitedEmail }}. If this wasn't you, please ignore this email.</small>
</x-mail::message>

