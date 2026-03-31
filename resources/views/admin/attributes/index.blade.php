@extends('admin.layouts.app')

@section('title', 'Product Attributes')
@section('page-title', 'Product Attributes')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Manage Attributes (Size, Color, etc.)</span>
        <a href="{{ route('admin.attributes.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus"></i> Add Attribute
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Values</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attributes as $attribute)
                        <tr>
                            <td>{{ $attribute->id }}</td>
                            <td>
                                <strong>{{ $attribute->name }}</strong>
                                <div class="small text-muted">{{ $attribute->slug }}</div>
                            </td>
                            <td>
                                @foreach($attribute->values as $value)
                                    @if($value->color_code)
                                        <span class="badge" style="background-color: {{ $value->color_code }}; color: {{ $value->color_code == '#FFFFFF' || $value->color_code == '#fff' ? '#000' : '#fff' }}">
                                            {{ $value->value }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">{{ $value->value }}</span>
                                    @endif
                                @endforeach
                                @if($attribute->values->isEmpty())
                                    <span class="text-muted small">No values</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.attributes.edit', $attribute) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form action="{{ route('admin.attributes.destroy', $attribute) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this attribute and all its values?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                No attributes found. Create your first attribute like "Size" or "Color".
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>
@endsection
