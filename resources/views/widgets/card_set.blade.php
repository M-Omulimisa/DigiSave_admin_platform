<link href="{{ asset('css/app.css') }}" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.12.0-2/css/fontawesome.min.css" />  
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.12.0-2/css/all.min.css" />  
<style>
    /* .small-box .inner h4:hover, .small-box .inner p:hover {
        text-decoration: underline;
        color: rgb(0, 162, 255);
        cursor: pointer;
    } */
    .savings {
        color: green;
        font-weight: bold;
    }
    .small-box {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
        word-wrap: break-word;
        background-color: #fff;
        background-clip: border-box;
        border: 1px solid rgba(0, 0, 0, .125);
        border-radius: .25rem;
        overflow: hidden;
    }
    .small-box .inner {
        padding: 10px;
    }
</style>

<div class="row">
    <div class="col-md-3">
        <div class="small-box bg-white">
            <div class="inner">
                <div class="row">
                    <div class="col-md-4">
                        <i class="fas fa-female fa-3x"></i>
                    </div>
                    <div class="col-md-8">
                        <h4 style="display: inline-block; margin-right: 10px;">{{ $femaleMembersCount }}</h4>
                        <p style="display: inline-block;">Female Members</p>
                        <p class="savings">Total Savings: {{ $femaleTotalBalance }} UGX</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="small-box bg-white">
            <div class="inner">
                <div class="row">
                    <div class="col-md-4">
                        <i class="fas fa-male fa-3x"></i>
                    </div>
                    <div class="col-md-8">
                        <h4 style="display: inline-block; margin-right: 10px;">{{ $maleMembersCount }}</h4>
                        <p style="display: inline-block;">Male Members</p>
                        <p class="savings">Total Savings: {{ $maleTotalBalance }} UGX</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="small-box bg-white">
            <div class="inner">
                <div class="row">
                    <div class="col-md-4">
                        <i class="fas fa-users fa-3x"></i>
                    </div>
                    <div class="col-md-8">
                        <h4 style="display: inline-block; margin-right: 10px;">{{ $youthMembersCount }}</h4>
                        <p style="display: inline-block;">Youth Members</p>
                        <p class="savings">Total Savings: {{ $youthTotalBalance }} UGX</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="small-box bg-white">
            <div class="inner">
                <div class="row">
                    <div class="col-md-4">
                        <i class="fas fa-wheelchair fa-3x"></i>
                    </div>
                    <div class="col-md-8">
                        <h4 style="display: inline-block; margin-right: 10px;">{{ $pwdMembersCount }}</h4>
                        <p style="display: inline-block;">PWD Members</p>
                        <p class="savings">Total Savings: {{ $pwdTotalBalance }} UGX</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
