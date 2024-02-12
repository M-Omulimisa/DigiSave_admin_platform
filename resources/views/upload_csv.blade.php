<!-- resources/views/admin/districts/upload_csv.blade.php -->

@extends('admin.layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Upload CSV File') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('admin.districts.import') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group row">
                            <label for="csv_file" class="col-md-4 col-form-label text-md-right">{{ __('CSV File') }}</label>

                            <div class="col-md-6">
                                <input id="csv_file" type="file" class="form-control @error('csv_file') is-invalid @enderror" name="csv_file" required>

                                @error('csv_file')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Upload') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
