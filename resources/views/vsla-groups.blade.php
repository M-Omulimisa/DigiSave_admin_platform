<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VSLA Groups Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <style>
        /* Custom styles */
        :root {
            --primary-color: #4f46e5;
            --success-color: #10b981;
            --accent-color: #3b82f6;
        }

        body {
            background-color: #f8fafc;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .dashboard-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            margin-bottom: 1.5rem;
        }

        .dashboard-header {
            padding: 2rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .search-input {
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            width: 100%;
            max-width: 400px;
            transition: border-color 0.15s ease-in-out;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.15s ease-in-out;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-filter {
            background-color: #f3f4f6;
            color: #374151;
        }

        .btn-filter:hover {
            background-color: #e5e7eb;
        }

        .btn-new {
            background-color: var(--success-color);
            color: white;
        }

        .btn-new:hover {
            background-color: #059669;
        }

        .btn-export {
            background-color: var(--accent-color);
            color: white;
        }

        .btn-export:hover {
            background-color: #2563eb;
        }

        .table-container {
            overflow-x: auto;
            background-color: white;
            border-radius: 0 0 1rem 1rem;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        th {
            background-color: #f8fafc;
            padding: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            color: #475569;
            border-bottom: 1px solid #e5e7eb;
        }

        td {
            padding: 1rem;
            font-size: 0.875rem;
            color: #475569;
            border-bottom: 1px solid #e5e7eb;
        }

        tr:hover {
            background-color: #f8fafc;
        }

        .credit-score {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            background-color: #fef3c7;
            border-radius: 0.375rem;
            color: #92400e;
        }

        .action-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.15s ease-in-out;
        }

        .action-link:hover {
            color: #4338ca;
        }

        @media (max-width: 768px) {
            .controls-container {
                flex-direction: column;
                gap: 1rem;
            }

            .search-input {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="dashboard-card">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">VSLA Groups Dashboard</h1>
                <p class="text-gray-600">Overview of all VSLA groups and their activities</p>
            </div>

            <div class="flex flex-wrap gap-4 items-center justify-between controls-container">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" placeholder="Search groups..." class="search-input">
                </div>
                <div class="flex flex-wrap gap-3">
                    <button class="btn btn-filter">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        Usage
                    </button>
                    <button class="btn btn-filter">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Date
                    </button>
                    <button class="btn btn-new">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        New Group
                    </button>
                    <button class="btn btn-export">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Leader</th>
                        <th>Contact</th>
                        <th>District</th>
                        <th>Cash/Shares</th>
                        <th>Min. Savings (UGX)</th>
                        <th>Share Price (UGX)</th>
                        <th>Credit Score</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($saccos as $sacco)
                    <tr>
                        <td class="font-medium text-gray-900">{{ $sacco->name }}</td>
                        <td>{{ $sacco->leader ?? 'No Leader' }}</td>
                        <td>{{ $sacco->phone_number }}</td>
                        <td>{{ $sacco->district ?? 'No District' }}</td>
                        <td>
                            {{ $sacco->uses_cash ? 'Cash' : '' }}{{ $sacco->uses_cash && $sacco->uses_shares ? ' & ' : '' }}{{ $sacco->uses_shares ? 'Shares' : '' }}
                        </td>
                        <td>{{ number_format($sacco->min_cash_savings) }}</td>
                        <td>{{ number_format($sacco->share_price) }}</td>
                        <td>
                            <div class="credit-score">
                                <svg class="w-4 h-4 text-yellow-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                </svg>
                                <a href="{{ url('/credit?sacco_id=' . $sacco->id) }}" class="action-link">
                                    View Score
                                </a>
                            </div>
                        </td>
                        <td>{{ $sacco->created_at->format('d M Y') }}</td>
                        <td>
                            <a href="{{ url('/transactions?sacco_id=' . $sacco->id) }}" class="action-link">
                                View Transactions
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
