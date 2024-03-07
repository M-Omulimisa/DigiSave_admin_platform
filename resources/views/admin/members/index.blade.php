{{-- @extends('admin.layouts.content') --}}


<link href=
"https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity=
"sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC"
          crossorigin="anonymous">

@section('content')
<div class="card bg-white p-4">
    <div class="card-header">
        <h3 class="card-title">{{ $title }}</h3>
    </div>

    <div class="card-body">
        <p>{{ $description }}</p>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-dark  table-striped table-bordered" style="width:100%">
                <thead class="thead-dark">
                    <tr>
                        <th>{{ __('First name') }}</th>
                        <th>{{ __('Last name') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Gender') }}</th>
                        <th>{{ __('Phone Number') }}</th>
                        <th>{{ __('Address') }}</th>
                        <th>{{ __('Date Joined') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($members as $member)
                        <tr>
                            <td>{{ $member->first_name }}</td>
                            <td>{{ $member->last_name }}</td>
                            <td>{{ $member->email }}</td>
                            <td>{{ $member->sex }}</td>
                            <td>{{ $member->phone_number }}</td>
                            <td>{{ $member->address }}</td>
                            <td>{{ $member->created_at->format('d M Y') }}</td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        {{ __('Actions') }}
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#editModal">{{ __('Edit') }}</a>
                                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#deleteModal">{{ __('Delete') }}</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">{{ __('Edit Member') }}</h5>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Add your edit form here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-primary">{{ __('Save changes') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">{{ __('Delete Member') }}</h5>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{ __('Are you sure you want to delete this member?') }}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-danger">{{ __('Delete') }}</button>
            </div>
        </div>
    </div>
</div>

@endsection
