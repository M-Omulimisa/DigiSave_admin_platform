<link href="{{ asset('css/app.css') }}" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.12.0-2/css/fontawesome.min.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.12.0-2/css/all.min.css" />

<style>
    .card-container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin: 20px 0;
    }

    .card {
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        padding: 20px;
        flex: 1 1 calc(50% - 20px);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: transform 0.2s ease-in-out;
        cursor: pointer;
    }

    .card:hover {
        transform: translateY(-5px);
    }

    .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 15px;
        border-bottom: 2px solid #4CAF50;
        padding-bottom: 5px;
    }

    .card-header .icon {
        font-size: 20px;
    }

    .card-title {
        font-size: 18px;
        font-weight: bold;
        color: #333;
    }

    .card-body {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-top: 10px;
    }

    .count-container {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 10px;
        position: relative;
    }

    .count-container:not(:first-child)::before {
        content: "";
        position: absolute;
        left: -10px;
        top: 0;
        bottom: 0;
        width: 1px;
        background-color: #ddd;
    }

    .count-icon {
        font-size: 24px;
    }

    .count {
        font-size: 24px;
        font-weight: bold;
        color: #333;
    }

    .description {
        font-size: 12px;
        color: #777;
    }

    .icon-users {
        color: #007bff;
    }

    .icon-user-tie {
        color: #28a745;
    }

    .icon-user-friends {
        color: #17a2b8;
    }

    .icon-user-shield {
        color: #ffc107;
    }

    .icon-user-check {
        color: #6f42c1;
    }

    .icon-building {
        color: #fd7e14;
    }

    .icon-user-graduate {
        color: #20c997;
    }

    .icon-percentage {
        color: #dc3545;
    }
</style>

<div class="card-container">
    <div class="card">
        <div class="card-header">
            <div class="card-title">VSLA Statistics</div>
            <div class="icon"><i class="fas fa-chart-bar"></i></div>
        </div>
        <div class="card-body">
            <div class="count-container">
                <div class="count-icon icon-users"><i class="fas fa-users"></i></div>
                <div>
                    <div class="count">{{ $totalSaccos }}</div>
                    <div class="description">Total VSLAs</div>
                </div>
            </div>
            <div class="count-container">
                <div class="count-icon icon-user-tie"><i class="fas fa-user-tie"></i></div>
                <div>
                    <div class="count">{{ $totalOrgAdmins }}</div>
                    <div class="description">Org Admins</div>
                </div>
            </div>
            <div class="count-container">
                <div class="count-icon icon-user-friends"><i class="fas fa-user-friends"></i></div>
                <div>
                    <div class="count">{{ $totalMembers }}</div>
                    <div class="description">Group Members</div>
                </div>
            </div>
            <div class="count-container">
                <div class="count-icon icon-user-shield"><i class="fas fa-user-shield"></i></div>
                <div>
                    <div class="count">{{ $totalPwdMembers }}</div>
                    <div class="description">PWDs</div>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <div class="card-title">Agent & Youth Statistics</div>
            <div class="icon"><i class="fas fa-chart-line"></i></div>
        </div>
        <div class="card-body">
            <div class="count-container">
                <div class="count-icon icon-user-check"><i class="fas fa-user-check"></i></div>
                <div>
                    <div class="count">{{ $villageAgents }}</div>
                    <div class="description">Village Agents</div>
                </div>
            </div>
            <div class="count-container">
                <div class="count-icon icon-building"><i class="fas fa-building"></i></div>
                <div>
                    <div class="count">{{ $organisationCount }}</div>
                    <div class="description">Organisations</div>
                </div>
            </div>
            <div class="count-container">
                <div class="count-icon icon-user-graduate"><i class="fas fa-user-graduate"></i></div>
                <div>
                    <div class="count">{{ $totalAccounts }}</div>
                    <div class="description">Group Accounts</div>
                </div>
            </div>
            <div class="count-container">
                <div class="count-icon icon-percentage"><i class="fas fa-percentage"></i></div>
                <div>
                    <div class="count">{{ $youthMembersPercentage }}%</div>
                    <div class="description">Youths</div>
                </div>
            </div>
        </div>
    </div>
</div>
