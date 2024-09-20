<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to DigiSave – Your Account is Ready!</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4CAF50;
            color: white;
            text-align: center;
            padding: 20px;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 0 0 5px 5px;
        }
        .button {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.8em;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome to DigiSave!</h1>
    </div>
    <div class="content">
        <p>Dear {{$data['first_name']}} {{$data['last_name']}},</p>
        <p>We are excited to welcome you to DigiSave, your new platform for managing and digitizing your savings and loan group transactions!</p>
        <p>Here are your account credentials:</p>
        <ul>
            <li><strong>Username/Email:</strong> {{$data['phone_number']}}</li>
            <li><strong>Password:</strong> {{$data['password']}}</li>
        </ul>
        <p>To get started, please follow these steps:</p>
        <ol>
            <li><strong>Log In:</strong> Visit <a href="{{$data['platformLink']}}">{{$data['platformLink']}}</a></li>
            <li><strong>Enter Your Credentials:</strong> Use the provided username and password to log in.</li>
            <li><strong>Explore DigiSave:</strong> Once you're logged in, you can start managing your VSLA groups, view reports, and monitor group activities with ease.</li>
        </ol>
        <p>If you have any questions or need assistance, please don't hesitate to reach out to our support team at <a href="mailto:info@m-omulimisa.com">info@m-omulimisa.com</a> or call us at 0200 904415.</p>
        <p>We are here to support you as you use DigiSave to manage your financial records more efficiently!</p>
        <a href="{{$data['platformLink']}}" class="button" style="color: #ffffff !important;">Access DigiSave</a>
        <p>Best regards,<br>The DigiSave Team</p>
    </div>
    <div class="footer">
        <p>Plot 709, Kisaasi-Kyanja Road<br>
        <a href="http://www.m-omulimisa.com">www.m-omulimisa.com</a></p>
    </div>
</body>
</html>
