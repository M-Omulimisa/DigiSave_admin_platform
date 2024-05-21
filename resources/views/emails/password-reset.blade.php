<!-- resources/views/emails/password-reset.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Password Reset</title>
    <style>
        /* Reset CSS */
        body, h1, h2, h3, h4, h5, h6, p, ul, ol, li, dl, dt, dd, div, a, button {
            margin: 0;
            padding: 0;
            border: 0;
        }

        /* Styles for overall layout */
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 16px;
            line-height: 1.6;
            background: #f9f9f9;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            max-width: 100px;
            height: auto;
        }

        .content {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .content h2 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }

        .content p {
            margin-bottom: 20px;
            color: #666;
            text-align: justify;
        }

        .content ul {
            margin-bottom: 20px;
            color: #666;
            text-align: left;
            padding-left: 20px;
        }

        .content ul li {
            list-style: none;
            margin-bottom: 10px;
        }

        .content a {
            color: #0DFF00;
            text-decoration: none;
        }

        .content a:hover {
            text-decoration: underline;
        }

        .footer {
            text-align: center;
            color: #666;
            font-size: 14px;
        }

        /* Button Styles */
        .button {
            display: inline-block;
            background-color: #0DC603;
            border: none;
            border-radius: 3px;
            color: #ffffff;
            cursor: pointer;
            font-size: 15px;
            font-weight: bold;
            padding: 15px 25px;
            text-align: center;
            text-decoration: none;
            text-transform: none;
            transition: background-color 0.3s ease;
        }

        .button:hover {
            background-color: #0ac600;
        }

        .btn.btn-primary {
            background-color: darkgreen;
            color: white;
            text-decoration: none; /* Remove underline */
        }

        .btn.btn-primary:hover {
            text-decoration: none; /* Remove underline on hover */
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content">
            <div class="header">
                <img class="logo" src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQzovy9F3InvItaWs_LOY1xux47vk6FPs8sSEs6yq772A&s" alt="Logo">
            </div>
            <h2>DigiSave VSLA</h2>
            <p>Hello {{$data['first_name']}} {{$data['last_name']}}!</p>
            <p>Your password has been reset successfully. You can now log in to our platform using the following credentials:</p>
            <ul>
                <li><strong>Username:</strong> {{$data['phone_number']}}</li>
                <li><strong>New Password:</strong> {{$data['password']}}</li>
            </ul>
            <p>Click the button below to access the platform:</p>
            <a type="button" href="{{$data['platformLink']}}" class="btn btn-primary">Login to Your Account</a>
            <div style="font-family:'Helvetica Neue',Arial,sans-serif;font-size:14px;line-height:20px;text-align:left;color:#525252;">
                Best regards,<br>DigiSave VSLA Support Team<br>
            </div>
            <p><i>This is an automated email. Please do not reply to this email.</i></p>
        </div>
        <div class="footer">
            <p>If you have any questions, please do not hesitate to contact us through direct message in the App or email to <a href="mailto:{{$data['email']}}">{{$data['email']}}</a>.</p>
            <p>© DigiSave VSLA</p>
        </div>
    </div>
</body>
</html>
