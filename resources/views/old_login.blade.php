<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ config('admin.title') }} | {{ trans('admin.login') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    @if (!is_null($favicon = Admin::favicon()))
        <link rel="shortcut icon" href="{{ $favicon }}">
    @endif

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        body {
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .background-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
            filter: blur(5px);
        }

        .container {
            display: flex;
            width: 100%;
            max-width: 1200px;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            z-index: 1;
        }

        .login-box, .info-box {
            flex: 1;
            padding: 50px;
        }

        .login-box {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-box-body {
            max-width: 900px;
            margin: 0 auto;
        }

        .text-center {
            margin-bottom: 50px;
        }

        .login-logo {
            text-align: center;
            margin-bottom: 20px;
        }

        .form-control {
            border-radius: 10px;
            padding: 20px;
            margin-top: 10px;
            margin-bottom: 10px;
            font-size: 16px;
            font-weight: 600;
        }

        .btn-primary {
            border-radius: 10px;
            background-color: #0a5a04;
            border-color: #0a5a04;
            font-size: 18px;
            padding: 15px;
            width: 100%;
            font-weight: bold;
            transition: background-color 0.3s, border-color 0.3s;
        }

        .btn-primary:hover {
            background-color: #0ada29;
            border-color: #43d007;
        }

        .form-check-input {
            border-radius: 50%;
        }

        .forgot-password {
            text-align: right;
            margin-bottom: 20px;
            display: block;
            cursor: pointer;
        }

        .info-box {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: rgba(1, 107, 68, 0.9);
            color: #fff;
            text-align: center;
        }

        .info-box img {
            max-width: 150px;
            margin-bottom: 20px;
        }

        .info-box h2 {
            font-size: 36px;
            margin-bottom: 20px;
        }

        .info-box p {
            font-size: 18px;
            max-width: 400px;
        }

        .input-group {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }

        #loadingAnimation {
            display: none; /* Hidden by default */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8); /* semi-transparent background */
            z-index: 1000; /* Ensure it appears above other content */
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .loader {
            width: 40px;
            aspect-ratio: 1;
            position: relative;
        }

        .loader:before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
            --c: #0000, #f03355 1deg 120deg, #0000 121deg;
            background:
            conic-gradient(from 0deg, var(--c)) top right,
            conic-gradient(from 120deg, var(--c)) bottom,
            conic-gradient(from 240deg, var(--c)) top left;
            background-size: 40px 40px;
            background-repeat: no-repeat;
            animation: l25 2s infinite cubic-bezier(0.3, 1, 0, 1);
        }

        @keyframes l25 {
            33% { inset: -8px; transform: rotate(0deg); }
            66% { inset: -8px; transform: rotate(180deg); }
            100% { inset: 0; transform: rotate(180deg); }
        }
    </style>
</head>

<body>
    <img src="https://ucca-uganda.org/wp-content/uploads/2022/05/1y7-J5Fgr_2_tyFOXDZegOA.jpeg" alt="Background" class="background-image">
    <div class="container">
        <div class="login-box">
            <div class="login-box-body">
                <h2 class="text-center">Login to dashboard</h2>
                <form id="loginForm" action="{{ admin_url('auth/login') }}" method="post">
                    <div class="mb-3">
                        @if ($errors->has('username'))
                            @foreach ($errors->get('username') as $message)
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-times-circle"></i> {{ $message }}
                                </div>
                            @endforeach
                        @endif

                        <input type="text" class="form-control" placeholder="Type your email"
                            name="username" value="{{ old('username') }}">
                    </div>
                    <div class="mb-3 input-group">
                        @if ($errors->has('password'))
                            @foreach ($errors->get('password') as $message)
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-times-circle"></i> {{ $message }}
                                </div>
                            @endforeach
                        @endif

                        <input type="password" class="form-control" placeholder="Type your password"
                            name="password" id="password">
                        <span class="toggle-password"><i class="fas fa-eye" id="togglePassword"></i></span>
                    </div>
                    <div class="mb-3 d-flex justify-content-between align-items-center">
                        @if (config('admin.auth.remember'))
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" value="1"
                                    {{ !old('username') || old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label">
                                    Remember me
                                </label>
                            </div>
                        @endif
                    </div>
                    <div class="mb-3">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
                <div class="text-center">
                    <p>Forgot password? <a href="#" class="forgot-password" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">Reset Password!</a></p>
                </div>
            </div>
        </div>
        <div class="info-box">
            <img src="https://iirr.org/wp-content/uploads/2021/09/IIRR-PING-logo-1-2.png" alt="Logo">
            <h2>DigiSave VSLA Platform</h2>
            <p>Join our community today</p>
        </div>
    </div>

    <!-- Custom Loader Animation -->
    <div id="loadingAnimation">
        <div class="loader"></div>
    </div>

    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="resetPasswordForm">
                        <div class="mb-3">
                            <label for="emailOrPhone" class="form-label">Enter your email or phone number</label>
                            <input type="text" class="form-control" id="emailOrPhone" name="identifier" placeholder="Email or Phone">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Submit</button>
                    </form>
                </div>
                <div class="modal-footer d-none" id="resetPasswordSuccess">
                    <p>Password reset instructions have been sent to your email or phone number.</p>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        $(document).ready(function() {
            function showLoader() {
                $('#loadingAnimation').show();
            }

            function hideLoader() {
                $('#loadingAnimation').hide();
            }

            // Ensure the loader is hidden when the document is ready
            hideLoader();

            $('#togglePassword').on('click', function() {
                var passwordInput = $('#password');
                var type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
                passwordInput.attr('type', type);
                $(this).toggleClass('fa-eye fa-eye-slash');
            });

            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                showLoader();

                var form = $(this);
                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        hideLoader();
                        toastr.success(response.message || 'Login successful!');
                        window.location.href = '/'; // Redirect to the home page
                    },
                    error: function(xhr) {
                        hideLoader();
                        toastr.error(xhr.responseJSON.message || 'Login failed. Please try again.');
                    }
                });
            });

            $('#resetPasswordForm').on('submit', function(e) {
                e.preventDefault();
                showLoader();

                var identifier = $('#emailOrPhone').val();
                $.ajax({
                    url: '/api/reset-password',
                    type: 'POST',
                    data: {
                        identifier: identifier,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        hideLoader();
                        $('#resetPasswordForm').addClass('d-none');
                        $('#resetPasswordSuccess').removeClass('d-none');
                        toastr.success(response.message || 'Password reset instructions have been sent!');
                    },
                    error: function(xhr) {
                        hideLoader();
                        toastr.error(xhr.responseJSON.message || 'Failed to reset password. Please try again.');
                    }
                });
            });
        });
    </script>
</body>

</html>
