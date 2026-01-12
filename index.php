<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archetype - Dashboard</title>
    <link rel="stylesheet" href="./public/style.css">
</head>
<body>
    <header>
        <div style="font-weight: bold; text-transform: uppercase;">Archetype Control Panel</div>
        <div id="session-info" class="user-info"></div>
    </header>

    <section>
        <h3>System Management</h3>
        <p>Access developer tools and administration dashboards.</p>
        
        <button onclick="location.href='./public/users.html'">Users Dashboard</button>
        <button onclick="location.href='./public/schemes.html'" style="margin-top: 10px;">Schemes Dashboard</button>
        <button onclick="location.href='./public/logs.html'" style="margin-top: 10px;">Logs Dashboard</button>
        <button onclick="location.href='./public/mails.html'" style="margin-top: 10px;">Mails Dashboard</button>
    </section>

    <script src="./public/auth.js"></script>
</body>
</html>