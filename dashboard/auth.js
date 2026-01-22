/**
 * Archetype Authentication & API Runner
 * Handles session storage and robust API response parsing.
 */
const auth = {
    save(data) {
        // Data comes from APIHelper::success payload [cite: 9, 296]
        localStorage.setItem('archetype_token', data.token);
        localStorage.setItem('archetype_user', JSON.stringify({ name: data.name, role: data.role }));
        updateUI();
    },
    logout() {
        localStorage.clear();
        updateUI();
    },
    get() {
        return {
            token: localStorage.getItem('archetype_token'),
            user: JSON.parse(localStorage.getItem('archetype_user') || '{}')
        };
    }
};

function updateUI() {
    const session = auth.get();
    const info = document.getElementById('session-info');
    if (info) {
        if (session.token) {
            info.innerHTML = `USER: ${session.user.name} [${session.user.role}] | TOKEN: ${session.token.substring(0, 8)}...`;
        } else {
            info.innerHTML = "OFFLINE (No Token)";
        }
    }
}

/**
 * Executes an API request and handles both JSON and Raw Text responses.
 */
async function run(url, method, body, outputId) {
    const out = document.getElementById(outputId);
    out.textContent = 'Processing...';
    out.className = '';

    const session = auth.get();
    const headers = { 'Content-Type': 'application/json' };
    if (session.token) headers['Authorization'] = `Bearer ${session.token}`; // [cite: 43, 51]

    try {
        const res = await fetch(url, {
            method,
            headers,
            body: body ? JSON.stringify(body) : null
        });

        // Get raw text first to prevent JSON parse crashes 
        const rawText = await res.text();
        let json = null;

        try {
            json = JSON.parse(rawText);
        } catch (e) {
            json = null; // Response is not valid JSON (e.g., PHP Fatal Error HTML)
        }

        // Display JSON if available, otherwise show raw server output
        if (json) {
            out.textContent = JSON.stringify(json, null, 2);
        } else {
            out.textContent = `[NON-JSON RESPONSE] Status ${res.status}:\n${rawText}`;
            out.className = 'error-output';
        }

        // Highlight HTTP errors [cite: 14]
        if (!res.ok) {
            out.className = 'error-output';
        }

        // Handle successful Login logic [cite: 296, 382]
        if (url.includes('user-login') && res.ok && json && json.status === 'success') {
            auth.save(json.data);
        }
    } catch (e) {
        out.textContent = 'Network/Client Error: ' + e.message;
        out.className = 'error-output';
    }
}

document.addEventListener('DOMContentLoaded', updateUI);