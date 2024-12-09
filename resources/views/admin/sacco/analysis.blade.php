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
            font-size: 14px;
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1rem 0;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .search-container {
            background: white;
            padding: 0.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .search-input {
            border-radius: 20px;
            padding: 0.3rem 0.8rem;
            color: black;
            border: 1px solid #141313;
            width: 100%;
            max-width: 250px;
        }

        .sacco-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 1rem;
            transition: transform 0.2s;
            overflow: hidden;
            font-size: 12px;
        }

        .sacco-card:hover {
            transform: translateY(-3px);
        }

        .sacco-header {
            background: var(--primary);
            color: white;
            padding: 1rem;
            position: relative;
        }

        .credit-badge {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            padding: 0.3rem 0.6rem;
            border-radius: 15px;
            font-size: 10px;
            font-weight: bold;
        }

        .credit-high {
            background: var(--success);
        }

        .credit-medium {
            background: var(--warning);
        }

        .credit-low {
            background: var(--danger);
        }

        .sacco-content {
            padding: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .stat-box {
            background: #f8f9fa;
            padding: 0.8rem;
            border-radius: 6px;
            text-align: center;
        }

        .stat-box h4 {
            font-size: 1rem;
            margin: 0;
            color: var(--primary);
        }

        .stat-box p {
            margin: 0.3rem 0 0 0;
            color: #666;
            font-size: 0.8rem;
        }

        .demographics {
            display: flex;
            justify-content: space-between;
            padding-top: 0.5rem;
            border-top: 1px solid #eee;
        }

        .demographic-item {
            text-align: center;
            flex: 1;
            font-size: 0.9rem;
        }

        .button-container {
            display: flex;
            justify-content: center;
            margin-top: 0.5rem;
        }

        .export-btn {
            background: var(--success);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 6px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .export-btn:hover {
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .demographics {
                flex-direction: column;
            }

            .demographic-item {
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-header">
        <div class="container d-flex justify-content-between align-items-center">
            <h1 class="mb-0">SACCO Groups</h1>
            <div class="search-container">
                <input type="text" class="search-input" placeholder="Search groups..." id="searchInput">
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row" id="saccoContainer">
            @foreach ($saccos as $sacco)
                <div class="col-md-6 col-lg-4 sacco-item">
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
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('.sacco-item').forEach(item => {
                const name = item.querySelector('.sacco-header h3').textContent.toLowerCase();
                item.style.display = name.includes(searchTerm) ? 'block' : 'none';
            });
        });

        // Export data function (placeholder)
        function exportData(saccoId) {
            alert(`Exporting data for SACCO ID: ${saccoId}`);
        }
    </script>
</body>

</html>
