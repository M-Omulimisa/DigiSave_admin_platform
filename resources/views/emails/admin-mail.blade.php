<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digisave VSLA!</title>
    <style>
        /* Reset CSS */
        body, h1, h2, h3, h4, h5, h6, p, ul, ol, li, dl, dt, dd, div, a {
            margin: 0;
            padding: 0;
            border: 0;
        }

        /* Styles for overall layout */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Styles for header */
        .header {
            background-color: #4CAF50;
            color: #fff;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        /* Styles for content */
        .content {
            background-color: #fff;
            padding: 30px;
            border-radius: 5px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .content p {
            margin-bottom: 15px;
            font-size: 16px;
            line-height: 1.6;
            color: #444;
        }
        .content ul {
            list-style: none;
            padding-left: 20px;
            margin-bottom: 20px;
        }
        .content ul li {
            margin-bottom: 10px;
            font-size: 16px;
            line-height: 1.6;
            color: #444;
        }
        .content .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #4CAF50;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }
        .content .btn:hover {
            background-color: #45a049;
        }

        /* Styles for footer */
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Digisave VSLA!</h1>
        </div>
        <div class="content">
            <p>Hello {{$data['first_name']}} {{$data['last_name']}},</p>
            <p>Welcome to Digisave VSLA! You have been registered as an organisation administrator.</p>
            <p>Your login details are:</p>
            <ul>
                <li>Phone Number: {{$data['phone_number']}}</li>
                <li>Password: {{$data['password']}}</li>
            </ul>
            <p>Click below to access the platform:</p>
            <a href="{{$data['platformLink']}}" class="btn">Access the Platform</a>
            <p>Thank you,</p>
            <p>M-Omulimisa Support Team</p>
        </div>
        <div class="footer">
            <p>If you have any questions, please contact us at <a href="mailto:support@m-omulimisa.com">support@m-omulimisa.com</a></p>
        </div>
    </div>
</body>
</html>
