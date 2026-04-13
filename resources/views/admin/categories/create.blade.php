@extends('layout.header')

@section('title', 'Create Department')

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1 class="h4 mb-0">Create department</h1>
        </div>

        <div class="wa-card">
            <div class="card-body">
                <form action="{{ route('admin.categories.store') }}" method="POST">
                    @csrf

                    <div class="form-group">
                        <label for="name">Department name</label>
                        <input
                            type="text"
                            class="form-control wa-input @error('name') is-invalid @enderror"
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            required
                        >
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Example: General Medicine, Dental Care, Pediatrics, ENT.</small>
                    </div>

                    <div class="form-group">
                        <label for="description">Description (dummy examples)</label>
                        <textarea
                            class="form-control wa-input @error('description') is-invalid @enderror"
                            id="description"
                            name="description"
                            rows="3"
                        >{{ old('description', 'Walk-in OPD for general consultations, basic vitals, and prescriptions.') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input
                                type="checkbox"
                                class="custom-control-input"
                                id="is_active"
                                name="is_active"
                                value="1"
                                {{ old('is_active', true) ? 'checked' : '' }}
                            >
                            <label class="custom-control-label" for="is_active">Active</label>
                        </div>
                    </div>

                    <div class="form-group text-right">
                        <a href="{{ route('admin.categories.index') }}" class="btn btn-light wa-action-btn">Cancel</a>
                        <button type="submit" class="btn btn-primary wa-action-btn"><i class="fas fa-floppy-disk mr-1"></i> Save department</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>
@endsection

