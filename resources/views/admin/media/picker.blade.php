{{-- 
    Media Library Picker Modal Component
    
    Usage:
    1. Include this partial in your view: @include('admin.media.picker')
    2. Add a button to open the picker: <button type="button" onclick="openMediaPicker('target-input-id', false)">Select Image</button>
    3. Have a hidden input to store selected media: <input type="hidden" id="target-input-id" name="image_id">
    4. Optionally have a preview element: <img id="target-input-id-preview" src="">
    
    For multiple selection, pass true as second parameter: openMediaPicker('target-input-id', true)
--}}

<div class="modal fade" id="mediaPickerModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-images"></i> Media Library</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{-- Tabs: Library / Upload --}}
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#media-library-tab" type="button">
                            <i class="bi bi-grid"></i> Media Library
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#media-upload-tab" type="button">
                            <i class="bi bi-cloud-upload"></i> Upload New
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- Library Tab --}}
                    <div class="tab-pane fade show active" id="media-library-tab">
                        {{-- Search --}}
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="input-group input-group-sm">
                                    <input type="text" id="media-picker-search" class="form-control" placeholder="Search...">
                                    <button type="button" class="btn btn-outline-secondary" onclick="loadMediaLibrary()">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-8 text-end">
                                <span id="media-selection-count" class="text-muted small"></span>
                            </div>
                        </div>

                        {{-- Media Grid --}}
                        <div id="media-picker-grid" class="row g-2" style="max-height: 400px; overflow-y: auto;">
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="text-muted mt-2">Loading media...</p>
                            </div>
                        </div>

                        {{-- Pagination --}}
                        <div id="media-picker-pagination" class="d-flex justify-content-center mt-3"></div>
                    </div>

                    {{-- Upload Tab --}}
                    <div class="tab-pane fade" id="media-upload-tab">
                        <div id="media-upload-dropzone" class="border-2 border-dashed rounded p-5 text-center" 
                             style="border-style: dashed; cursor: pointer;"
                             ondragover="event.preventDefault(); this.classList.add('bg-light');"
                             ondragleave="this.classList.remove('bg-light');"
                             ondrop="handleDrop(event)"
                             onclick="document.getElementById('media-picker-file-input').click()">
                            <i class="bi bi-cloud-upload text-muted" style="font-size: 3rem;"></i>
                            <p class="mt-3 mb-1">Drag & drop files here or click to browse</p>
                            <small class="text-muted">Accepted: JPG, PNG, GIF, WebP (Max 5MB)</small>
                            <input type="file" id="media-picker-file-input" class="d-none" multiple accept="image/*" onchange="uploadFiles(this.files)">
                        </div>

                        {{-- Upload Progress --}}
                        <div id="upload-progress-container" class="mt-3 d-none">
                            <div class="progress">
                                <div id="upload-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%"></div>
                            </div>
                            <p id="upload-status" class="small text-muted mt-1"></p>
                        </div>

                        {{-- Recently Uploaded --}}
                        <div id="recently-uploaded" class="row g-2 mt-3"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="media-picker-select-btn" onclick="confirmMediaSelection()" disabled>
                    <i class="bi bi-check"></i> Select
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Media Picker State
let mediaPickerState = {
    targetInputId: null,
    multiple: false,
    selectedMedia: [],
    currentPage: 1,
    onSelect: null // callback function
};

// Open the media picker
function openMediaPicker(targetInputId, multiple = false, onSelect = null) {
    mediaPickerState.targetInputId = targetInputId;
    mediaPickerState.multiple = multiple;
    mediaPickerState.selectedMedia = [];
    mediaPickerState.currentPage = 1;
    mediaPickerState.onSelect = onSelect;
    
    updateSelectionCount();
    document.getElementById('media-picker-select-btn').disabled = true;
    
    loadMediaLibrary();
    
    new bootstrap.Modal(document.getElementById('mediaPickerModal')).show();
}

// Load media library via AJAX
function loadMediaLibrary(page = 1) {
    const search = document.getElementById('media-picker-search').value;
    const grid = document.getElementById('media-picker-grid');
    
    grid.innerHTML = '<div class="text-center py-5 w-100"><div class="spinner-border text-primary" role="status"></div></div>';
    
    fetch(`{{ route('admin.media.list') }}?page=${page}&search=${encodeURIComponent(search)}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            renderMediaGrid(data.data);
            renderPagination(data.pagination);
            mediaPickerState.currentPage = page;
        }
    })
    .catch(err => {
        grid.innerHTML = '<div class="text-center py-5 w-100 text-danger">Failed to load media</div>';
    });
}

// Render media grid
function renderMediaGrid(items) {
    const grid = document.getElementById('media-picker-grid');
    
    if (!items.length) {
        grid.innerHTML = '<div class="text-center py-5 w-100 text-muted"><i class="bi bi-images" style="font-size: 2rem;"></i><p class="mt-2">No media found</p></div>';
        return;
    }
    
    grid.innerHTML = items.map(item => `
        <div class="col-4 col-md-2">
            <div class="media-picker-item card h-100 ${mediaPickerState.selectedMedia.find(m => m.id === item.id) ? 'border-primary border-2' : ''}" 
                 data-id="${item.id}" 
                 data-url="${item.url}"
                 data-path="${item.path}"
                 onclick="toggleMediaSelection(${item.id}, '${item.url}', '${item.path}')"
                 style="cursor: pointer;">
                <div class="position-relative" style="padding-top: 100%;">
                    <img src="${item.url}" alt="${item.alt_text || item.original_name}" 
                         class="position-absolute top-0 start-0 w-100 h-100" style="object-fit: cover;">
                    ${mediaPickerState.selectedMedia.find(m => m.id === item.id) ? 
                        '<div class="position-absolute top-0 end-0 m-1"><span class="badge bg-primary"><i class="bi bi-check"></i></span></div>' : ''}
                </div>
                <div class="card-body p-1">
                    <small class="text-truncate d-block" style="font-size: 10px;">${item.original_name}</small>
                </div>
            </div>
        </div>
    `).join('');
}

// Render pagination
function renderPagination(pagination) {
    const container = document.getElementById('media-picker-pagination');
    
    if (pagination.last_page <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '<nav><ul class="pagination pagination-sm mb-0">';
    
    // Previous
    html += `<li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="event.preventDefault(); loadMediaLibrary(${pagination.current_page - 1})">«</a>
    </li>`;
    
    // Pages
    for (let i = 1; i <= pagination.last_page; i++) {
        if (i === 1 || i === pagination.last_page || (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
            html += `<li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                <a class="page-link" href="#" onclick="event.preventDefault(); loadMediaLibrary(${i})">${i}</a>
            </li>`;
        } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    // Next
    html += `<li class="page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="event.preventDefault(); loadMediaLibrary(${pagination.current_page + 1})">»</a>
    </li>`;
    
    html += '</ul></nav>';
    container.innerHTML = html;
}

// Toggle media selection
function toggleMediaSelection(id, url, path) {
    const index = mediaPickerState.selectedMedia.findIndex(m => m.id === id);
    
    if (index > -1) {
        // Deselect
        mediaPickerState.selectedMedia.splice(index, 1);
    } else {
        // Select
        if (!mediaPickerState.multiple) {
            mediaPickerState.selectedMedia = [];
        }
        mediaPickerState.selectedMedia.push({ id, url, path });
    }
    
    // Re-render to show selection state
    loadMediaLibrary(mediaPickerState.currentPage);
    updateSelectionCount();
}

// Update selection count display
function updateSelectionCount() {
    const count = mediaPickerState.selectedMedia.length;
    document.getElementById('media-selection-count').textContent = count ? `${count} selected` : '';
    document.getElementById('media-picker-select-btn').disabled = count === 0;
}

// Confirm selection
function confirmMediaSelection() {
    if (mediaPickerState.selectedMedia.length === 0) return;
    
    const targetInput = document.getElementById(mediaPickerState.targetInputId);
    const previewEl = document.getElementById(mediaPickerState.targetInputId + '-preview');
    
    if (mediaPickerState.multiple) {
        // Multiple selection - callback handles it
        if (mediaPickerState.onSelect) {
            mediaPickerState.onSelect(mediaPickerState.selectedMedia);
        }
    } else {
        // Single selection
        const selected = mediaPickerState.selectedMedia[0];
        
        if (targetInput) {
            targetInput.value = selected.path;
        }
        
        if (previewEl) {
            previewEl.src = selected.url;
            previewEl.classList.remove('d-none');
        }
        
        if (mediaPickerState.onSelect) {
            mediaPickerState.onSelect(selected);
        }
    }
    
    bootstrap.Modal.getInstance(document.getElementById('mediaPickerModal')).hide();
}

// Handle file drop
function handleDrop(event) {
    event.preventDefault();
    event.target.classList.remove('bg-light');
    
    const files = event.dataTransfer.files;
    if (files.length) {
        uploadFiles(files);
    }
}

// Upload files
function uploadFiles(files) {
    const formData = new FormData();
    
    for (let i = 0; i < files.length; i++) {
        formData.append('files[]', files[i]);
    }
    
    const progressContainer = document.getElementById('upload-progress-container');
    const progressBar = document.getElementById('upload-progress-bar');
    const statusText = document.getElementById('upload-status');
    
    progressContainer.classList.remove('d-none');
    progressBar.style.width = '0%';
    statusText.textContent = 'Uploading...';
    
    fetch('{{ route('admin.media.upload') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        progressBar.style.width = '100%';
        
        if (data.success) {
            statusText.textContent = data.message;
            
            // Show recently uploaded
            const container = document.getElementById('recently-uploaded');
            container.innerHTML = data.data.map(item => `
                <div class="col-4 col-md-2">
                    <div class="card media-picker-item" data-id="${item.id}" data-url="${item.url}" data-path="${item.path}"
                         onclick="toggleMediaSelection(${item.id}, '${item.url}', '${item.path}')" style="cursor: pointer;">
                        <div class="position-relative" style="padding-top: 100%;">
                            <img src="${item.url}" class="position-absolute top-0 start-0 w-100 h-100" style="object-fit: cover;">
                        </div>
                    </div>
                </div>
            `).join('');
            
            // Reload library
            setTimeout(() => loadMediaLibrary(1), 1000);
        } else {
            statusText.textContent = 'Upload failed';
        }
    })
    .catch(err => {
        progressBar.classList.add('bg-danger');
        statusText.textContent = 'Upload failed: ' + err.message;
    });
}

// Search on Enter
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('media-picker-search');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                loadMediaLibrary(1);
            }
        });
    }
});
</script>
@endpush
