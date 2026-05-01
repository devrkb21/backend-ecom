@extends('admin.layouts.app')

@section('title', 'View Message')
@section('page-title', 'Contact Message')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="mb-3">
            <a href="{{ route('admin.contact-messages.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Inbox
            </a>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">
                    <i class="bi bi-envelope-open me-2"></i>{{ $contactMessage->subject ?: '(No Subject)' }}
                </h6>
                <span class="badge {{ $contactMessage->is_read ? 'bg-secondary' : 'bg-primary' }}">
                    {{ $contactMessage->is_read ? 'Read' : 'Unread' }}
                </span>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted small text-uppercase fw-semibold">From</label>
                            <div class="fw-semibold">{{ $contactMessage->first_name }} {{ $contactMessage->last_name }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted small text-uppercase fw-semibold">Email</label>
                            <div>
                                <a href="mailto:{{ $contactMessage->email }}" class="text-decoration-none">
                                    {{ $contactMessage->email }}
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label text-muted small text-uppercase fw-semibold">Date</label>
                            <div>{{ $contactMessage->created_at->format('F j, Y \a\t g:i A') }}</div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="mt-4">
                    <label class="form-label text-muted small text-uppercase fw-semibold">Message</label>
                    <div class="bg-light rounded p-4 mt-2" style="white-space: pre-wrap; line-height: 1.8;">{{ $contactMessage->message }}</div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="mailto:{{ $contactMessage->email }}?subject=Re: {{ urlencode($contactMessage->subject ?: 'Your message') }}" class="btn btn-primary">
                    <i class="bi bi-reply me-1"></i> Reply via Email
                </a>
                <form action="{{ route('admin.contact-messages.destroy', $contactMessage) }}" method="POST" onsubmit="return confirm('Delete this message?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="bi bi-trash me-1"></i> Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
