@extends('admin.layouts.app')

@section('title', 'Media Library')
@section('page-title', 'Media Library')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-images me-2"></i>Media Library</h6>
        <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('media-upload-input').click()">
            <i class="bi bi-cloud-upload me-1"></i> Upload Files
        </button>
    </div>
    <div class="card-body">
        {{-- Upload Form --}}
        <form id="upload-form" action="{{ route('admin.media.upload') }}" method="POST" enctype="multipart/form-data" class="d-none" data-no-admin-ajax="1">
            @csrf
            <input type="file" id="media-upload-input" name="files[]" multiple accept="image/*" onchange="this.form.submit()">
        </form>

        {{-- Search --}}
        <div class="row mb-4">
            <div class="col-md-4">
                <form action="{{ route('admin.media.index') }}" method="GET" data-realtime-filter="1">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" name="search" placeholder="Search media..." value="{{ request('search') }}">
                        <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
                    </div>
                </form>
            </div>
        </div>

        @if($media->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-images text-muted" style="font-size: 4rem;"></i>
                <p class="text-muted mt-3">No media files yet. Upload some images to get started.</p>
            </div>
        @else
            {{-- Media Grid --}}
            <div class="row g-3">
                @foreach($media as $item)
                    <div class="col-6 col-md-3 col-lg-2">
                        <div class="card h-100 media-item shadow-sm" data-id="{{ $item->id }}">
                            <div class="position-relative" style="padding-top: 100%;">
                                <img src="{{ $item->url }}" alt="{{ $item->alt_text ?? $item->original_name }}" 
                                     class="position-absolute top-0 start-0 w-100 h-100 rounded-top" style="object-fit: cover;">
                            </div>
                            <div class="card-body p-2">
                                <p class="small text-truncate mb-1" title="{{ $item->original_name }}">{{ $item->original_name }}</p>
                                <small class="text-muted">{{ $item->formatted_size }}</small>
                            </div>
                            <div class="card-footer p-2 d-flex gap-1">
                                <button type="button" class="btn btn-outline-primary btn-sm flex-grow-1" onclick="showMediaDetails({{ $item->id }})">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <form action="{{ route('admin.media.destroy', $item) }}" method="POST" class="d-inline media-delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                @include('admin.partials.pagination', ['paginator' => $media])
            </div>
        @endif
    </div>
</div>

{{-- Media Details Modal --}}
<div class="modal fade" id="mediaDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Media Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <img id="detail-image" src="" alt="" class="img-fluid rounded">
                    </div>
                    <div class="col-md-6">
                        <form id="media-update-form" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label class="form-label">File Name</label>
                                <p id="detail-filename" class="form-control-plaintext"></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Dimensions</label>
                                <p id="detail-dimensions" class="form-control-plaintext"></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Size</label>
                                <p id="detail-size" class="form-control-plaintext"></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">URL</label>
                                <div class="input-group">
                                    <input type="text" id="detail-url" class="form-control form-control-sm" readonly>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="copyUrl()">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </div>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <label for="detail-title" class="form-label">Title</label>
                                <input type="text" id="detail-title" name="title" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="detail-alt" class="form-label">Alt Text</label>
                                <input type="text" id="detail-alt" name="alt_text" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showMediaDetails(id) {
    fetch(`{{ url('admin/media') }}/${id}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const m = data.data;
            document.getElementById('detail-image').src = m.url;
            document.getElementById('detail-filename').textContent = m.original_name;
            document.getElementById('detail-dimensions').textContent = m.width && m.height ? `${m.width} x ${m.height}px` : 'N/A';
            document.getElementById('detail-size').textContent = m.formatted_size;
            document.getElementById('detail-url').value = m.url;
            document.getElementById('detail-title').value = m.title || '';
            document.getElementById('detail-alt').value = m.alt_text || '';
            document.getElementById('media-update-form').action = `{{ url('admin/media') }}/${id}`;
            
            new bootstrap.Modal(document.getElementById('mediaDetailsModal')).show();
        }
    });
}

function copyUrl() {
    const input = document.getElementById('detail-url');
    input.select();
    document.execCommand('copy');
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.media-delete-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!confirm('Delete this file?')) {
                return;
            }
            
            const btn = form.querySelector('button[type="submit"]');
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            
            const url = form.action;
            const csrfToken = form.querySelector('input[name="_token"]').value;
            const col = form.closest('.col-6');
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-HTTP-Method-Override': 'DELETE',
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    col.style.transition = 'all 0.3s ease';
                    col.style.opacity = '0';
                    col.style.transform = 'scale(0.8)';
                    setTimeout(() => {
                        col.remove();
                        const remaining = document.querySelectorAll('.media-item');
                        if (remaining.length === 0) {
                            window.location.reload();
                        }
                    }, 300);
                } else {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    alert(data.message || 'Failed to delete file.');
                }
            })
            .catch(err => {
                console.error(err);
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                alert('An error occurred while deleting.');
            });
        });
    });
});
</script>
@endpush
