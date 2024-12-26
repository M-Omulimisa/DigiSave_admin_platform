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

    <!-- Main Loan Categories: Women, Men, Youth, PWD -->
    <div class="row">
        <div class="col-12">
            <div class="loan-category">
                <div class="row">
                    <div class="col-md-4 column-divider">
                        <p>Number of Loans Disbursed to Women</p>
                        <h4>{{ $loansDisbursedToWomen }}</h4>
                        <div class="progress">
                            <div class="progress-bar women" role="progressbar"
                                style="width: {{ $percentageLoansWomen }}%;"
                                aria-valuenow="{{ $percentageLoansWomen }}"
                                aria-valuemin="0"
                                aria-valuemax="100">{{ number_format($percentageLoansWomen, 2) }}%</div>
                        </div>
                    </div>
                    <div class="col-md-4 column-divider">
                        <p>Number of Loans Disbursed to Men</p>
                        <h4>{{ $loansDisbursedToMen }}</h4>
                        <div class="progress">
                            <div class="progress-bar men" role="progressbar"
                                style="width: {{ $percentageLoansMen }}%;"
                                aria-valuenow="{{ $percentageLoansMen }}"
                                aria-valuemin="0"
                                aria-valuemax="100">{{ number_format($percentageLoansMen, 2) }}%</div>
                        </div>
                    </div>
                    <div class="col-md-4 column-divider">
                        <p>Number of Loans Disbursed to Youths</p>
                        <h4>{{ $loansDisbursedToYouths }}</h4>
                        <div class="progress">
                            <div class="progress-bar youths" role="progressbar"
                                style="width: {{ $percentageLoansYouths }}%;"
                                aria-valuenow="{{ $percentageLoansYouths }}"
                                aria-valuemin="0"
                                aria-valuemax="100">{{ number_format($percentageLoansYouths, 2) }}%</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <p>Number of Loans Disbursed to PWDs</p>
                        <h4>{{ $pwdTotalLoanCount }}</h4>
                        <div class="progress">
                            <div class="progress-bar pwds" role="progressbar"
                                style="width: {{ $percentageLoansPwd }}%;"
                                aria-valuenow="{{ $percentageLoansPwd }}"
                                aria-valuemin="0"
                                aria-valuemax="100">{{ number_format($percentageLoansPwd, 2) }}%</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PWD Loans by Gender -->
            <div class="loan-category">
                <h3>PWD Loan Distribution by Gender</h3>
                <div class="row">
                    <div class="col-md-6 column-divider">
                        <p>Number of Loans Disbursed to PWD Women</p>
                        <h4>{{ $pwdFemaleLoanCount }}</h4>
                        <div class="progress">
                            <div class="progress-bar women" role="progressbar"
                                style="width: {{ $pwdFemaleLoanCount > 0 ? ($pwdFemaleLoanCount / $pwdTotalLoanCount * 100) : 0 }}%;">
                                {{ $pwdFemaleLoanCount > 0 ? number_format($pwdFemaleLoanCount / $pwdTotalLoanCount * 100, 2) : 0 }}%
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <p>Number of Loans Disbursed to PWD Men</p>
                        <h4>{{ $pwdMaleLoanCount }}</h4>
                        <div class="progress">
                            <div class="progress-bar men" role="progressbar"
                                style="width: {{ $pwdMaleLoanCount > 0 ? ($pwdMaleLoanCount / $pwdTotalLoanCount * 100) : 0 }}%;">
                                {{ $pwdMaleLoanCount > 0 ? number_format($pwdMaleLoanCount / $pwdTotalLoanCount * 100, 2) : 0 }}%
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PWD Loan Amounts by Gender -->
            <div class="loan-category">
                <h3>PWD Loan Amount Distribution by Gender</h3>
                <div class="row">
                    <div class="col-md-6 column-divider">
                        <p>Loan Amount Disbursed to PWD Women</p>
                        <h4>{{ number_format(abs($pwdFemaleLoanAmount), 2) }} UGX</h4>
                        <div class="progress">
                            <div class="progress-bar women" role="progressbar"
                                style="width: {{ $pwdTotalLoanBalance > 0 ? ($pwdFemaleLoanAmount / $pwdTotalLoanBalance * 100) : 0 }}%;">
                                {{ $pwdTotalLoanBalance > 0 ? number_format($pwdFemaleLoanAmount / $pwdTotalLoanBalance * 100, 2) : 0 }}%
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <p>Loan Amount Disbursed to PWD Men</p>
                        <h4>{{ number_format(abs($pwdMaleLoanAmount), 2) }} UGX</h4>
                        <div class="progress">
                            <div class="progress-bar men" role="progressbar"
                                style="width: {{ $pwdTotalLoanBalance > 0 ? ($pwdMaleLoanAmount / $pwdTotalLoanBalance * 100) : 0 }}%;">
                                {{ $pwdTotalLoanBalance > 0 ? number_format($pwdMaleLoanAmount / $pwdTotalLoanBalance * 100, 2) : 0 }}%
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Refugee Loans by Gender -->
            <div class="loan-category">
                <h3>Refugee Loan Distribution by Gender</h3>
                <div class="row">
                    <div class="col-md-6 column-divider">
                        <p>Number of Loans Disbursed to Refugee Women</p>
                        <h4>{{ $refugeeFemaleLoanCount }}</h4>
                        <div class="progress">
                            <div class="progress-bar women" role="progressbar"
                                style="width: {{ ($refugeeFemaleLoanCount + $refugeeMaleLoanCount) > 0 ?
                                    ($refugeeFemaleLoanCount / ($refugeeFemaleLoanCount + $refugeeMaleLoanCount) * 100) : 0 }}%;">
                                {{ ($refugeeFemaleLoanCount + $refugeeMaleLoanCount) > 0 ?
                                    number_format($refugeeFemaleLoanCount / ($refugeeFemaleLoanCount + $refugeeMaleLoanCount) * 100, 2) : 0 }}%
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <p>Number of Loans Disbursed to Refugee Men</p>
                        <h4>{{ $refugeeMaleLoanCount }}</h4>
                        <div class="progress">
                            <div class="progress-bar men" role="progressbar"
                                style="width: {{ ($refugeeFemaleLoanCount + $refugeeMaleLoanCount) > 0 ?
                                    ($refugeeMaleLoanCount / ($refugeeFemaleLoanCount + $refugeeMaleLoanCount) * 100) : 0 }}%;">
                                {{ ($refugeeFemaleLoanCount + $refugeeMaleLoanCount) > 0 ?
                                    number_format($refugeeMaleLoanCount / ($refugeeFemaleLoanCount + $refugeeMaleLoanCount) * 100, 2) : 0 }}%
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Refugee Loan Amounts by Gender -->
            <div class="loan-category">
                <h3>Refugee Loan Amount Distribution by Gender</h3>
                <div class="row">
                    <div class="col-md-6 column-divider">
                        <p>Loan Amount Disbursed to Refugee Women</p>
                        <h4>{{ number_format(abs($refugeeFemaleLoanAmount), 2) }} UGX</h4>
                        <div class="progress">
                            <div class="progress-bar women" role="progressbar"
                                style="width: {{ ($refugeeFemaleLoanAmount + $refugeeMaleLoanAmount) > 0 ?
                                    ($refugeeFemaleLoanAmount / ($refugeeFemaleLoanAmount + $refugeeMaleLoanAmount) * 100) : 0 }}%;">
                                {{ ($refugeeFemaleLoanAmount + $refugeeMaleLoanAmount) > 0 ?
                                    number_format($refugeeFemaleLoanAmount / ($refugeeFemaleLoanAmount + $refugeeMaleLoanAmount) * 100, 2) : 0 }}%
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <p>Loan Amount Disbursed to Refugee Men</p>
                        <h4>{{ number_format(abs($refugeeMaleLoanAmount), 2) }} UGX</h4>
                        <div class="progress">
                            <div class="progress-bar men" role="progressbar"
                                style="width: {{ ($refugeeFemaleLoanAmount + $refugeeMaleLoanAmount) > 0 ?
                                    ($refugeeMaleLoanAmount / ($refugeeFemaleLoanAmount + $refugeeMaleLoanAmount) * 100) : 0 }}%;">
                                {{ ($refugeeFemaleLoanAmount + $refugeeMaleLoanAmount) > 0 ?
                                    number_format($refugeeMaleLoanAmount / ($refugeeFemaleLoanAmount + $refugeeMaleLoanAmount) * 100, 2) : 0 }}%
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- General Loan Amounts -->
            <div class="loan-category">
                <h3>General Loan Amount Distribution</h3>
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
        </div>
    </div>
</div>
