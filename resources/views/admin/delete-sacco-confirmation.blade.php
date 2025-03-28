@extends('admin::index')

@section('content')
<div class="box box-danger">
    <div class="box-header with-border">
        <h3 class="box-title">Delete VSLA Group: {{ $sacco->name }}</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
        </div>
    </div>
    <div class="box-body">
        <div class="alert alert-warning">
            <h4><i class="fa fa-warning"></i> Warning!</h4>
            <p>You cannot delete this group until you have deleted all associated records listed below. Please delete each type of record before proceeding with the group deletion.</p>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="active"><a href="#users" data-toggle="tab">Members ({{ $users->count() }})</a></li>
                        <li><a href="#cycles" data-toggle="tab">Cycles ({{ $cycles->count() }})</a></li>
                        <li><a href="#meetings" data-toggle="tab">Meetings ({{ $meetings->count() }})</a></li>
                        <li><a href="#transactions" data-toggle="tab">Transactions ({{ $transactions->count() }})</a></li>
                        <li><a href="#loans" data-toggle="tab">Loans ({{ $loans->count() }})</a></li>
                        <li><a href="#positions" data-toggle="tab">Positions ({{ $positions->count() }})</a></li>
                        <li><a href="#loanSchemes" data-toggle="tab">Loan Schemes ({{ $loanSchemes->count() }})</a></li>
                        <li><a href="#socialFunds" data-toggle="tab">Social Funds ({{ $socialFunds->count() }})</a></li>
                        <li><a href="#orgAssociations" data-toggle="tab">Organization Links ({{ $orgAssociations->count() }})</a></li>
                    </ul>
                    <div class="tab-content">
                        <!-- Users Tab -->
                        <div class="tab-pane active" id="users">
                            @if($users->isEmpty())
                                <div class="alert alert-success">
                                    <p>No members associated with this group.</p>
                                </div>
                            @else
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Phone</th>
                                            <th>Email</th>
                                            <th>Position</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($users as $user)
                                            <tr>
                                                <td>{{ $user->id }}</td>
                                                <td>{{ $user->name ?? ($user->first_name . ' ' . $user->last_name) }}</td>
                                                <td>{{ $user->phone_number }}</td>
                                                <td>{{ $user->email }}</td>
                                                <td>{{ optional($user->position)->name ?? 'N/A' }}</td>
                                                <td>
                                                    <a href="{{ admin_url('members/'.$user->id) }}" class="btn btn-xs btn-primary">View</a>
                                                    <a href="{{ admin_url('members/'.$user->id.'/edit') }}" class="btn btn-xs btn-warning">Edit</a>
                                                    <form action="{{ admin_url('members/'.$user->id) }}" method="post" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this member?');">
                                                        {{ csrf_field() }}
                                                        {{ method_field('DELETE') }}
                                                        <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>

                        <!-- Cycles Tab -->
                        <div class="tab-pane" id="cycles">
                            @if($cycles->isEmpty())
                                <div class="alert alert-success">
                                    <p>No cycles associated with this group.</p>
                                </div>
                            @else
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($cycles as $cycle)
                                            <tr>
                                                <td>{{ $cycle->id }}</td>
                                                <td>{{ $cycle->name }}</td>
                                                <td>{{ $cycle->start_date }}</td>
                                                <td>{{ $cycle->end_date }}</td>
                                                <td>{{ $cycle->status }}</td>
                                                <td>
                                                    <a href="{{ admin_url('cycles/'.$cycle->id) }}" class="btn btn-xs btn-primary">View</a>
                                                    <a href="{{ admin_url('cycles/'.$cycle->id.'/edit') }}" class="btn btn-xs btn-warning">Edit</a>
                                                    <form action="{{ admin_url('cycles/'.$cycle->id) }}" method="post" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this cycle?');">
                                                        {{ csrf_field() }}
                                                        {{ method_field('DELETE') }}
                                                        <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>

                        <!-- Meetings Tab -->
                        <div class="tab-pane" id="meetings">
                            @if($meetings->isEmpty())
                                <div class="alert alert-success">
                                    <p>No meetings associated with this group.</p>
                                </div>
                            @else
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Date</th>
                                            <th>Location</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($meetings as $meeting)
                                            <tr>
                                                <td>{{ $meeting->id }}</td>
                                                <td>{{ $meeting->name }}</td>
                                                <td>{{ $meeting->date }}</td>
                                                <td>{{ $meeting->location }}</td>
                                                <td>
                                                    <a href="{{ admin_url('meetings/'.$meeting->id) }}" class="btn btn-xs btn-primary">View</a>
                                                    <a href="{{ admin_url('meetings/'.$meeting->id.'/edit') }}" class="btn btn-xs btn-warning">Edit</a>
                                                    <form action="{{ admin_url('meetings/'.$meeting->id) }}" method="post" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this meeting?');">
                                                        {{ csrf_field() }}
                                                        {{ method_field('DELETE') }}
                                                        <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>

                        <!-- Transactions Tab -->
                        <div class="tab-pane" id="transactions">
                            @if($transactions->isEmpty())
                                <div class="alert alert-success">
                                    <p>No transactions associated with this group.</p>
                                </div>
                            @else
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Type</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($transactions as $transaction)
                                            <tr>
                                                <td>{{ $transaction->id }}</td>
                                                <td>{{ $transaction->type }}</td>
                                                <td>{{ number_format($transaction->amount) }}</td>
                                                <td>{{ $transaction->created_at }}</td>
                                                <td>
                                                    <a href="{{ admin_url('transactions/'.$transaction->id) }}" class="btn btn-xs btn-primary">View</a>
                                                    <form action="{{ admin_url('transactions/'.$transaction->id) }}" method="post" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this transaction?');">
                                                        {{ csrf_field() }}
                                                        {{ method_field('DELETE') }}
                                                        <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>

                        <!-- Loans Tab -->
                        <div class="tab-pane" id="loans">
                            @if($loans->isEmpty())
                                <div class="alert alert-success">
                                    <p>No loans associated with this group.</p>
                                </div>
                            @else
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Member</th>
                                            <th>Amount</th>
                                            <th>Balance</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($loans as $loan)
                                            <tr>
                                                <td>{{ $loan->id }}</td>
                                                <td>{{ optional($loan->user)->name ?? 'N/A' }}</td>
                                                <td>{{ number_format($loan->amount) }}</td>
                                                <td>{{ number_format($loan->balance) }}</td>
                                                <td>{{ $loan->is_fully_paid }}</td>
                                                <td>
                                                    <a href="{{ admin_url('loans/'.$loan->id) }}" class="btn btn-xs btn-primary">View</a>
                                                    <a href="{{ admin_url('loans/'.$loan->id.'/edit') }}" class="btn btn-xs btn-warning">Edit</a>
                                                    <form action="{{ admin_url('loans/'.$loan->id) }}" method="post" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this loan?');">
                                                        {{ csrf_field() }}
                                                        {{ method_field('DELETE') }}
                                                        <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>

                        <!-- Positions Tab -->
                        <div class="tab-pane" id="positions">
                            @if($positions->isEmpty())
                                <div class="alert alert-success">
                                    <p>No positions associated with this group.</p>
                                </div>
                            @else
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($positions as $position)
                                            <tr>
                                                <td>{{ $position->id }}</td>
                                                <td>{{ $position->name }}</td>
                                                <td>
                                                    <a href="{{ admin_url('positions/'.$position->id) }}" class="btn btn-xs btn-primary">View</a>
                                                    <form action="{{ admin_url('positions/'.$position->id) }}" method="post" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this position?');">
                                                        {{ csrf_field() }}
                                                        {{ method_field('DELETE') }}
                                                        <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>

                        <!-- Loan Schemes Tab -->
                        <div class="tab-pane" id="loanSchemes">
                            @if($loanSchemes->isEmpty())
                                <div class="alert alert-success">
                                    <p>No loan schemes associated with this group.</p>
                                </div>
                            @else
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Interest Type</th>
                                            <th>Min Amount</th>
                                            <th>Max Amount</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($loanSchemes as $scheme)
                                            <tr>
                                                <td>{{ $scheme->id }}</td>
                                                <td>{{ $scheme->name }}</td>
                                                <td>{{ $scheme->initial_interest_type }}</td>
                                                <td>{{ number_format($scheme->min_amount) }}</td>
                                                <td>{{ number_format($scheme->max_amount) }}</td>
                                                <td>
                                                    <a href="{{ admin_url('loan-schemes/'.$scheme->id) }}" class="btn btn-xs btn-primary">View</a>
                                                    <a href="{{ admin_url('loan-schemes/'.$scheme->id.'/edit') }}" class="btn btn-xs btn-warning">Edit</a>
                                                    <form action="{{ admin_url('loan-schemes/'.$scheme->id) }}" method="post" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this loan scheme?');">
                                                        {{ csrf_field() }}
                                                        {{ method_field('DELETE') }}
                                                        <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>

                        <!-- Social Funds Tab -->
                        <div class="tab-pane" id="socialFunds">
                            @if($socialFunds->isEmpty())
                                <div class="alert alert-success">
                                    <p>No social funds associated with this group.</p>
                                </div>
                            @else
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>User</th>
                                            <th>Amount Paid</th>
                                            <th>Remaining Balance</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($socialFunds as $fund)
                                            <tr>
                                                <td>{{ $fund->id }}</td>
                                                <td>{{ optional($fund->user)->name ?? 'N/A' }}</td>
                                                <td>{{ number_format($fund->amount_paid) }}</td>
                                                <td>{{ number_format($fund->remaining_balance) }}</td>
                                                <td>{{ $fund->created_at }}</td>
                                                <td>
                                                    <a href="{{ admin_url('social-funds/'.$fund->id) }}" class="btn btn-xs btn-primary">View</a>
                                                    <form action="{{ admin_url('social-funds/'.$fund->id) }}" method="post" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this social fund?');">
                                                        {{ csrf_field() }}
                                                        {{ method_field('DELETE') }}
                                                        <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>

                        <!-- Organization Associations Tab -->
                        <div class="tab-pane" id="orgAssociations">
                            @if($orgAssociations->isEmpty())
                                <div class="alert alert-success">
                                    <p>No organization associations with this group.</p>
                                </div>
                            @else
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Organization</th>
                                            <th>Date Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($orgAssociations as $assoc)
                                            <tr>
                                                <td>{{ $assoc->id }}</td>
                                                <td>{{ optional($assoc->vslaOrganisation)->name ?? 'N/A' }}</td>
                                                <td>{{ $assoc->created_at }}</td>
                                                <td>
                                                    <form action="{{ admin_url('vsla-organisation-saccos/'.$assoc->id) }}" method="post" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this organization association?');">
                                                        {{ csrf_field() }}
                                                        {{ method_field('DELETE') }}
                                                        <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="box-footer">
        <div class="row">
            <div class="col-md-6">
                <a href="{{ admin_url('saccos') }}" class="btn btn-default">
                    <i class="fa fa-arrow-left"></i> Back to Group List
                </a>
            </div>
            <div class="col-md-6 text-right">
                <form action="{{ admin_url('saccos/'.$sacco->id) }}" method="post" onsubmit="return confirm('Are you sure you want to delete this group? This action cannot be undone.');">
                    {{ csrf_field() }}
                    {{ method_field('DELETE') }}
                    <button type="submit" class="btn btn-danger" {{ $users->isNotEmpty() || $cycles->isNotEmpty() || $meetings->isNotEmpty() || $transactions->isNotEmpty() || $loans->isNotEmpty() || $positions->isNotEmpty() || $loanSchemes->isNotEmpty() || $socialFunds->isNotEmpty() || $orgAssociations->isNotEmpty() ? 'disabled' : '' }}>
                        <i class="fa fa-trash"></i> Delete Group
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(function() {
        // Add active class to first tab by default
        $('.nav-tabs a:first').tab('show');
    });
</script>
@endsection
