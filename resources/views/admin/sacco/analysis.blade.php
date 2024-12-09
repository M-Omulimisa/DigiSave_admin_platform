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

        .modal-content {
            border-radius: 12px;
        }

        .modal-header {
            background: var(--primary);
            color: white;
            border-radius: 12px 12px 0 0;
        }

        .detail-card {
            height: 100%;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .detail-card .card-header {
            background: var(--primary);
            color: white;
            font-weight: 500;
        }

        .search-container {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .search-input {
            border-radius: 20px;
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            border: 1px solid #ddd;
            width: 100%;
            max-width: 300px;
        }

        /* .button-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-top: 1rem;
        } */

        .action-btn {
            border: none;
            border-radius: 6px;
            padding: 0.5rem;
            color: white;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .action-btn:hover {
            transform: translateY(-2px);
        }

        .view-btn {
            background: var(--accent);
        }

        .button-container {
    display: flex;
    justify-content: center; /* Centers the button horizontally */
    margin-top: 1rem;
}

.export-btn {
    background: var(--success);
    display: flex;
    align-items: center;
    gap: 0.5rem; /* Adds space between icon and text */
    padding: 0.5rem 1rem;
}


        .metric-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .metric-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }

        .metric-item:last-child {
            border-bottom: none;
        }

        .metric-label {
            color: #666;
        }

        .metric-value {
            font-weight: 500;
            color: var(--primary);
        }

        /* Modal Tab Styles */
        .tab-controls {
            display: flex;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 1rem;
        }

        .tab-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            background: none;
            color: #666;
            font-weight: 500;
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }

        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .demographics {
                flex-direction: column;
            }

            .demographic-item {
                margin-bottom: 1rem;
            }

            .button-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col">
                    <h1 class="mb-0">SACCO Groups</h1>
                    <p class="mb-0">Credit Score Analysis Dashboard</p>
                </div>
                <div class="col text-end">
                    <div class="search-container">
                        <input type="text" class="search-input" placeholder="Search groups..." id="searchInput">
                        <i class="fa fa-search position-absolute ms-3"
                            style="top: 50%; transform: translateY(-50%); left: 1rem; color: #aaa;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row" id="saccoContainer">
            <!-- SACCO Cards will be injected here -->
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="saccoDetails" tabindex="-1" aria-labelledby="saccoDetailsLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">SACCO Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="tab-controls">
                        <button class="tab-btn active" data-tab="overview">Overview</button>
                        <button class="tab-btn" data-tab="loans">Loans</button>
                        <button class="tab-btn" data-tab="savings">Savings</button>
                        <button class="tab-btn" data-tab="members">Members</button>
                    </div>

                    <div class="tab-content active" id="overview">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="detail-card">
                                    <div class="card-header">Credit Score Analysis</div>
                                    <div class="card-body" id="creditScoreDetails"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-card">
                                    <div class="card-header">Key Metrics</div>
                                    <div class="card-body" id="keyMetrics"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-content" id="loans">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="detail-card">
                                    <div class="card-header">Loan Distribution</div>
                                    <div class="card-body" id="loanMetrics"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-content" id="savings">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="detail-card">
                                    <div class="card-header">Savings Overview</div>
                                    <div class="card-body" id="savingsMetrics"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-content" id="members">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="detail-card">
                                    <div class="card-header">Member Demographics</div>
                                    <div class="card-body" id="memberMetrics"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary mr-3" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" id="exportButton">
                        <i class="fa fa-download mr-2"></i> Export Data
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Your Custom Script -->
    <script>
        // Sample SACCO Data
        const saccos = [{
                id: 1,
                name: "Green Valley SACCO",
                creditScore: {
                    score: 85,
                    description: "Excellent credit standing. The group demonstrates strong savings culture and reliable loan repayment history."
                },
                totalMembers: 150,
                savingsStats: {
                    totalBalance: 5000000
                },
                loanStats: {
                    total: 120
                },
                averageAttendance: 75,
                maleMembers: 90,
                femaleMembers: 60,
                youthMembers: 30
            },
            {
                id: 2,
                name: "Blue Horizon SACCO",
                creditScore: {
                    score: 65,
                    description: "Good credit standing. The group shows consistent savings and satisfactory loan management."
                },
                totalMembers: 80,
                savingsStats: {
                    totalBalance: 2000000
                },
                loanStats: {
                    total: 50
                },
                averageAttendance: 60,
                maleMembers: 50,
                femaleMembers: 30,
                youthMembers: 15
            },
            {
                id: 3,
                name: "Sunrise SACCO",
                creditScore: {
                    score: 45,
                    description: "Needs improvement. The group should focus on increasing savings and improving loan repayment rates."
                },
                totalMembers: 60,
                savingsStats: {
                    totalBalance: 1000000
                },
                loanStats: {
                    total: 30
                },
                averageAttendance: 50,
                maleMembers: 35,
                femaleMembers: 20,
                youthMembers: 10
            }
            // Add more SACCO objects as needed
        ];

        // Function to initialize SACCO cards
        function initializeSaccoCards() {
            const saccoContainer = document.getElementById('saccoContainer');
            saccoContainer.innerHTML = ''; // Clear existing content

            saccos.forEach(sacco => {
                const saccoCol = document.createElement('div');
                saccoCol.className = 'col-md-6 col-lg-4 sacco-item';
                saccoCol.innerHTML = `
                    <div class="sacco-card" data-sacco-id="${sacco.id}">
                        <div class="sacco-header">
                            <h3>${sacco.name}</h3>
                            <span class="credit-badge ${sacco.creditScore.score >= 70 ? 'credit-high' : (sacco.creditScore.score >= 50 ? 'credit-medium' : 'credit-low')}">
                                Score: ${sacco.creditScore.score}
                            </span>
                        </div>
                        <div class="sacco-content">
                            <div class="stats-grid">
                                <div class="stat-box">
                                    <h4>${numberWithCommas(sacco.totalMembers)}</h4>
                                    <p>Total Members</p>
                                </div>
                                <div class="stat-box">
                                    <h4>UGX ${numberWithCommas(sacco.savingsStats.totalBalance)}</h4>
                                    <p>Total Savings</p>
                                </div>
                                <div class="stat-box">
                                    <h4>${numberWithCommas(sacco.loanStats.total)}</h4>
                                    <p>Active Loans</p>
                                </div>
                                <div class="stat-box">
                                    <h4>${sacco.averageAttendance}%</h4>
                                    <p>Attendance Rate</p>
                                </div>
                            </div>

                            <div class="demographics">
                                <div class="demographic-item">
                                    <h5>${numberWithCommas(sacco.maleMembers)}</h5>
                                    <small>Male</small>
                                </div>
                                <div class="demographic-item">
                                    <h5>${numberWithCommas(sacco.femaleMembers)}</h5>
                                    <small>Female</small>
                                </div>
                                <div class="demographic-item">
                                    <h5>${numberWithCommas(sacco.youthMembers)}</h5>
                                    <small>Youth</small>
                                </div>
                            </div>

                            <div class="button-container d-flex justify-content-center">
    <button class="action-btn export-btn" onclick="exportData(${sacco.id})">
        <i class="fa fa-download me-2"></i> Export Data
    </button>
</div>
                        </div>
                    </div>
                `;
                saccoContainer.appendChild(saccoCol);
            });
        }

        // Utility function to format numbers with commas
        function numberWithCommas(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('.sacco-item').forEach(item => {
                const name = item.querySelector('.sacco-header h3').textContent.toLowerCase();
                item.style.display = name.includes(searchTerm) ? 'block' : 'none';
            });
        });

        // Tab switching within modal
        document.querySelectorAll('.tab-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons and contents
                document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove(
                    'active'));

                // Add active class to clicked button and corresponding content
                this.classList.add('active');
                document.getElementById(this.dataset.tab).classList.add('active');
            });
        });

        // Show SACCO details in the existing modal
        function showDetails(saccoId) {
            const sacco = saccos.find(s => s.id === saccoId);
            if (!sacco) return;

            // Set modal title
            document.getElementById('modalTitle').textContent = sacco.name;

            // Populate Credit Score Details
            document.getElementById('creditScoreDetails').innerHTML = `
                <h2>${sacco.creditScore.score}</h2>
                <p>${sacco.creditScore.description}</p>
            `;

            // Populate Key Metrics
            document.getElementById('keyMetrics').innerHTML = generateMetricsList([{
                    label: 'Total Savings',
                    value: 'UGX ' + numberWithCommas(sacco.savingsStats.totalBalance)
                },
                {
                    label: 'Active Loans',
                    value: numberWithCommas(sacco.loanStats.total)
                },
                {
                    label: 'Average Attendance',
                    value: sacco.averageAttendance + '%'
                }
            ]);

            // Populate Loan Metrics
            document.getElementById('loanMetrics').innerHTML = generateMetricsList([{
                    label: 'Total Loans',
                    value: numberWithCommas(sacco.loanStats.total)
                },
                {
                    label: 'Total Principal',
                    value: 'UGX ' + numberWithCommas(sacco.loanStats.total * 10000)
                }, // Example calculation
                {
                    label: 'Male Borrowers',
                    value: Math.floor(sacco.loanStats.total * 0.6)
                }, // Example calculation
                {
                    label: 'Female Borrowers',
                    value: Math.floor(sacco.loanStats.total * 0.4)
                } // Example calculation
            ]);

            // Populate Savings Metrics
            document.getElementById('savingsMetrics').innerHTML = generateMetricsList([{
                    label: 'Total Savings',
                    value: 'UGX ' + numberWithCommas(sacco.savingsStats.totalBalance)
                },
                {
                    label: 'Average per Member',
                    value: 'UGX ' + numberWithCommas(Math.round(sacco.savingsStats.totalBalance / sacco
                        .totalMembers))
                },
                {
                    label: 'Male Savers',
                    value: sacco.maleMembers
                },
                {
                    label: 'Female Savers',
                    value: sacco.femaleMembers
                }
            ]);

            // Populate Member Metrics
            document.getElementById('memberMetrics').innerHTML = generateMetricsList([{
                    label: 'Total Members',
                    value: sacco.totalMembers
                },
                {
                    label: 'Male Members',
                    value: sacco.maleMembers
                },
                {
                    label: 'Female Members',
                    value: sacco.femaleMembers
                },
                {
                    label: 'Youth Members',
                    value: sacco.youthMembers
                },
                {
                    label: 'Average Attendance',
                    value: sacco.averageAttendance + '%'
                }
            ]);

            // Show the modal using Bootstrap
            const saccoModal = new bootstrap.Modal(document.getElementById('saccoDetails'));
            saccoModal.show();
        }

        // Generate metrics list HTML
        function generateMetricsList(metrics) {
            return `
                <ul class="metric-list">
                    ${metrics.map(metric => `
                            <li class="metric-item">
                                <span class="metric-label">${metric.label}</span>
                                <span class="metric-value">${metric.value}</span>
                            </li>
                        `).join('')}
                </ul>
            `;
        }

        // Export data function
        function exportData(saccoId) {
            const sacco = saccos.find(s => s.id === saccoId);
            if (!sacco) return;

            const csvContent = [
                ["SACCO Credit Score Report"],
                ["Generated on: " + new Date().toLocaleString()],
                [],
                ["Basic Information"],
                ["Group Name", sacco.name],
                ["Credit Score", sacco.creditScore.score],
                ["Credit Standing", sacco.creditScore.description],
                [],
                ["Membership Statistics"],
                ["Total Members", sacco.totalMembers],
                ["Male Members", sacco.maleMembers],
                ["Female Members", sacco.femaleMembers],
                ["Youth Members", sacco.youthMembers],
                [],
                ["Financial Statistics"],
                ["Total Savings Balance", sacco.savingsStats.totalBalance],
                ["Active Loans", sacco.loanStats.total],
                ["Average Attendance Rate", sacco.averageAttendance + "%"]
            ].map(row => row.join(",")).join("\n");

            const blob = new Blob([csvContent], {
                type: 'text/csv;charset=utf-8;'
            });
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", `${sacco.name}_credit_report.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Initialize the dashboard on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeSaccoCards();
        });
    </script>
</body>

</html>
