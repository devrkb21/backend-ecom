@extends('admin.layouts.app')

@section('title', 'Customers')
@section('page-title', 'Customers (Loyalty Program)')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Customer Analytics</h5>
        <a href="{{ route('admin.customer-groups.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-award me-1"></i> Manage Customer Groups
        </a>
    </div>
    
    <!-- KPI Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm bg-white text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-muted mb-1">Total Unique Customers</h6>
                            <h2 class="mb-0 fw-bold">{{ number_format($kpis['total_customers']) }}</h2>
                        </div>
                        <div class="bg-primary bg-opacity-10 text-primary rounded p-3">
                            <i class="bi bi-people fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm bg-white text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-muted mb-1">Total Lifetime Revenue</h6>
                            <h2 class="mb-0 fw-bold">৳{{ number_format($kpis['total_revenue'], 2) }}</h2>
                        </div>
                        <div class="bg-success bg-opacity-10 text-success rounded p-3">
                            <i class="bi bi-cash-coin fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm bg-white text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-muted mb-1">Total Completed Orders</h6>
                            <h2 class="mb-0 fw-bold">{{ number_format($kpis['total_orders']) }}</h2>
                        </div>
                        <div class="bg-info bg-opacity-10 text-info rounded p-3">
                            <i class="bi bi-bag-check fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body border-bottom">
        <form action="{{ route('admin.customers.index') }}" method="GET" class="d-flex gap-2 w-md-50">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by phone, name, or email..." value="{{ request('search') }}">
            <button type="submit" class="btn btn-primary btn-sm">Search</button>
            @if(request('search'))
                <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            @endif
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Phone Number</th>
                    <th>Latest Name</th>
                    <th>Total Orders</th>
                    <th>Total Spent</th>
                    <th>Loyalty Group</th>
                    <th>Last Order Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td>
                            <strong>{{ $customer->phone }}</strong>
                        </td>
                        <td>
                            {{ $customer->latest_name }}<br>
                            <small class="text-muted">{{ $customer->latest_email }}</small>
                        </td>
                        <td>
                            <span class="badge bg-secondary rounded-pill">{{ $customer->total_orders }}</span>
                        </td>
                        <td>৳{{ number_format($customer->total_spent, 2) }}</td>
                        <td>
                            @if($customer->group)
                                <span class="badge bg-success">{{ $customer->group->name }}</span>
                                <br><small class="text-muted">{{ $customer->group->discount_percentage }}% Off</small>
                            @else
                                <span class="badge bg-light text-dark">Regular</span>
                            @endif
                        </td>
                        <td>{{ \Carbon\Carbon::parse($customer->last_order_date)->format('M d, Y h:i A') }}</td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle py-0 px-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li>
                                        <a href="{{ route('admin.customers.show', $customer->phone) }}" class="dropdown-item fw-bold">
                                            <i class="bi bi-person-vcard me-2 text-info"></i> View Profile
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editCustomerModal{{ $loop->index }}">
                                            <i class="bi bi-pencil me-2 text-primary"></i> Edit Details
                                        </button>
                                    </li>
                                    <li>
                                        <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#assignGroupModal{{ $loop->index }}">
                                            <i class="bi bi-award me-2 text-success"></i> Assign Group
                                        </button>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('admin.customers.destroy', $customer->phone) }}" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete all orders associated with this customer phone number? This action cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="bi bi-trash me-2"></i> Delete Customer
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>

                            <!-- Edit Customer Modal -->
                            <div class="modal fade text-start" id="editCustomerModal{{ $loop->index }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('admin.customers.update', $customer->phone) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Customer Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="alert alert-info py-2 small">
                                                    Updating these details will apply to all historical orders placed with phone number {{ $customer->phone }}.
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                                    <input type="text" name="phone" class="form-control" value="{{ $customer->phone }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Name <span class="text-danger">*</span></label>
                                                    <input type="text" name="name" class="form-control" value="{{ $customer->latest_name }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Email</label>
                                                    <input type="email" name="email" class="form-control" value="{{ $customer->latest_email }}">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Assign Group Modal -->
                            <div class="modal fade text-start" id="assignGroupModal{{ $loop->index }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('admin.customers.assign-group', $customer->phone) }}" method="POST">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title">Assign Loyalty Group</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="mb-3">Assign <strong>{{ $customer->latest_name }} ({{ $customer->phone }})</strong> to a loyalty group manually.</p>
                                                <div class="mb-3">
                                                    <label class="form-label">Select Group</label>
                                                    <select name="group_id" class="form-select">
                                                        <option value="">-- Auto Assign (Based on Orders/Spent) --</option>
                                                        @foreach($groups as $g)
                                                            <option value="{{ $g->id }}" {{ $customer->group && $customer->group->id == $g->id ? 'selected' : '' }}>
                                                                {{ $g->name }} ({{ $g->discount_percentage }}% Off)
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Assign Group</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No customers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer" id="customersPaginationWrap">
        @include('admin.partials.pagination', ['paginator' => $customers])
    </div>
</div>
@endsection
