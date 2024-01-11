<style>
    /* CSS styles */
    .loan-category {
        background-color: #f9f9f9;
        border: 1px solid #ccc;
        padding: 15px;
        text-align: center;
        margin-bottom: 20px;
        border-radius: 5px;
    }

    .loan-category h3 {
        margin-bottom: 20px;
        font-size: 28px;
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

    .details {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-top: 1px solid #ccc;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h3 style="font-size: 28px; font-weight: bold; color: #3B88D4; text-align: center;">Total Sum of LOAN Money Disbursed by Category</h3>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="loan-category">
                <div class="row">
                    <div class="col-md-4 column-divider">
                        <p>Sum of LOAN Money for Women</p>
                        <h4>{{ $loanSumForWomen }}</h4>
                    </div>
                    <div class="col-md-4 column-divider">
                        <p>Sum of LOAN Money for Men</p>
                        <h4>{{ $loanSumForMen }}</h4>
                    </div>
                    <div class="col-md-4">
                        <p>Sum of LOAN Money for Youths</p>
                        <h4>{{ $loanSumForYouths }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
