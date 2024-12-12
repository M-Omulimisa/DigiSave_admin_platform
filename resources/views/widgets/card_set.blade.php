<link href="{{ asset('css/app.css') }}" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.12.0-2/css/fontawesome.min.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.12.0-2/css/all.min.css" />

<style>
    .stat-card-container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin: 20px 0;
    }

    .stat-card {
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        padding: 20px;
        flex: 1 1 calc(25% - 20px); /* 4 cards per row */
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        cursor: pointer;
        transition: transform 0.3s ease-in-out;
        position: relative;
        font-family: 'Arial', sans-serif;
    }

    .stat-card:hover {
        transform: translateY(-10px);
    }

    .stat-card .title {
        font-size: 16px;
        font-weight: bold;
        color: #333;
        margin-bottom: 10px;
        text-decoration: underline;
    }

    .stat-card .count {
        font-size: 20px;
        font-weight: bold;
        color: #333;
        margin-bottom: 10px;
    }

    .stat-card .description {
        font-size: 12px;
        color: #777;
    }

    .stat-card .icon {
        font-size: 30px;
        position: absolute;
        top: 20px;
        right: 20px;
    }

    .stat-card.blue { background-color: #e7f3ff; }
    .stat-card.yellow { background-color: #fffbe7; }
    .stat-card.orange { background-color: #fff0e7; }
    .stat-card.purple { background-color: #f4e7ff; }
    .stat-card.green { background-color: #e7ffe9; }
    .stat-card.red { background-color: #ffe7e7; }

    .icon-blue { color: #007bff; }
    .icon-yellow { color: #ffc107; }
    .icon-orange { color: #fd7e14; }
    .icon-purple { color: #6f42c1; }
    .icon-green { color: #28a745; }
    .icon-red { color: #dc3545; }
</style>

<div class="stat-card-container">
    <div class="stat-card blue">
        <div class="title">Female Members</div>
        <div class="icon icon-blue"><i class="fas fa-female"></i></div>
        <div class="count">{{ $femaleMembersCount }}</div>
        <div class="description">Total Savings: {{ $femaleTotalBalance }} UGX</div>
    </div>

    <div class="stat-card yellow">
        <div class="title">Male Members</div>
        <div class="icon icon-yellow"><i class="fas fa-male"></i></div>
        <div class="count">{{ $maleMembersCount }}</div>
        <div class="description">Total Savings: {{ $maleTotalBalance }} UGX</div>
    </div>

    <div class="stat-card orange">
        <div class="title">Youth Members</div>
        <div class="icon icon-orange"><i class="fas fa-users"></i></div>
        <div class="count">{{ $youthMembersCount }}</div>
        <div class="description">Total Savings: {{ $youthTotalBalance }} UGX</div>
    </div>

    <div class="stat-card purple">
        <div class="title">PWD Members</div>
        <div class="icon icon-purple"><i class="fas fa-wheelchair"></i></div>
        <div class="count">{{ $pwdMembersCount }}</div>
        <div class="description">Total Savings: {{ $pwdTotalBalance }} UGX</div>
    </div>

    <div class="stat-card green">
        <div class="title">Male Refugees</div>
        <div class="icon icon-green"><i class="fas fa-user-shield"></i></div>
        <div class="count">{{ $refugeeMaleMembersCount }}</div>
        <div class="description">Total Savings: {{ $refugeeMaleSavings }} UGX</div>
    </div>

    <div class="stat-card red">
        <div class="title">Female Refugees</div>
        <div class="icon icon-red"><i class="fas fa-user-shield"></i></div>
        <div class="count">{{ $refugeeFemaleMembersCount }}</div>
        <div class="description">Total Savings: {{ $refugeeFemaleSavings }} UGX</div>
    </div>
</div>
