<style>
    /* CSS styles */
    .loan-category {
        background-color: #f9f9f9;
        border: 1px solid #ccc;
        padding: 15px 0;
        text-align: center;
        margin-bottom: 20px;
        border-radius: 5px;
    }

    .loan-category h3 {
        margin-bottom: 20px;
        font-size: 28px; /* Adjust as needed */
        font-weight: bold;
        color: #3B88D4;
    }

    .loan-category p {
        font-size: 16px;
        color: #666;
        margin-bottom: 10px;
    }

    .loan-category h4 {
        font-size: 24px;
        color: #333;
        font-weight: bold;
    }

    .loan-category .column-divider {
        border-right: 1px solid #ccc;
        padding-right: 10px;
        padding-left: 10px;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h3 style="font-size: 25px; font-weight: bold; color: #3B88D4; text-align: center;">Number of Loan Disbursements</h3>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="loan-category">
                <div class="row">
                    <div class="col-md-4 column-divider">
                        <p>Number of Loans Disbursed to Women</p>
                        <h4>{{ $loansDisbursedToWomen }}</h4>
                    </div>
                    <div class="col-md-4 column-divider">
                        <p>Number of Loans Disbursed to Men</p>
                        <h4>{{ $loansDisbursedToMen }}</h4>
                    </div>
                    <div class="col-md-4">
                        <p>Number of Loans Disbursed to Youths</p>
                        <h4>{{ $loansDisbursedToYouths }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
