<?php
/**
 * Archetype System Diagnostic & Dashboard
 * Standardized B&W theme with explicit security, routing, and filesystem feedback.
 */
require_once __DIR__ . '/core/Boot.php';

use Archetype\Core\Env;

// Force HTML header to override the API JSON header from Boot.php
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
        .diag-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; }
        .log-item { display: flex; flex-direction: column; padding: 10px 0; border-bottom: 1px solid #eee; }
        .log-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
        .log-comment { font-size: 0.8em; color: #666; font-style: italic; }
        .tag { padding: 2px 8px; border: 1px solid #000; font-size: 0.75em; font-weight: bold; text-transform: uppercase; }
        .ok { background: #000; color: #fff; }
        .err { color: #f00; border-color: #f00; }
        .info { color: #666; font-family: 'Consolas', monospace; font-size: 0.9em; }
    </style>
</head>
<body>
    <header>
        <div style="font-weight: bold; text-transform: uppercase;">Archetype Control Panel</div>
        <div id="session-info" class="user-info"></div>
    </header>

    <section>
        <h3>Main Navigation</h3>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button onclick="location.href='./public/users.html'">Users Dashboard</button>
            <button onclick="location.href='./public/schemes.html'">Schemes Dashboard</button>
            <button onclick="location.href='./public/logs.html'">Logs Dashboard</button>
            <button onclick="location.href='./public/mails.html'">Mails Dashboard</button>
        </div>
    </section>

    <div class="diag-grid">
        <section>
            <h3>Server & Filesystem</h3>
            
            <div class="log-item">
                <div class="log-header"><span>Protocol</span> <strong><?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'HTTPS' : 'HTTP'; ?></strong></div>
            </div>
            <div class="log-item">
                <div class="log-header"><span>PHP Version</span> <strong><?php echo PHP_VERSION; ?></strong></div>
            </div>
            <div class="log-item">
                <div class="log-header"><span>DB Engine</span> <strong><?php echo $_ENV['DB_TYPE'] ?? 'NOT SET'; ?></strong></div>
            </div>
            
            <?php if(($_ENV['DB_TYPE'] ?? '') === 'SQLITE'): ?>
                <div class="log-item">
                    <div class="log-header"><span>Database File</span> <span class="info"><?php echo basename($_ENV['DB_PATH'] ?? 'database.sql'); ?></span></div>
                </div>
            <?php else: ?>
                <div class="log-item">
                    <div class="log-header"><span>Host</span> <strong><?php echo $_ENV['DB_HOST'] ?? 'localhost'; ?></strong></div>
                </div>
            <?php endif; ?>
            <hr style="border: 0; margin: 3px 0;">
            <?php
            $paths = [
                'Logs Directory' => Env::getLogsPath(),
                'Uploads Directory' => Env::getUploadsPath(),
                'Data Root' => dirname(Env::getDbPath())
            ];

            if (($_ENV['DB_TYPE'] ?? '') === 'SQLITE') {
                $paths['Database File Access'] = Env::getDbPath();
            }

            foreach ($paths as $name => $path):
                $isWritable = is_writable($path);
            ?>
            <div class="log-item">
                <div class="log-header">
                    <span><?php echo $name; ?></span>
                    <span class="tag <?php echo $isWritable ? 'ok' : 'err'; ?>">
                        <?php echo $isWritable ? 'Writable' : 'Locked'; ?>
                    </span>
                </div>
                <div class="log-comment">
                    <?php echo $isWritable ? '✓ Permissions are correct' : '✗ Permission denied at ' . $path; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </section>

        <section>
            <h3>Security Diagnostics</h3>
            <div id="security-check">Testing folder protections...</div>
        </section>

        <section>
            <h3>API Availability (Rewrites)</h3>
            <div id="routes-check">Testing .htaccess rewrite rules...</div>
        </section>
    </div>

    <script src="./public/auth.js"></script>
    <script>
        const v = (id) => document.getElementById(id);

        function createDiagItem(name, status, isOk, comment) {
            return `
                <div class="log-item">
                    <div class="log-header">
                        <span>${name}</span>
                        <span class="tag ${isOk ? 'ok' : 'err'}">${status}</span>
                    </div>
                    <div class="log-comment">${isOk ? '✓' : '✗'} ${comment}</div>
                </div>`;
        }

        async function runSecurityTests() {
            const tests = [
                { name: 'Core Protection', url: './core/Boot.php', expect: 403 },
                { name: 'Data Protection', url: './data/', expect: 403 },
                { name: 'Vendor Protection', url: './vendor/', expect: 403 },
                { name: 'Env Protection', url: './.env', expect: 403 }
            ];
            
            let html = '';
            for (const t of tests) {
                try {
                    const res = await fetch(t.url);
                    const isOk = res.status === t.expect;
                    const msg = isOk ? 'Correctly hidden from public' : (res.status === 200 ? 'VULNERABLE: File is exposed' : 'Unexpected status');
                    html += createDiagItem(t.name, res.status, isOk, msg);
                } catch (e) {
                    html += createDiagItem(t.name, 'FAIL', false, 'Network error or blocked');
                }
            }
            v('security-check').innerHTML = html;
        }

        async function runRouteTests() {
            const routes = [
                { name: 'User Login', url: './api/user-login' },
                { name: 'Logs Range', url: './api/logs-range' },
                { name: 'Schemes List', url: './api/schemes-list' }
            ];

            let html = '';
            for (const r of routes) {
                try {
                    const res = await fetch(r.url, { method: 'OPTIONS' });
                    const isOk = res.status < 500 && res.status !== 404; 
                    let comment = isOk ? 'Rewrite rules functional' : 'Check .htaccess configuration';
                    if (res.status === 404) comment = 'FAILED: .php extension required';
                    if (res.status >= 500) comment = 'CRITICAL: PHP Script crashed';

                    html += createDiagItem(r.name, res.status, isOk, comment);
                } catch (e) {
                    html += createDiagItem(r.name, 'ERR', false, 'Cannot reach endpoint');
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