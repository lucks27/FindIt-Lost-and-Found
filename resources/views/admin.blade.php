<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SNSU Admin - Lost & Found Management</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/logo.png') }}">
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body>
    <div id="admin-app"></div>
</body>
</html>
