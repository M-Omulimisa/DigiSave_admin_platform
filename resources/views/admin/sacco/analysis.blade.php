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
            margin: 0;
            padding: 0;
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1rem 0;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin: -0.5rem;
        }

        .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
            padding: 0.5rem;
        }

        .col-lg-4 {
            flex: 0 0 33.333333%;
            max-width: 33.333333%;
            padding: 0.5rem;
        }

        .search-container {
            background: white;
            padding: 0.5rem;
            border-radius: 8px;
            max-width: 250px;
            margin-left: auto;
        }

        .search-input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .sacco-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            margin-bottom: 1rem;
            transition: transform 0.2s;
            overflow: hidden;
        }

        .sacco-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .sacco-header {
            background: var(--primary);
            color: white;
            padding: 1rem;
            position: relative;
        }

        .sacco-header h3 {
            margin: 0;
            font-size: 1.1rem;
        }

        .credit-badge {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            padding: 0.3rem 0.6rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: bold;
            color: white;
        }

        .credit-high { background: var(--success); }
        .credit-medium { background: var(--warning); }
        .credit-low { background: var(--danger); }

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
        }

        .demographic-item h5 {
            margin: 0;
            font-size: 0.9rem;
            color: var(--primary);
        }

        .demographic-item small {
            color: #666;
            font-size: 0.8rem;
        }

        .button-container {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            font-size: 0.9rem;
            color: white;
            transition: all 0.2s;
        }

        .btn-primary { background: var(--accent); }
        .btn-success { background: var(--success); }
        .btn-secondary { background: var(--secondary); }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* Custom Modal Styles */
        .custom-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            overflow-y: auto;
            padding: 20px;
        }

        .modal-content {
            background: white;
            max-width: 900px;
            margin: 20px auto;
            border-radius: 8px;
            position: relative;
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 1rem;
            background: var(--primary);
            color: white;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s;
        }

        .modal-close:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            padding: 1rem;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .metric-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            height: 100%;
        }

        .metric-title {
            color: var(--primary);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #dee2e6;
            font-size: 1rem;
            font-weight: 600;
        }

        .metric-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }

        .metric-item:last-child {
            border-bottom: none;
        }

        .metric-value {
            font-weight: 600;
            color: var(--primary);
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .col-md-6, .col-lg-4 {
                flex: 0 0 100%;
                max-width: 100%;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .demographics {
                flex-direction: column;
                gap: 0.5rem;
            }

            .modal-content {
                margin: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1>SACCO Groups</h1>
                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Search groups..." id="searchInput">
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            @foreach($saccos as $sacco)
            <div class="col-md-6 col-lg-4 sacco-item">
                <div class="sacco-card">
                    <div class="sacco-header">
                        <h3>{{ $sacco['name'] }}</h3>
                        <span class="credit-badge {{ $sacco['creditScore']['score'] >= 80 ? 'credit-high' : ($sacco['creditScore']['score'] >= 60 ? 'credit-medium' : 'credit-low') }}">
                            Score: {{ $sacco['creditScore']['score'] ?? 'N/A' }}
                        </span>
                    </div>
                    <div class="sacco-content">
                        <div class="stats-grid">
                            <div class="stat-box">
                                <h4>{{ number_format($sacco['totalMembers']) }}</h4>
                                <p>Total Members</p>
                            </div>
                            <div class="stat-box">
                                <h4>UGX {{ number_format($sacco['savingsStats']['totalBalance']) }}</h4>
                                <p>Total Savings</p>
                            </div>
                            <div class="stat-box">
                                <h4>{{ number_format($sacco['loanStats']['total']) }}</h4>
                                <p>Active Loans</p>
                            </div>
                            <div class="stat-box">
                                <h4>{{ $sacco['averageAttendance'] }}%</h4>
                                <p>Attendance Rate</p>
                            </div>
                        </div>

                        <div class="demographics">
                            <div class="demographic-item">
                                <h5>{{ number_format($sacco['maleMembers']) }}</h5>
                                <small>Male</small>
                            </div>
                            <div class="demographic-item">
                                <h5>{{ number_format($sacco['femaleMembers']) }}</h5>
                                <small>Female</small>
                            </div>
                            <div class="demographic-item">
                                <h5>{{ number_format($sacco['youthMembers']) }}</h5>
                                <small>Youth</small>
                            </div>
                        </div>

                        <div class="button-container">
                            <button class="btn btn-primary" onclick="viewDetails({{ json_encode($sacco) }})">
                                <i class="fa fa-eye"></i> View Details
                            </button>
                            <button class="btn btn-success" onclick="exportData({{ json_encode($sacco) }})">
                                <i class="fa fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Custom Modal -->
    <div id="saccoDetails" class="custom-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"></h5>
                <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="metric-card">
                            <h6 class="metric-title">Credit Score Analysis</h6>
                            <div class="text-center">
                                <h2 id="creditScore"></h2>
                                <p id="creditDescription"></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="metric-card">
                            <h6 class="metric-title">Membership Overview</h6>
                            <div id="membershipMetrics"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="metric-card">
                            <h6 class="metric-title">Loan Statistics</h6>
                            <div id="loanMetrics"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="metric-card">
                            <h6 class="metric-title">Savings Statistics</h6>
                            <div id="savingsMetrics"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
                <button type="button" class="btn btn-success" onclick="exportCurrentSaccoData()">
                    <i class="fa fa-download"></i> Export Data
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentSaccoData = null;

        function viewDetails(sacco) {
            currentSaccoData = sacco;
            const modal = document.getElementById('saccoDetails');

            document.getElementById('modalTitle').textContent = sacco.name;
            document.getElementById('creditScore').textContent = sacco.creditScore.score;
            document.getElementById('creditDescription').textContent = sacco.creditScore.description;

            document.getElementById('membershipMetrics').innerHTML = generateMetricsList([
                { label: 'Total Members', value: sacco.totalMembers },
                { label: 'Male Members', value: sacco.maleMembers },
                { label: 'Female Members', value: sacco.femaleMembers },
                { label: 'Youth Members', value: sacco.youthMembers }
            ]);

            document.getElementById('loanMetrics').innerHTML = generateMetricsList([
                { label: 'Active Loans', value: sacco.loanStats.total },
                { label: 'Total Principal', value: 'UGX ' + formatNumber(sacco.loanStats.principal) },
                { label: 'Total Interest', value: 'UGX ' + formatNumber(sacco.loanStats.interest) },
                { label: 'Repayments', value: 'UGX ' + formatNumber(sacco.loanStats.repayments) }
            ]);

            document.getElementById('savingsMetrics').innerHTML = generateMetricsList([
                { label: 'Total Savings', value: 'UGX ' + formatNumber(sacco.savingsStats.totalBalance) },
                { label: 'Total Accounts', value: sacco.savingsStats.totalAccounts },
                { label: 'Average per Member', value: 'UGX ' + formatNumber(sacco.savingsStats.totalBalance / sacco.totalMembers) }
            ]);

            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function generateMetricsList(metrics) {
            return metrics.map(metric => `
                <div class="metric-item">
                    <span>${metric.label}</span>
                    <span class="metric-value">${metric.value}</span>
                </div>
            `).join('');
        }

        function closeModal() {
            const modal = document.getElementById('saccoDetails');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        function exportData(sacco) {
            const csvContent = [
                ['SACCO Credit Score Report'],
                ['Generated on:', new Date().toLocaleString()],
                [''],
                ['Basic Information'],
                ['Group Name:', sacco.name],
                ['Credit Score:', sacco.creditScore.score],
                ['Credit Standing:', sacco.creditScore.description],
                [''],
                ['Membership Statistics'],
                ['Total Members:', sacco.totalMembers],
                ['Male Members:', sacco.maleMembers],
                ['Female Members:', sacco.femaleMembers],
                ['Youth Members:', sacco.youthMembers],
                [''],
                ['Financial Statistics'],
                ['Total Savings Balance:', `UGX ${formatNumber(sacco.savingsStats.totalBalance)}`],
                ['Active Loans:', sacco.loanStats.total],
                ['Total Principal:', `UGX ${formatNumber(sacco.loanStats.principal)}`],
                ['Total Interest:', `UGX ${formatNumber(sacco.loanStats.interest)}`],
                ['Total Repayments:', `UGX ${formatNumber(sacco.loanStats.repayments)}`],
                [''],
                ['Performance Metrics'],
                ['Average Attendance:', `${sacco.averageAttendance}%`],
                ['Savings per Member:', `UGX ${formatNumber(sacco.savingsStats.totalBalance / sacco.totalMembers)}`],
                ['Loan to Savings Ratio:', `${((sacco.loanStats.principal / sacco.savingsStats.totalBalance) * 100).toFixed(2)}%`]
            ].map(row => row.join(',')).join('\n');

            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `${sacco.name}_credit_report.csv`;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function exportCurrentSaccoData() {
            if (currentSaccoData) {
                exportData(currentSaccoData);
            }
        }

        function formatNumber(number) {
            return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('.sacco-item').forEach(item => {
                const name = item.querySelector('.sacco-header h3').textContent.toLowerCase();
                item.style.display = name.includes(searchTerm) ? 'block' : 'none';
            });
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('saccoDetails');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Close modal on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>
