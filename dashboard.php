<?php
/**
 * Archetype System Diagnostic & Dashboard
 * Compact B&W theme for at-a-glance monitoring.
 */
require_once __DIR__ . '/core/Boot.php';
use Archetype\Core\Env;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archetype - System Diagnostic</title>
    <link rel="stylesheet" href="./public/style.css">
    <style>
        body { font-size: 0.9em; }
        .diag-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 10px; }
        section { padding: 10px; }
        h3 { margin-top: 0; margin-bottom: 10px; border-bottom: 2px solid #000; padding-bottom: 5px; text-transform: uppercase; font-size: 0.9em; }
        
        .log-item { display: flex; align-items: center; justify-content: space-between; padding: 4px 0; border-bottom: 1px solid #eee; gap: 10px; }
        .log-label { flex-grow: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .log-details { display: flex; align-items: center; gap: 8px; }
        
        .tag { padding: 1px 6px; border: 1px solid #000; font-size: 0.7em; font-weight: bold; text-transform: uppercase; white-space: nowrap; }
        .ok { background: #000; color: #fff; }
        .err { color: #f00; border-color: #f00; }
        
        .info { color: #666; font-family: 'Consolas', monospace; font-size: 0.85em; }
        .comment-icon { cursor: help; font-style: normal; }
    </style>
</head>
<body>
    <header style="padding: 10px; margin-bottom: 10px;">
        <div style="font-weight: bold; text-transform: uppercase;">Archetype Panel</div>
        <div id="session-info" class="user-info" style="font-size: 0.8em;"></div>
    </header>

    <section style="margin-bottom: 10px;">
        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
            <button class="btn-sm" onclick="location.href='./dashboard/users.html'">Users</button>
            <button class="btn-sm" onclick="location.href='./dashboard/logs.html'">Logs</button>
            <button class="btn-sm" onclick="location.href='./dashboard/mails.html'">Mails</button>
            <button class="btn-sm" onclick="location.href='./dashboard/schemes.html'">Schemes</button>
            <button class="btn-sm" onclick="location.href='./dashboard/entries.html'">Entries</button>
            <button class="btn-sm" onclick="location.href='./dashboard/schemes-tests.html'">Schemes Tests</button>
            <button class="btn-sm" onclick="location.href='./dashboard/uploads.html'">Uploads</button>
            <button class="btn-sm" onclick="location.href='./dashboard/trackers.html'">Trackers</button>
        </div>
    </section>

    <div class="diag-grid">
        <section>
            <h3>Environment</h3>
            <div class="log-item"><span>Protocol</span> <strong><?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'HTTPS' : 'HTTP'; ?></strong></div>
            <div class="log-item"><span>PHP</span> <strong><?php echo PHP_VERSION; ?></strong></div>
            <div class="log-item"><span>DB</span> <strong><?php echo $_ENV['DB_TYPE'] ?? 'N/A'; ?></strong></div>
            <div class="log-item"><span>Host</span> <span class="info"><?php echo ($_ENV['DB_TYPE'] === 'SQLITE') ? basename($_ENV['DB_PATH']) : $_ENV['DB_HOST']; ?></span></div>

            <div style="margin: 10px 0 5px 0; font-weight: bold; font-size: 0.8em; text-transform: uppercase;">File Presence</div>
            <?php
            $components = [
                '.env' => __DIR__ . '/.env',
                'vendor/' => __DIR__ . '/vendor',
                'composer.json' => __DIR__ . '/composer.json',
                'composer.lock' => __DIR__ . '/composer.lock',
            ];
            foreach ($components as $name => $path):
                $exists = file_exists($path);
            ?>
            <div class="log-item">
                <span class="log-label"><?php echo $name; ?></span>
                <span class="tag <?php echo $exists ? 'ok' : 'err'; ?>"><?php echo $exists ? 'YES' : 'NO'; ?></span>
            </div>
            <?php endforeach; ?>

            <div style="margin: 10px 0 5px 0; font-weight: bold; font-size: 0.8em; text-transform: uppercase;">Writability</div>
            <?php
            $paths = ['Logs' => Env::getLogsPath(), 'Uploads' => Env::getUploadsPath(), 'Data' => dirname(Env::getDbPath())];
            if ($_ENV['DB_TYPE'] === 'SQLITE') $paths['DB File'] = Env::getDbPath();

            foreach ($paths as $name => $path):
                $isWritable = is_writable($path);
            ?>
            <div class="log-item">
                <span class="log-label"><?php echo $name; ?></span>
                <span class="tag <?php echo $isWritable ? 'ok' : 'err'; ?>"><?php echo $isWritable ? 'OK' : 'LOCK'; ?></span>
            </div>
            <?php endforeach; ?>
        </section>

        <section>
            <h3>Security</h3>
            <div id="security-check">Loading...</div>
        </section>

        <section>
            <h3>API Access</h3>
            <div id="routes-check">Loading...</div>
        </section>
    </div>

    <script src="./public/auth.js"></script>
    <script>
        const v = (id) => document.getElementById(id);

        function createDiagItem(name, status, isOk, comment) {
            return `
                <div class="log-item">
                    <span class="log-label">${name}</span>
                    <div class="log-details">
                        <i class="comment-icon" title="${comment}">${isOk ? '✓' : '✗'}</i>
                        <span class="tag ${isOk ? 'ok' : 'err'}">${status}</span>
                    </div>
                </div>`;
        }

        async function runSecurityTests() {
            const tests = [
                { name: 'Core', url: './core/Boot.php', expect: 403 },
                { name: 'Data', url: './data/', expect: 403 },
                { name: 'Vendor', url: './vendor/', expect: 403 },
                { name: '.env', url: './.env', expect: 403 }
            ];
            let html = '';
            for (const t of tests) {
                try {
                    const res = await fetch(t.url);
                    const isOk = res.status === t.expect;
                    html += createDiagItem(t.name, res.status, isOk, isOk ? 'Protected' : 'Exposed');
                } catch (e) {
                    html += createDiagItem(t.name, 'FAIL', false, 'Error');
                }
            }
            v('security-check').innerHTML = html;
        }

        async function runRouteTests() {
            const routes = [
                { name: 'Login', url: './api/user-login' },
                { name: 'Public Msg', url: './api/logs-message' },
                { name: 'Admin Boot', url: './api/user-create-admin-user' },
                { name: 'Email Send', url: './api/email-send' },
                { name: 'Logs Range', url: './api/logs-range' },
                { name: 'Logs Get', url: './api/logs-get' },
                { name: 'Schemes', url: './api/schemes-list' }
            ];
            let html = '';
            for (const r of routes) {
                try {
                    const res = await fetch(r.url, { method: 'OPTIONS' });
                    const isOk = res.status < 500 && res.status !== 404;
                    
                    // Add expected failure notice to label for 400 (Bad Request) or 401 (Unauthorized)
                    let label = r.name;
                    if (res.status === 400 || res.status === 401) {
                        label += ' <span style="font-size:0.8em; opacity:0.5;">(Expected Fail)</span>';
                    }
                    
                    html += createDiagItem(label, res.status, isOk, isOk ? 'Working' : 'Rewrite fail');
                } catch (e) {
                    html += createDiagItem(r.name, 'ERR', false, 'Unreachable');
                }
            }
            v('routes-check').innerHTML = html;
        }

        document.addEventListener('DOMContentLoaded', () => {
            runSecurityTests();
            runRouteTests();
        });
    </script>
</body>
</html>