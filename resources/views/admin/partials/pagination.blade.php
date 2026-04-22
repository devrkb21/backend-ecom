@props(['paginator', 'perPageOptions' => [20, 50, 100]])

@php
    $currentPerPage = (int) ($paginator->perPage() ?? 20);
@endphp

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 px-3 py-2">
    {{-- Per-page selector --}}
    <div class="d-flex align-items-center gap-2">
        <label class="text-muted small mb-0 text-nowrap" for="perPageSelect">Show</label>
        <select
            class="form-select form-select-sm"
            id="perPageSelect"
            style="width: auto; min-width: 75px;"
            onchange="(function(el){
                var url = new URL(window.location.href);
                url.searchParams.set('per_page', el.value);
                url.searchParams.delete('page');
                window.location.href = url.toString();
            })(this)"
        >
            @foreach($perPageOptions as $option)
                <option value="{{ $option }}" {{ $currentPerPage === (int) $option ? 'selected' : '' }}>
                    {{ $option }}
                </option>
            @endforeach
        </select>
        <span class="text-muted small mb-0 text-nowrap">per page</span>
        <span class="text-muted small mb-0 ms-2 text-nowrap">
            — Showing {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }}
        </span>
    </div>

    {{-- Pagination links --}}
    @if($paginator->hasPages())
        <nav aria-label="Pagination navigation">
            <ul class="pagination pagination-sm mb-0">
                {{-- Previous --}}
                @if($paginator->onFirstPage())
                    <li class="page-item disabled">
                        <span class="page-link"><i class="bi bi-chevron-left"></i></span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                @endif

                {{-- Page numbers with smart truncation --}}
                @php
                    $currentPage = $paginator->currentPage();
                    $lastPage = $paginator->lastPage();
                    $window = 2;
                    $pages = [];

                    // Always show first page
                    $pages[] = 1;

                    // Calculate range around current page
                    $start = max(2, $currentPage - $window);
                    $end = min($lastPage - 1, $currentPage + $window);

                    // Add dots before the window
                    if ($start > 2) {
                        $pages[] = '...';
                    }

                    // Add pages in the window
                    for ($i = $start; $i <= $end; $i++) {
                        $pages[] = $i;
                    }

                    // Add dots after the window
                    if ($end < $lastPage - 1) {
                        $pages[] = '...';
                    }

                    // Always show last page (if more than 1)
                    if ($lastPage > 1) {
                        $pages[] = $lastPage;
                    }
                @endphp

                @foreach($pages as $page)
                    @if($page === '...')
                        <li class="page-item disabled">
                            <span class="page-link">…</span>
                        </li>
                    @else
                        <li class="page-item {{ (int) $page === $currentPage ? 'active' : '' }}">
                            <a class="page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                        </li>
                    @endif
                @endforeach

                {{-- Next --}}
                @if($paginator->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                @else
                    <li class="page-item disabled">
                        <span class="page-link"><i class="bi bi-chevron-right"></i></span>
                    </li>
                @endif
            </ul>
        </nav>
    @endif
</div>
