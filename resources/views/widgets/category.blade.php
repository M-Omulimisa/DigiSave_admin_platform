<link href="{{ asset('css/app.css') }}" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.12.0-2/css/fontawesome.min.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.12.0-2/css/all.min.css" />

<style>
    .loan-category {
        background-color: #f9f9f9;
        border: 1px solid #ccc;
        padding: 15px;
        text-align: center;
        margin-bottom: 20px;
        border-radius: 10px;
    }

    .loan-category h3 {
        margin-bottom: 20px;
        font-size: 24px;
        font-weight: bold;
        color: #398c06;
    }

    .loan-category p {
        font-size: 14px;
        color: #333;
        margin-bottom: 10px;
    }

    .loan-category h4 {
        font-size: 20px;
        color: #333;
        font-weight: bold;
        margin-bottom: 10px;
    }

    .progress {
        height: 15px;
        background-color: #e9ecef;
        border-radius: 5px;
        overflow: hidden;
    }

    .progress-bar {
        height: 100%;
        text-align: center;
        color: #fff;
        line-height: 15px;
        transition: width 0.6s ease;
    }

    .progress-bar.women {
        background-color: #ff6384;
    }

    .progress-bar.men {
        background-color: #36a2eb;
    }

    .progress-bar.youths {
        background-color: #4bc0c0;
    }

    .progress-bar.pwds {
        background-color: #716802;
    }

    .loan-category .row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
    }

    .loan-category .column-divider {
        border-right: 1px solid #ccc;
        padding-right: 20px;
    }

    .loan-category .column-divider:last-child {
        border-right: none;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h3 style="font-size: 24px; font-weight: bold; color: #398c06; text-align: center;">Loan Disbursements</h3>
        </div>
    </div>

    <!-- Existing Loan Categories: Women, Men, Youth, PWD -->
    <div class="row">
        <div class="col-12">
            <div class="loan-category">
                <div class="row">
                    <div class="col-md-4 column-divider">
                        <p>Number of Loans Disbursed to Women</p>
                        <h4>{{ $loansDisbursedToWomen }}</h4>
                        <div class="progress">
                            <div class="progress-bar women" role="progressbar" style="width: {{ $percentageLoansWomen }}%;" aria-valuenow="{{ $percentageLoansWomen }}" aria-valuemin="0" aria-valuemax="100">{{ number_format($percentageLoansWomen, 2) }}%</div>
                        </div>
                    </div>
                    <div class="col-md-4 column-divider">
                        <p>Number of Loans Disbursed to Men</p>
                        <h4>{{ $loansDisbursedToMen }}</h4>
                        <div class="progress">
                            <div class="progress-bar men" role="progressbar" style="width: {{ $percentageLoansMen }}%;" aria-valuenow="{{ $percentageLoansMen }}" aria-valuemin="0" aria-valuemax="100">{{ number_format($percentageLoansMen, 2) }}%</div>
                        </div>
                    </div>
                    <div class="col-md-4 column-divider">
                        <p>Number of Loans Disbursed to Youths</p>
                        <h4>{{ $loansDisbursedToYouths }}</h4>
                        <div class="progress">
                            <div class="progress-bar youths" role="progressbar" style="width: {{ $percentageLoansYouths }}%;" aria-valuenow="{{ $percentageLoansYouths }}" aria-valuemin="0" aria-valuemax="100">{{ number_format($percentageLoansYouths, 2) }}%</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <p>Number of Loans Disbursed to PWDs</p>
                        <h4>{{ $pwdTotalLoanCount }}</h4>
                        <div class="progress">
                            <div class="progress-bar pwds" role="progressbar" style="width: {{ $percentageLoansPwd }}%;" aria-valuenow="{{ $percentageLoansPwd }}" aria-valuemin="0" aria-valuemax="100">{{ number_format($percentageLoansPwd, 2) }}%</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- New Section: Refugee Loans by Gender -->
            <div class="loan-category">
                <div class="row">
                    <div class="col-md-6 column-divider">
                        <p>Number of Loans Disbursed to Refugee Women</p>
                        <h4>{{ $refugeeFemaleLoanCount }}</h4>
                        <!-- If you want progress bars, compute percentage and add similar as above -->
                    </div>
                    <div class="col-md-6">
                        <p>Number of Loans Disbursed to Refugee Men</p>
                        <h4>{{ $refugeeMaleLoanCount }}</h4>
                    </div>
                </div>
            </div>

            <!-- Existing Loan Amount Categories: Women, Men, Youth, PWD -->
            <div class="loan-category">
                <div class="row">
                    <div class="col-md-4 column-divider">
                        <p>Loan Amount Disbursed to Women</p>
                        <h4>{{ number_format(abs($loanSumForWomen), 2) }} UGX</h4>
                    </div>
                    <div class="col-md-4 column-divider">
                        <p>Loan Amount Disbursed to Men</p>
                        <h4>{{ number_format(abs($loanSumForMen), 2) }} UGX</h4>
                    </div>
                    <div class="col-md-4 column-divider">
                        <p>Loan Amount Disbursed to Youth</p>
                        <h4>{{ number_format(abs($loanSumForYouths), 2) }} UGX</h4>
                    </div>
                    <div class="col-md-4">
                        <p>Loan Amount Disbursed to PWDs</p>
                        <h4>{{ number_format(abs($pwdTotalLoanBalance), 2) }} UGX</h4>
                    </div>
                </div>
            </div>

            <!-- New Section: Refugee Loan Amounts by Gender -->
            <div class="loan-category">
                <div class="row">
                    <div class="col-md-6 column-divider">
                        <p>Loan Amount Disbursed to Refugee Women</p>
                        <h4>{{ number_format(abs($refugeeFemaleLoanAmount), 2) }} UGX</h4>
                    </div>
                    <div class="col-md-6">
                        <p>Loan Amount Disbursed to Refugee Men</p>
                        <h4>{{ number_format(abs($refugeeMaleLoanAmount), 2) }} UGX</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
