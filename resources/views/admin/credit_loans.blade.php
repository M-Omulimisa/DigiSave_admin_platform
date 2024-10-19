<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Credit Loans</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
        }
        .table thead th {
            background-color: #343a40;
            color: #fff;
            text-align: center;
        }
        .table td, .table th {
            vertical-align: middle;
        }
        .status-pending {
            background-color: #ffc107;
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
        }
        .status-approved {
            background-color: #28a745;
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
        }
        .status-rejected {
            background-color: #dc3545;
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
        }
        .action-btn {
            font-size: 0.9em;
            padding: 5px 10px;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <h2 class="text-center mb-4">Credit Loans</h2>

    <!-- Status Filters -->
    <div class="mb-4 text-center">
        <a href="{{ route('credit-loans.index', ['status' => 'pending']) }}" class="btn btn-warning mx-2">Pending</a>
        <a href="{{ route('credit-loans.index', ['status' => 'approved']) }}" class="btn btn-success mx-2">Approved</a>
        <a href="{{ route('credit-loans.index', ['status' => 'rejected']) }}" class="btn btn-danger mx-2">Rejected</a>
        <a href="{{ route('credit-loans.index') }}" class="btn btn-secondary mx-2">All Loans</a>
    </div>

    <!-- Loans Table -->
    <div class="table-responsive">
        <table class="table table-hover table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Sacco</th>
                    <th>Loan Amount</th>
                    <th>Term (Months)</th>
                    <th>Total Interest</th>
                    <th>Monthly Payment</th>
                    <th>Purpose</th>
                    <th>Billing Address</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($creditLoans as $loan)
                <tr>
                    <td>{{ $loan->id }}</td>
                    <td>{{ $loan->sacco->name }}</td>
                    <td>UGX {{ number_format($loan->loan_amount, 2) }}</td>
                    <td>{{ $loan->loan_term }}</td>
                    <td>UGX {{ number_format($loan->total_interest, 2) }}</td>
                    <td>UGX {{ number_format($loan->monthly_payment, 2) }}</td>
                    <td>{{ $loan->loan_purpose }}</td>
                    <td>{{ $loan->billing_address }}</td>
                    <td>{{ ucfirst($loan->selected_method) }}</td>
                    <td>
                        @if ($loan->status == 'pending')
                        <span class="status-pending">Pending</span>
                        @elseif ($loan->status == 'approved')
                        <span class="status-approved">Approved</span>
                        @elseif ($loan->status == 'rejected')
                        <span class="status-rejected">Rejected</span>
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('credit-loans.update', $loan->id) }}" method="POST" class="d-inline-block">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="approved">
                            <button type="submit" class="btn btn-success action-btn">Approve</button>
                        </form>
                        <form action="{{ route('credit-loans.update', $loan->id) }}" method="POST" class="d-inline-block">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="rejected">
                            <button type="submit" class="btn btn-danger action-btn">Reject</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center">
        {{ $creditLoans->links() }}
    </div>
</div>

<!-- Bootstrap JS and Dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
