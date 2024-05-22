<!-- resources/views/widgets/users_with_balances.blade.php -->
<div class="box">
    <div class="box-header with-border">
        <h3 class="box-title">Users and Balances</h3>
    </div>
    <div class="box-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($usersWithBalances as $user)
                    <tr>
                        <td>{{ $user['name'] }}</td>
                        <td>{{ number_format($user['balance'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
