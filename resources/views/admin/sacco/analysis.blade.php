<!DOCTYPE html>
<html lang="en">
<head>
    <!-- ... (same head content as before) -->
</head>
<body>
    <div class="dashboard-header">
        <div class="container">
            <div class="header-content">
                <h1>VSLA Credit Score</h1>
                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Search groups..." id="searchInput">
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Filters Bar -->
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

        <!-- SACCO Cards Grid -->
        <div class="row">
            @foreach($saccos as $sacco)
            <div class="col-md-6 col-lg-4 sacco-item">
                <div class="sacco-card" data-sacco="{{ json_encode($sacco) }}">
                    <div class="sacco-header">
                        <h3>{{ $sacco['name'] }}</h3>
                        <!-- Initially loading... and we will AJAX fetch score -->
                        <span class="credit-badge credit-loading" data-sacco-id="{{ $sacco['id'] }}">
                            Loading...
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

    <script>
        let allSaccoData = [];
        let currentSaccoData = null;

        document.addEventListener('DOMContentLoaded', function() {
            // Collect all SACCO data
            document.querySelectorAll('.sacco-card').forEach(card => {
                const saccoData = JSON.parse(card.dataset.sacco);
                allSaccoData.push({
                    element: card.closest('.sacco-item'),
                    data: saccoData,
                    attendance: saccoData.averageAttendance,
                    savings: saccoData.savingsStats.totalBalance,
                    loans: saccoData.loanStats.total,
                    members: saccoData.totalMembers
                });
            });

            // Fetch credit scores via AJAX
            document.querySelectorAll('.credit-badge[data-sacco-id]').forEach(badge => {
                const saccoId = badge.getAttribute('data-sacco-id');
                badge.textContent = 'Loading...';

                fetch(`/credit-score/${saccoId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.score !== null) {
                            badge.textContent = 'Score: ' + data.score;
                            badge.classList.remove('credit-loading');
                            if (data.score >= 80) {
                                badge.classList.add('credit-high');
                            } else if (data.score >= 60) {
                                badge.classList.add('credit-medium');
                            } else {
                                badge.classList.add('credit-low');
                            }

                            // Store credit score into the sacco data as well
                            const sacco = allSaccoData.find(s => s.data.id == saccoId);
                            if (sacco) {
                                sacco.data.creditScore = data;
                            }
                        } else {
                            badge.textContent = 'N/A';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching score:', error);
                        badge.textContent = 'Error';
                    });
            });
        });

        function applyFilters() {
            const attendance = document.getElementById('attendanceFilter').value;
            const savings = document.getElementById('savingsFilter').value;
            const loans = document.getElementById('loansFilter').value;
            const members = document.getElementById('membersFilter').value;
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();

            allSaccoData.forEach(sacco => {
                let show = true;
                if (attendance && sacco.attendance < parseFloat(attendance)) show = false;
                if (savings && sacco.savings < parseFloat(savings)) show = false;
                if (loans && sacco.loans < parseInt(loans)) show = false;
                if (members && sacco.members < parseInt(members)) show = false;
                if (searchTerm && !sacco.data.name.toLowerCase().includes(searchTerm)) show = false;

                sacco.element.style.display = show ? 'block' : 'none';
            });
        }

        function resetFilters() {
            document.getElementById('attendanceFilter').value = '';
            document.getElementById('savingsFilter').value = '';
            document.getElementById('loansFilter').value = '';
            document.getElementById('membersFilter').value = '';
            document.getElementById('searchInput').value = '';

            allSaccoData.forEach(sacco => {
                sacco.element.style.display = 'block';
            });
        }

        function viewDetails(button) {
            const saccoCard = button.closest('.sacco-card');
            const sacco = JSON.parse(saccoCard.dataset.sacco);
            // If creditScore not yet loaded, we handle that:
            const savedSacco = allSaccoData.find(s => s.data.id == sacco.id);
            if (savedSacco && savedSacco.data.creditScore) {
                sacco.creditScore = savedSacco.data.creditScore;
            } else {
                sacco.creditScore = { score: 'N/A', description: 'Score not loaded yet.' };
            }

            currentSaccoData = sacco;

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
                {
                    label: 'Average per Member',
                    value: sacco.totalMembers > 0
                            ? 'UGX ' + formatNumber(sacco.savingsStats.totalBalance / sacco.totalMembers)
                            : 'UGX 0'
                }
            ]);

            document.getElementById('saccoDetails').style.display = 'block';
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
            document.getElementById('saccoDetails').style.display = 'none';
            document.body.style.overflow = '';
        }

        function exportData(button) {
            const saccoCard = button.closest('.sacco-card');
            const sacco = JSON.parse(saccoCard.dataset.sacco);
            const found = allSaccoData.find(s => s.data.id == sacco.id);
            if (found && found.data.creditScore) {
                sacco.creditScore = found.data.creditScore;
            }
            generateExport(sacco);
        }

        function exportCurrentSaccoData() {
            if (currentSaccoData) {
                generateExport(currentSaccoData);
            }
        }

        function exportAllData() {
            const visibleSaccos = allSaccoData.filter(sacco =>
                sacco.element.style.display !== 'none'
            );

            const combinedData = [
                ['VSLA Groups Credit Score Report'],
                ['Generated on:', new Date().toLocaleString()],
                ['Number of Groups:', visibleSaccos.length],
                [''],
                ['Group Name,Credit Score,Total Members,Male,Female,Youth,Total Savings,Active Loans,Attendance Rate']
            ];

            visibleSaccos.forEach(sacco => {
                const score = sacco.data.creditScore && sacco.data.creditScore.score !== null
                              ? sacco.data.creditScore.score
                              : 'N/A';
                combinedData.push([
                    sacco.data.name,
                    score,
                    sacco.data.totalMembers,
                    sacco.data.maleMembers,
                    sacco.data.femaleMembers,
                    sacco.data.youthMembers,
                    sacco.data.savingsStats.totalBalance,
                    sacco.data.loanStats.total,
                    sacco.data.averageAttendance + '%'
                ].join(','));
            });

            downloadCSV(combinedData.join('\n'), 'vsla_groups_report.csv');
        }

        function generateExport(sacco) {
            const score = sacco.creditScore && sacco.creditScore.score !== null
                          ? sacco.creditScore.score : 'N/A';
            const desc = sacco.creditScore && sacco.creditScore.description
                         ? sacco.creditScore.description : 'No description';

            const csvContent = [
                ['SACCO Credit Score Report'],
                ['Generated on:', new Date().toLocaleString()],
                [''],
                ['Basic Information'],
                ['Group Name:', sacco.name],
                ['Credit Score:', score],
                ['Credit Standing:', desc],
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
                ['Savings per Member:', sacco.totalMembers > 0
                    ? `UGX ${formatNumber(sacco.savingsStats.totalBalance / sacco.totalMembers)}`
                    : `UGX 0`],
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

        function formatNumber(number) {
            if (typeof number !== 'number') {
                number = parseFloat(number) || 0;
            }
            return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        // Event Listeners
        document.getElementById('searchInput').addEventListener('input', applyFilters);
        document.getElementById('attendanceFilter').addEventListener('change', applyFilters);
        document.getElementById('savingsFilter').addEventListener('change', applyFilters);
        document.getElementById('loansFilter').addEventListener('change', applyFilters);
        document.getElementById('membersFilter').addEventListener('change', applyFilters);

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('saccoDetails');
            if (event.target === modal) {
                closeModal();
            }
        };

        // Close modal on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>
