<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>VSLA Credit Score Dashboard</title>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    >
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
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .search-container {
            background: white;
            padding: 0.5rem;
            border-radius: 8px;
            max-width: 250px;
        }
        .search-input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .filters-bar {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .filters-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-item {
            flex: 1;
            min-width: 200px;
        }
        .filter-label {
            display: block;
            margin-bottom: 0.3rem;
            color: var(--secondary);
            font-size: 0.9rem;
        }
        .filter-select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
            background-color: white;
        }
        .actions-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
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
        .sacco-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            margin-bottom: 1rem;
            transition: transform 0.2s;
            overflow: hidden;
            position: relative;
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
        /* Add the rest of your CSS styles here */
    </style>
</head>
<body>
<div class="dashboard-header">
    <div class="container">
        <div class="header-content">
            <h1>VSLA Credit Score</h1>
            <div class="search-container">
                <input
                  type="text"
                  class="search-input"
                  placeholder="Search groups..."
                  id="searchInput"
                >
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="filters-bar">
        <div class="filters-row">
            <div class="filter-item">
                <label class="filter-label">Meeting Attendance</label>
                <select class="filter-select" id="attendanceFilter">
                    <option value="">All</option>
                    <option value="80">Above 80%</option>
                    <option value="60">Above 60%</option>
                    <option value="40">Above 40%</option>
                </select>
            </div>
            <div class="filter-item">
                <label class="filter-label">Total Savings</label>
                <select class="filter-select" id="savingsFilter">
                    <option value="">All</option>
                    <option value="1000000">Above 1M</option>
                    <option value="500000">Above 500K</option>
                    <option value="100000">Above 100K</option>
                </select>
            </div>
            <div class="filter-item">
                <label class="filter-label">Active Loans</label>
                <select class="filter-select" id="loansFilter">
                    <option value="">All</option>
                    <option value="10">10+ Loans</option>
                    <option value="5">5+ Loans</option>
                    <option value="3">3+ Loans</option>
                </select>
            </div>
            <div class="filter-item">
                <label class="filter-label">Members</label>
                <select class="filter-select" id="membersFilter">
                    <option value="">All</option>
                    <option value="30">30+ Members</option>
                    <option value="20">20+ Members</option>
                    <option value="10">10+ Members</option>
                </select>
            </div>
        </div>
        <div class="actions-row">
            <button class="btn btn-secondary" onclick="resetFilters()">
                <i class="fas fa-refresh"></i> Reset Filters
            </button>
            <button class="btn btn-success" onclick="exportAllData()">
                <i class="fas fa-download"></i> Export All Data
            </button>
        </div>
    </div>

    <div class="row" id="saccoGrid">
        @foreach($saccos as $sacco)
            @php
                $qualified = (
                    floatval($sacco['totalMeetings'] ?? 0) >= 4 &&
                    floatval($sacco['totalMembers'] ?? 0) >= 10 &&
                    floatval($sacco['savingsStats']['totalBalance'] ?? 0) > 0
                );
                $saccoData = array_merge($sacco, [
                    'qualified' => $qualified
                ]);
            @endphp

            <div class="col-md-6 col-lg-4 sacco-item">
                <div class="sacco-card" data-sacco="{{ json_encode($saccoData) }}">
                    <div class="sacco-header">
                        <h3>{{ $sacco['name'] }}</h3>
                        @if($qualified)
                            <span class="credit-badge {{
                                (floatval($sacco['creditScore']['score'] ?? 0) >= 80) ? 'credit-high' :
                                ((floatval($sacco['creditScore']['score'] ?? 0) >= 60) ? 'credit-medium' : 'credit-low')
                            }}">
                                Score: {{ floatval($sacco['creditScore']['score'] ?? 0) }}
                            </span>
                        @endif
                    </div>

                    @if(!$qualified)
                        <div class="not-qualified-overlay">
                            Not qualify for crediting
                        </div>
                    @endif

                    <div class="sacco-content">
                        <div class="stats-grid">
                            <div class="stat-box">
                                <h4>{{ number_format(floatval($sacco['totalMembers'] ?? 0)) }}</h4>
                                <p>Total Members</p>
                            </div>
                            <div class="stat-box">
                                <h4>UGX {{ number_format(floatval($sacco['savingsStats']['totalBalance'] ?? 0)) }}</h4>
                                <p>Total Savings</p>
                            </div>
                            <div class="stat-box">
                                <h4>
                                    @if($qualified)
                                        {{ number_format(floatval($sacco['loanStats']['total'] ?? 0)) }}
                                    @else
                                        --
                                    @endif
                                </h4>
                                <p>Active Loans</p>
                            </div>
                            <div class="stat-box">
                                <h4>{{ floatval($sacco['averageAttendance'] ?? 0) }}</h4>
                                <p>Average Attendance</p>
                            </div>
                            <div class="stat-box">
                                <h4>{{ number_format(floatval($sacco['totalMeetings'] ?? 0)) }}</h4>
                                <p>Total Meetings</p>
                            </div>
                        </div>

                        <div class="demographics">
                            <div class="demographic-item">
                                <h5>{{ number_format(floatval($sacco['maleMembers'] ?? 0)) }}</h5>
                                <small>Male</small>
                            </div>
                            <div class="demographic-item">
                                <h5>{{ number_format(floatval($sacco['femaleMembers'] ?? 0)) }}</h5>
                                <small>Female</small>
                            </div>
                            <div class="demographic-item">
                                <h5>{{ number_format(floatval($sacco['youthMembers'] ?? 0)) }}</h5>
                                <small>Youth</small>
                            </div>
                        </div>

                        <div class="stat-box" style="margin-top:1rem;background:#eef9f0;text-align:center;border-radius:6px;">
                            @if($qualified)
                                <h4>UGX {{ number_format(floatval($sacco['maxLoanAmount'] ?? 0)) }}</h4>
                                <p>Max Loan Amount</p>
                            @else
                                <h4>--</h4>
                                <p>Max Loan Amount</p>
                            @endif
                        </div>

                        <div class="button-container">
                            <button class="btn btn-primary" onclick="viewDetails(this)">
                                <i class="fas fa-eye"></i> View Details
                            </button>
                            <button class="btn btn-success" onclick="exportData(this)">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Modal -->
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
                <i class="fas fa-download"></i> Export Data
            </button>
        </div>
    </div>
</div>
</div>
</div>

<script>
let allSaccoData = [];
let currentSaccoData = null;

document.addEventListener('DOMContentLoaded', function() {
// Capitalize group names
document.querySelectorAll('.sacco-header h3').forEach(el => {
    el.textContent = el.textContent.replace(/\b\w/g, c => c.toUpperCase());
});

// Gather sacco data from each .sacco-card
document.querySelectorAll('.sacco-card').forEach(card => {
    const data = JSON.parse(card.dataset.sacco);

    // In JS, confirm qualification (≥4 meetings, ≥10 members, >0 savings)
    data.qualified = data.qualified ??
        (parseFloat(data.totalMeetings) >= 4 &&
         parseFloat(data.totalMembers) >= 10 &&
         parseFloat(data.savingsStats.totalBalance) > 0);

    allSaccoData.push({
        element: card.closest('.sacco-item'),
        data,
        attendance: parseFloat(data.averageAttendance || 0),
        savings: parseFloat(data.savingsStats.totalBalance || 0),
        loans: parseFloat(data.loanStats.total || 0),
        members: parseFloat(data.totalMembers || 0)
    });
});

sortSaccoData();
});

function sortSaccoData() {
allSaccoData.sort((a, b) => {
    const A = a.data;
    const B = b.data;

    if (A.qualified && !B.qualified) return -1;
    if (!A.qualified && B.qualified) return 1;

    const meetingsA = parseFloat(A.totalMeetings || 0);
    const meetingsB = parseFloat(B.totalMeetings || 0);
    if (meetingsB !== meetingsA) {
        return meetingsB - meetingsA;
    }

    const attendanceA = parseFloat(A.averageAttendance || 0);
    const attendanceB = parseFloat(B.averageAttendance || 0);
    return attendanceB - attendanceA;
});

const grid = document.getElementById('saccoGrid');
allSaccoData.forEach(obj => {
    grid.appendChild(obj.element);
});
}

function formatNumber(num) {
if (!num || isNaN(num)) return '0';
return parseFloat(num).toLocaleString('en-US');
}

function generateMetricsList(metrics) {
return metrics.map(m => `
    <div class="metric-item">
        <span>${m.label}</span>
        <span class="metric-value">${m.value}</span>
    </div>
`).join('');
}

function viewDetails(button) {
const card = button.closest('.sacco-card');
const sacco = JSON.parse(card.dataset.sacco);
currentSaccoData = sacco;

document.getElementById('modalTitle').textContent = sacco.name;

if (!sacco.qualified) {
    document.getElementById('creditScore').textContent = 'N/A';
    document.getElementById('creditDescription').textContent = 'Not qualify for crediting';
} else {
    document.getElementById('creditScore').textContent = sacco.creditScore.score ?? 'N/A';
    document.getElementById('creditDescription').textContent = sacco.creditScore.description;
}

document.getElementById('membershipMetrics').innerHTML = generateMetricsList([
    { label: 'Total Members', value: formatNumber(sacco.totalMembers) },
    { label: 'Male Members', value: formatNumber(sacco.maleMembers) },
    { label: 'Female Members', value: formatNumber(sacco.femaleMembers) },
    { label: 'Youth Members', value: formatNumber(sacco.youthMembers) },
    { label: 'Total Meetings', value: formatNumber(sacco.totalMeetings) }
]);

const activeLoansVal = sacco.qualified ? formatNumber(sacco.loanStats.total) : '--';
const principal = sacco.qualified ? `UGX ${formatNumber(sacco.loanStats.principal)}` : '--';
const interest = sacco.qualified ? `UGX ${formatNumber(sacco.loanStats.interest)}` : '--';
const repay = sacco.qualified ? `UGX ${formatNumber(sacco.loanStats.repayments)}` : '--';
const maxLoan = sacco.qualified ? `UGX ${formatNumber(sacco.maxLoanAmount)}` : '--';

document.getElementById('loanMetrics').innerHTML = generateMetricsList([
    { label: 'Active Loans', value: activeLoansVal },
    { label: 'Total Principal', value: principal },
    { label: 'Total Interest', value: interest },
    { label: 'Repayments', value: repay },
    { label: 'Max Loan Amount', value: maxLoan }
]);

document.getElementById('savingsMetrics').innerHTML = generateMetricsList([
    {
        label: 'Total Savings',
        value: `UGX ${formatNumber(sacco.savingsStats.totalBalance)}`
    },
    {
        label: 'Total Accounts',
        value: formatNumber(sacco.savingsStats.totalAccounts)
    },
    {
        label: 'Average per Member',
        value: parseFloat(sacco.totalMembers) > 0
            ? `UGX ${formatNumber(sacco.savingsStats.totalBalance / sacco.totalMembers)}`
            : 'N/A'
    }
]);

document.getElementById('saccoDetails').style.display = 'block';
document.body.style.overflow = 'hidden';
}

function generateMetricsList(metrics) {
        return metrics.map(m => `
            <div class="metric-item">
                <span>${m.label}</span>
                <span class="metric-value">${m.value}</span>
            </div>
        `).join('');
    }

    function closeModal() {
        document.getElementById('saccoDetails').style.display = 'none';
        document.body.style.overflow = '';
    }

    function exportData(button) {
        const card = button.closest('.sacco-card');
        const sacco = JSON.parse(card.dataset.sacco);
        generateExport(sacco);
    }

    function exportCurrentSaccoData() {
        if (currentSaccoData) {
            generateExport(currentSaccoData);
        }
    }

    function exportAllData() {
        const visible = allSaccoData.filter(s => s.element.style.display !== 'none');

        const lines = [
            ['VSLA Groups Credit Score Report'],
            ['Generated on:', new Date().toLocaleString()],
            ['Number of Groups:', visible.length],
            [''],
            [
                'Group Name', 'Qualified?',
                'Credit Score', 'Total Members', 'Male', 'Female', 'Youth',
                'Total Savings', 'Active Loans', 'Avg Attendance', 'Total Meetings'
            ].join(',')
        ];

        visible.forEach(s => {
            const d = s.data;
            lines.push([
                d.name,
                d.qualified ? 'Yes' : 'No',
                d.qualified ? (d.creditScore.score ?? 'N/A') : 'N/A',
                d.totalMembers,
                d.maleMembers,
                d.femaleMembers,
                d.youthMembers,
                d.savingsStats.totalBalance,
                d.qualified ? d.loanStats.total : '--',
                d.averageAttendance + '%',
                d.totalMeetings
            ].join(','));
        });

        downloadCSV(lines.join('\n'), 'vsla_groups_report.csv');
    }

    function generateExport(sacco) {
        const isQual = sacco.qualified;
        const csvContent = [
            ['VSLA Credit Score Report'],
            ['Generated on:', new Date().toLocaleString()],
            [''],
            ['Basic Information'],
            ['Group Name:', sacco.name],
            ['Qualified?:', isQual ? 'Yes' : 'No'],
            ['Credit Score:', isQual ? (sacco.creditScore.score ?? 'N/A') : 'N/A'],
            ['Credit Standing:', isQual ? sacco.creditScore.description : 'Not qualify for crediting'],
            [''],
            ['Membership Statistics'],
            ['Total Members:', sacco.totalMembers],
            ['Male Members:', sacco.maleMembers],
            ['Female Members:', sacco.femaleMembers],
            ['Youth Members:', sacco.youthMembers],
            ['Total Meetings:', sacco.totalMeetings],
            [''],
            ['Financial Statistics'],
            [
                'Total Savings Balance:',
                'UGX ' + formatNumber(sacco.savingsStats.totalBalance)
            ],
            ['Active Loans:', isQual ? sacco.loanStats.total : '--'],
            [
                'Total Principal:',
                isQual ? 'UGX ' + formatNumber(sacco.loanStats.principal) : '--'
            ],
            [
                'Total Interest:',
                isQual ? 'UGX ' + formatNumber(sacco.loanStats.interest) : '--'
            ],
            [
                'Total Repayments:',
                isQual ? 'UGX ' + formatNumber(sacco.loanStats.repayments) : '--'
            ],
            [
                'Max Loan Amount:',
                isQual ? 'UGX ' + formatNumber(sacco.maxLoanAmount) : '--'
            ],
            [''],
            ['Performance Metrics'],
            ['Average Attendance:', sacco.averageAttendance + '%'],
            [
              'Savings per Member:',
              sacco.totalMembers > 0
                ? 'UGX ' + formatNumber(sacco.savingsStats.totalBalance / sacco.totalMembers)
                : 'N/A'
            ]
        ].map(row => row.join(',')).join('\n');

        downloadCSV(csvContent, `${sacco.name}_credit_report.csv`);
    }

    function downloadCSV(content, filename) {
        const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function formatNumber(num) {
        if (!num || isNaN(num)) return '0';
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    // Listen to filters
    document.getElementById('searchInput').addEventListener('input', applyFilters);
    document.getElementById('attendanceFilter').addEventListener('change', applyFilters);
    document.getElementById('savingsFilter').addEventListener('change', applyFilters);
    document.getElementById('loansFilter').addEventListener('change', applyFilters);
    document.getElementById('membersFilter').addEventListener('change', applyFilters);

    // Close modal if clicked outside
    window.onclick = function(e) {
        if (e.target === document.getElementById('saccoDetails')) {
            closeModal();
        }
    };
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
</script>
</body>
</html>