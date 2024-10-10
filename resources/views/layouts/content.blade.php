<!-- resources/views/layouts/content.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Default Title')</title>
    <!-- Include your CSS files here -->
</head>
<body>
    <header>
        <!-- Your header content -->
    </header>

    <main>
        @yield('content')
    </main>

    <footer>
        <!-- Your footer content -->
    </footer>

    <!-- Include your JS files here -->
</body>
</html>
