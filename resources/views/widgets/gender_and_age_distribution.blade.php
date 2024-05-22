<!-- resources/views/widgets/gender_and_age_distribution.blade.php -->
<div class="row">
    <div class="col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Gender Distribution</h3>
            </div>
            <div class="box-body">
                <canvas id="genderDistributionChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Age Distribution</h3>
            </div>
            <div class="box-body">
                <canvas id="ageDistributionChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Gender Distribution Chart
        var genderCtx = document.getElementById('genderDistributionChart').getContext('2d');
        var genderDistributionChart = new Chart(genderCtx, {
            type: 'pie',
            data: {
                labels: @json($genderDistribution->keys()),
                datasets: [{
                    label: 'Users by Gender',
                    data: @json($genderDistribution->values()),
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.2)', // Male - Blue
                        'rgba(255, 99, 132, 0.2)', // Female - Pink
                        'rgba(153, 102, 255, 0.2)' // Other - Purple
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)', // Male - Blue
                        'rgba(255, 99, 132, 1)', // Female - Pink
                        'rgba(153, 102, 255, 1)' // Other - Purple
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Age Distribution Chart
        var ageCtx = document.getElementById('ageDistributionChart').getContext('2d');
        var ageDistributionChart = new Chart(ageCtx, {
            type: 'bar',
            data: {
                labels: @json($ageDistribution->keys()),
                datasets: [{
                    label: 'Users by Age',
                    data: @json($ageDistribution->values()),
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Age'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Number of Users'
                        },
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>
