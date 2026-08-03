@extends('admin.layouts.app')

@section('title', 'Media Library')
@section('page-title', 'Media Library')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-images me-2"></i>Media Library</h6>
        <div class="d-flex align-items-center gap-2">
            <form action="{{ route('admin.media.bulk-convert-webp') }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to convert all existing library images to WebP? This will replace old files and update all database links.')" data-no-admin-ajax="1">
                @csrf
                <button type="submit" class="btn btn-warning btn-sm">
                    <i class="bi bi-magic me-1"></i> Convert All to WebP
                </button>
            </form>
            <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('media-upload-input').click()">
                <i class="bi bi-cloud-upload me-1"></i> Upload Files
            </button>
        </div>
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
                                <a href="{{ $item->url }}" target="_blank" class="btn btn-outline-secondary btn-sm" title="Open in new tab">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
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
                    showAdminToast(data.message || 'Failed to delete file.', 'danger');
                }
            })
            .catch(err => {
                console.error(err);
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                showAdminToast('An error occurred while deleting.', 'danger');
            });
        });
    });

    // WebP Bulk conversion queue
    const bulkConvertForm = document.querySelector('form[action$="bulk-convert-webp"]');
    if (bulkConvertForm) {
        bulkConvertForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to convert all existing library images to WebP? This will replace old files and update all database links.')) {
                return;
            }

            const modal = new bootstrap.Modal(document.getElementById('webpConvertModal'));
            modal.show();

            const progressBar      = document.getElementById('webp-progress-bar');
            const progressPercent  = document.getElementById('webp-progress-percent');
            const currentFileText  = document.getElementById('webp-current-file');
            const progressCounts   = document.getElementById('webp-progress-counts');
            const processingView   = document.getElementById('webp-processing-view');
            const successView      = document.getElementById('webp-success-view');
            const successMessage   = document.getElementById('webp-success-message');
            const itemLog          = document.getElementById('webp-item-log');
            
            const csrfToken = bulkConvertForm.querySelector('input[name="_token"]').value;

            // Step 1: Fetch list of image IDs to convert
            fetch(bulkConvertForm.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success || !data.items || data.items.length === 0) {
                    currentFileText.textContent = 'No images need conversion.';
                    progressCounts.textContent  = 'Everything is already WebP!';
                    progressBar.style.width      = '100%';
                    progressPercent.textContent  = '100%';
                    setTimeout(() => {
                        processingView.classList.add('d-none');
                        successView.classList.remove('d-none');
                        successMessage.textContent = 'No conversion was needed — all images are already WebP.';
                    }, 800);
                    return;
                }

                const total = data.items.length;
                let processed = 0;
                let succeeded = 0;
                let failed    = 0;

                const processQueue = () => {
                    if (processed >= total) {
                        // Complete
                        progressBar.style.width    = '100%';
                        progressPercent.textContent = '100%';
                        progressCounts.textContent  = `Done: ${succeeded} converted, ${failed} failed.`;
                        setTimeout(() => {
                            processingView.classList.add('d-none');
                            successView.classList.remove('d-none');
                            if (failed === 0) {
                                successMessage.textContent = `Successfully converted ${succeeded} image(s) to WebP.`;
                            } else {
                                successMessage.innerHTML = `Converted <strong>${succeeded}</strong> image(s). ` +
                                    `<span class="text-danger">${failed} failed</span> — check server logs for details.`;
                            }
                        }, 500);
                        return;
                    }

                    const currentItem = data.items[processed];
                    currentFileText.textContent = `Converting: ${currentItem.name}`;
                    progressCounts.textContent  = `Processing ${processed + 1} of ${total} — ${succeeded} done, ${failed} failed`;

                    // Update progress bar
                    const percent = Math.round((processed / total) * 100);
                    progressBar.style.width    = `${percent}%`;
                    progressPercent.textContent = `${percent}%`;

                    // Call single item conversion
                    fetch(`/admin/media/${currentItem.id}/convert-webp`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json().then(body => ({ ok: res.ok, body })))
                    .then(({ ok, body }) => {
                        if (ok && body.success) {
                            succeeded++;
                            appendLog(itemLog, currentItem.name, true, body.message || 'Converted');
                        } else {
                            failed++;
                            appendLog(itemLog, currentItem.name, false, body.message || 'Conversion failed');
                        }
                    })
                    .catch(err => {
                        failed++;
                        appendLog(itemLog, currentItem.name, false, 'Network or server error');
                        console.error('Conversion error for', currentItem.name, err);
                    })
                    .finally(() => {
                        processed++;
                        processQueue();
                    });
                };

                // Start queue
                processQueue();
            })
            .catch(err => {
                console.error(err);
                showAdminToast('Failed to initialize bulk WebP conversion.', 'danger');
                modal.hide();
            });
        });
    }

    function appendLog(container, name, success, message) {
        const row = document.createElement('div');
        row.className = `d-flex align-items-center gap-2 py-1 border-bottom small ${success ? 'text-success' : 'text-danger'}`;
        row.innerHTML = `<i class="bi ${success ? 'bi-check-circle-fill' : 'bi-x-circle-fill'}"></i>` +
                        `<span class="text-truncate flex-grow-1" title="${name}">${name}</span>` +
                        `<span class="text-muted">${message}</span>`;
        container.prepend(row);
    }
});
</script>

{{-- WebP Bulk Conversion Progress Modal --}}
<div class="modal fade" id="webpConvertModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="webpConvertModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-warning text-dark border-0 py-3">
                <h5 class="modal-title fw-bold" id="webpConvertModalLabel">
                    <i class="bi bi-magic me-2"></i>Converting Images to WebP
                </h5>
            </div>
            <div class="modal-body p-4">
                <div id="webp-processing-view">
                    <div class="text-center mb-3">
                        <div class="spinner-grow text-warning mb-3" style="width: 2.5rem; height: 2.5rem;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <h6 class="fw-semibold mb-1" id="webp-current-file">Initializing conversion queue...</h6>
                        <p class="text-muted small mb-3" id="webp-progress-counts">Please wait...</p>

                        <div class="progress mb-1" style="height: 12px; border-radius: 6px; background-color: #f0f0f0;">
                            <div id="webp-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-warning" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <span class="text-dark fw-bold small" id="webp-progress-percent">0%</span>
                    </div>

                    {{-- Per-item log --}}
                    <div id="webp-item-log" class="mt-3 border rounded p-2" style="max-height: 220px; overflow-y: auto; font-size: 0.8rem; background: #f8f9fa;">
                        <div class="text-muted text-center small py-2">Conversion log will appear here...</div>
                    </div>
                </div>

                <div id="webp-success-view" class="d-none text-center">
                    <div class="mb-3 text-success">
                        <i class="bi bi-check-circle-fill" style="font-size: 3.5rem;"></i>
                    </div>
                    <h5 class="fw-bold text-success mb-1">Conversion Complete!</h5>
                    <p class="text-muted" id="webp-success-message">Successfully converted all images.</p>
                    <button type="button" class="btn btn-success px-4" onclick="window.location.reload()">
                        Done
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endpush
