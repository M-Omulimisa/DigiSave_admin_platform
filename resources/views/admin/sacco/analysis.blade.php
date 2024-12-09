<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SACCO Groups Dashboard</title>
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #34495e;
            --accent: #3498db;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
        }

        body {
            background-color: #f5f6fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .sacco-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            transition: transform 0.2s;
            overflow: hidden;
        }

        .sacco-card:hover {
            transform: translateY(-5px);
        }

        .sacco-header {
            background: var(--primary);
            color: white;
            padding: 1.5rem;
            position: relative;
        }

        .credit-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
        }

        .credit-high {
            background: var(--success);
            color: white;
        }

        .credit-medium {
            background: var(--warning);
            color: white;
        }

        .credit-low {
            background: var(--danger);
            color: white;
        }

        .sacco-content {
            padding: 1.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .stat-box {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }

        .stat-box h4 {
            font-size: 1.5rem;
            margin: 0;
            color: var(--primary);
        }

        .stat-box p {
            margin: 0.5rem 0 0 0;
            color: #666;
            font-size: 0.9rem;
        }

        .demographics {
            display: flex;
            justify-content: space-between;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .demographic-item {
            text-align: center;
            flex: 1;
        }

        .button-container {
            display: flex;
            justify-content: center;
            margin-top: 1rem;
        }

        .export-btn {
            background: var(--success);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .export-btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <div class="dashboard-header">
        <div class="container">
            <h1 class="mb-0">SACCO Groups</h1>
            <p class="mb-0">Credit Score Analysis Dashboard</p>
        </div>
    </div>

    <div class="container">
        <div class="row">
            @foreach ($saccos as $sacco)
                <div class="col-md-6 col-lg-4">
                    <div class="sacco-card">
                        <div class="sacco-header">
                            <h3>{{ $sacco['name'] }}</h3>
                            <span class="credit-badge
                                {{ $sacco['creditScore']['score'] >= 80 ? 'credit-high' : ($sacco['creditScore']['score'] >= 60 ? 'credit-medium' : 'credit-low') }}">
                                Score: {{ $sacco['creditScore']['score'] ?? 'N/A' }}
                            </span>
                        </div>
                        <div class="sacco-content">
                            <div class="stats-grid">
                                <div class="stat-box">
                                    <h4>{{ $sacco['totalMembers'] }}</h4>
                                    <p>Total Members</p>
                                </div>
                                <div class="stat-box">
                                    <h4>UGX {{ number_format($sacco['savingsStats']['totalBalance']) }}</h4>
                                    <p>Total Savings</p>
                                </div>
                                <div class="stat-box">
                                    <h4>{{ $sacco['loanStats']['total'] }}</h4>
                                    <p>Active Loans</p>
                                </div>
                                <div class="stat-box">
                                    <h4>{{ $sacco['averageAttendance'] }}%</h4>
                                    <p>Attendance Rate</p>
                                </div>
                            </div>

                            <div class="demographics">
                                <div class="demographic-item">
                                    <h5>{{ $sacco['maleMembers'] }}</h5>
                                    <small>Male</small>
                                </div>
                                <div class="demographic-item">
                                    <h5>{{ $sacco['femaleMembers'] }}</h5>
                                    <small>Female</small>
                                </div>
                                <div class="demographic-item">
                                    <h5>{{ $sacco['youthMembers'] }}</h5>
                                    <small>Youth</small>
                                </div>
                            </div>

                            <div class="button-container">
                                <button class="export-btn" onclick="exportData({{ $sacco['id'] }})">
                                    <i class="fa fa-download"></i> Export Data
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <script>
        function exportData(saccoId) {
            alert(`Exporting data for SACCO ID: ${saccoId}`);
            // Real export functionality can be implemented here
        }
    </script>
</body>

</html>
