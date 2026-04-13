@extends('layout.header')

@section('title', 'Edit Admin User')

@section('content')
<div class="main-content">
    <section class="section">
        <div class="section-header">
            <h1 class="h4 mb-0">Edit admin user</h1>
        </div>

        <div class="wa-card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.users.update', $user) }}">
                    @csrf
                    @method('PUT')

                    @php
                        $isSuper = (bool) (auth()->user()?->is_super_admin);
                    @endphp

                    <div class="form-group">
                        <label>Name</label>
                        <input name="name" class="form-control wa-input @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input name="email" type="email" class="form-control wa-input @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label>Department (Category)</label>
                        <select name="category_id" class="form-control wa-select @error('category_id') is-invalid @enderror" {{ $isSuper ? '' : 'disabled' }}>
                            <option value="" {{ old('category_id', $user->category_id) ? '' : 'selected' }}>All departments (Super Admin only)</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c->id }}" {{ (string) old('category_id', $user->category_id) === (string) $c->id ? 'selected' : '' }}>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="d-flex align-items-center gap-2 mb-0">
                            <input type="checkbox" name="is_super_admin" value="1" {{ old('is_super_admin', $user->is_super_admin) ? 'checked' : '' }} {{ $isSuper ? '' : 'disabled' }}>
                            <span>Super Admin (access to all departments)</span>
                        </label>
                        @if (! $isSuper)
                            <small class="form-text text-muted">Only Super Admin can change department/super-admin access.</small>
                        @endif
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>New password <span class="text-muted small">(optional)</span></label>
                                <input name="password" type="password" class="form-control wa-input @error('password') is-invalid @enderror">
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Confirm new password</label>
                                <input name="password_confirmation" type="password" class="form-control wa-input">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary wa-action-btn" type="submit"><i class="fas fa-floppy-disk mr-1"></i>Save</button>
                        <a class="btn btn-light wa-action-btn" href="{{ route('admin.users.index') }}">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>
@endsection

