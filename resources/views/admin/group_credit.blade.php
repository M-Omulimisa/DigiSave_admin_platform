<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Credit Details</title>
    {{-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet"> --}}
    <style>
        .container {
            max-width: 1200px;
        }
        .table th {
            background-color: #343a40;
            color: #fff;
            width: 30%;
        }
        .alert {
            margin-top: 20px;
        }
        .box {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            background-color: #fff;
            padding: 20px;
            margin-bottom: 20px;
        }
        .box-header {
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 15px;
        }
        .box-title {
            font-size: 1.5rem;
            font-weight: bold;
        }
        pre {
            background-color: #f1f1f1;
            padding: 15px;
            border-radius: 5px;
            overflow: auto;
        }
        .progress {
            height: 20px;
        }
        .progress-bar {
            line-height: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="mb-4">
            <h1 class="text-center">Sacco Credit Details</h1>
            <hr>
        </div>

        <!-- Main Content -->
        <div class="row">
            <div class="col-md-12">
                @if(isset($saccoDetails['error']))
                    <div class="alert alert-danger">
                        {{ $saccoDetails['error'] }}
                    </div>
                @else
                    <div class="box">
                        <div class="box-header">
                            <h3 class="box-title">Credit Details</h3>
                        </div>
                        <div class="box-body; margin-bottom: 10px;">

                            <!-- New Prediction Response Section -->
                            @if(isset($saccoDetails['prediction_response']['error']))
                                <div class="alert alert-danger">
                                    {{ $saccoDetails['prediction_response']['error'] }}
                                </div>
                            @else
                                <div id="prediction-response" class="mt-4">
                                    <h4 class="mb-3">Prediction Response</h4>
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <strong>Credit Score Class:</strong>
                                                    <span id="credit-score-class"></span>
                                                </div>
                                                <div class="col-md-4">
                                                    <strong>Description:</strong>
                                                    <span id="description"></span>
                                                </div>
                                                <div class="col-md-4">
                                                    <strong>Credit Score:</strong>
                                                    <span id="credit-score"></span>
                                                </div>
                                            </div>
                                            <h5 class="mb-3">Indicator Performance</h5>
                                            <div id="performance-indicators"></div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            <div class="box-header">
                                <h3 class="box-title">Group Statistics</h3>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <tbody>
                                        <tr>
                                            <th>Number of Loans</th>
                                            <td>{{ $saccoDetails['number_of_loans'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Total Principal (UGX)</th>
                                            <td>{{ $saccoDetails['total_principal'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Total Interest (UGX)</th>
                                            <td>{{ $saccoDetails['total_interest'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Total Loan Repayments (UGX)</th>
                                            <td>{{ $saccoDetails['total_loan_repayments'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Average Monthly Savings (UGX)</th>
                                            <td>{{ number_format($saccoDetails['average_monthly_savings'], 2, '.', ',') }}</td>
                                        </tr>
                                        <tr>
                                            <th>Number of Members</th>
                                            <td>{{ $saccoDetails['number_of_members'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Number of Men</th>
                                            <td>{{ $saccoDetails['number_of_men'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Number of Women</th>
                                            <td>{{ $saccoDetails['number_of_women'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Number of Youth</th>
                                            <td>{{ $saccoDetails['number_of_youth'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Total Meetings</th>
                                            <td>{{ $saccoDetails['total_meetings'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Max Loan Amount</th>
                                            <td>{{ $saccoDetails['max_loan_amount'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Average Meeting Attendance</th>
                                            <td>{{ $saccoDetails['average_meeting_attendance'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Number of Loans to Men</th>
                                            <td>{{ $saccoDetails['number_of_loans_to_men'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Total Disbursed to Men (UGX)</th>
                                            <td>{{ $saccoDetails['total_disbursed_to_men'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Number of Loans to Women</th>
                                            <td>{{ $saccoDetails['number_of_loans_to_women'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Total Disbursed to Women (UGX)</th>
                                            <td>{{ $saccoDetails['total_disbursed_to_women'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Number of Loans to Youth</th>
                                            <td>{{ $saccoDetails['number_of_loans_to_youth'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Total Disbursed to Youth (UGX)</th>
                                            <td>{{ $saccoDetails['total_disbursed_to_youth'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Number of Savings Accounts</th>
                                            <td>{{ $saccoDetails['number_of_savings_accounts'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Total Savings Balance (UGX)</th>
                                            <td>{{ $saccoDetails['total_savings_balance'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Savings Accounts for Men</th>
                                            <td>{{ $saccoDetails['savings_accounts_for_men'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Total Savings Balance for Men (UGX)</th>
                                            <td>{{ $saccoDetails['total_savings_balance_for_men'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Savings Accounts for Women</th>
                                            <td>{{ $saccoDetails['savings_accounts_for_women'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Total Savings Balance for Women (UGX)</th>
                                            <td>{{ $saccoDetails['total_savings_balance_for_women'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Savings Accounts for Youth</th>
                                            <td>{{ $saccoDetails['savings_accounts_for_youth'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Total Savings Balance for Youth (UGX)</th>
                                            <td>{{ $saccoDetails['total_savings_balance_for_youth'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Average Savings per Member (UGX)</th>
                                            <td>{{ $saccoDetails['average_savings_per_member'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Savings Credit Mobilization</th>
                                            <td>{{ number_format($saccoDetails['savings_credit_mobilization'], 2, '.', ',') }}</td>
                                        </tr>
                                        <tr>
                                            <th>Youth Support Rate</th>
                                            <td>{{ $saccoDetails['youth_support_rate'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Fund Savings Credit Status</th>
                                            <td>{{ $saccoDetails['fund_savings_credit_status'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Total Principal Paid (UGX)</th>
                                            <td>{{ $saccoDetails['total_principal_paid'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Total Interest Paid (UGX)</th>
                                            <td>{{ $saccoDetails['total_interest_paid'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Total Principal Outstanding (UGX)</th>
                                            <td>{{ $saccoDetails['total_principal_outstanding'] }}</td>
                                        </tr>
                                        <tr>
                                            <th>Total Interest Outstanding (UGX)</th>
                                            <td>{{ $saccoDetails['total_interest_outstanding'] }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-5">
            <hr>
            <p class="text-center text-muted">&copy; {{ date('Y') }} Digisave Dashboard. All rights reserved.</p>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Prediction Response Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const predictionData = @json($saccoDetails['prediction_response']);

        document.getElementById('credit-score-class').textContent = predictionData.credit_score_class;
        document.getElementById('description').textContent = predictionData.description;
        document.getElementById('credit-score').textContent = predictionData.credi_score;

        const performanceIndicators = document.getElementById('performance-indicators');

        const indicators = [
            { name: 'Capacity', key: 'Capacity' },
            { name: 'Savings and Credit Mobilization', key: 'Savings and Credit Mobilization' },
            { name: 'Vulnerability', key: 'Vulnerability' },
            { name: 'Maturity', key: 'Maturity' }
        ];

        indicators.forEach(indicator => {
            const data = predictionData.indactor_performance[indicator.key];
            const percentage = (data.score / data.max_points) * 100;

            const indicatorHtml = `
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span>${indicator.name}</span>
                        <span>${data.score} / ${data.max_points}</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: ${percentage}%;"
                             aria-valuenow="${data.score}" aria-valuemin="0" aria-valuemax="${data.max_points}">
                            ${percentage.toFixed(1)}%
                        </div>
                    </div>
                </div>
            `;

            performanceIndicators.insertAdjacentHTML('beforeend', indicatorHtml);
        });
    });
    </script>
</body>
</html>
