@extends('admin.layouts.app')

@section('title', 'Contact Messages')
@section('page-title', 'Contact Messages')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-envelope-open me-2"></i>Inbox
                        @if($unreadCount > 0)
                            <span class="badge bg-danger ms-2">{{ $unreadCount }} unread</span>
                        @endif
                    </h6>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    @if($unreadCount > 0)
                        <form action="{{ route('admin.contact-messages.mark-all-read') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-check-all me-1"></i> Mark All Read
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.contact-messages.index') }}" method="GET" class="row g-3 mb-4" data-realtime-filter="1">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="search" placeholder="Search by name, email or subject..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="status">
                            <option value="">All Messages</option>
                            <option value="unread" {{ request('status') === 'unread' ? 'selected' : '' }}>Unread</option>
                            <option value="read" {{ request('status') === 'read' ? 'selected' : '' }}>Read</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i> Filter</button>
                        <a href="{{ route('admin.contact-messages.index') }}" class="btn btn-outline-secondary flex-fill">Reset</a>
                    </div>
                </form>

                @if($messages->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                        <p class="mt-3">No messages found.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;"></th>
                                    <th>Sender</th>
                                    <th>Subject</th>
                                    <th>Date</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($messages as $msg)
                                    <tr class="{{ !$msg->is_read ? 'fw-bold bg-light' : '' }}">
                                        <td>
                                            @if(!$msg->is_read)
                                                <span class="badge bg-primary rounded-pill">New</span>
                                            @else
                                                <i class="bi bi-envelope-open text-muted"></i>
                                            @endif
                                        </td>
                                        <td>
                                            <div>{{ $msg->first_name }} {{ $msg->last_name }}</div>
                                            <small class="text-muted">{{ $msg->email }}</small>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.contact-messages.show', $msg) }}" class="text-decoration-none text-dark">
                                                {{ $msg->subject ?: '(No Subject)' }}
                                            </a>
                                            <div class="text-muted small text-truncate" style="max-width: 300px;">
                                                {{ Str::limit(strip_tags($msg->message), 60) }}
                                            </div>
                                        </td>
                                        <td class="text-nowrap">
                                            <small class="text-muted">{{ $msg->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('admin.contact-messages.show', $msg) }}" class="btn btn-sm btn-outline-primary" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <form action="{{ route('admin.contact-messages.destroy', $msg) }}" method="POST" onsubmit="return confirm('Delete this message?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $messages->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
