// resources/views/pdf/sacco.blade.php
<!DOCTYPE html>
<html>
<head>
    <title>Sacco PDF</title>
</head>
<body>
    <h1>{{ $sacco->name }}</h1>
    <p>Phone Number: {{ $sacco->phone_number }}</p>
    <p>Uses Shares: {{ $sacco->uses_shares ? 'Yes' : 'No' }}</p>
    <p>Share Price: {{ $sacco->share_price }}</p>
    <p>Physical Address: {{ $sacco->physical_address }}</p>
    <p>Chairperson Name: {{ $sacco->chairperson_name }}</p>
    <p>Created At: {{ $sacco->created_at->format('d M Y') }}</p>
</body>
</html>
