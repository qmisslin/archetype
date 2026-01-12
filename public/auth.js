const auth = {
    save(data) {
        localStorage.setItem('archetype_token', data.token);
        localStorage.setItem('archetype_user', JSON.stringify({ name: data.name, role: data.role }));
        window.location.reload();
    },
    logout() {
        localStorage.clear();
        window.location.reload();
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
    if (session.token) {
        info.innerHTML = `USER: ${session.user.name} [${session.user.role}] | TOKEN: ${session.token.substring(0, 8)}...`;
    } else {
        info.innerHTML = "OFFLINE (No Token)";
    }
}

async function run(url, method, body, outputId) {
    const out = document.getElementById(outputId);
    out.textContent = 'Processing...';
    out.className = '';

    const session = auth.get();
    const headers = { 'Content-Type': 'application/json' };
    if (session.token) headers['Authorization'] = `Bearer ${session.token}`;

    try {
        const res = await fetch(url, {
            method,
            headers,
            body: body ? JSON.stringify(body) : null
        });
        const json = await res.json().catch(() => null);

        out.textContent = JSON.stringify(json || { status: res.status, text: res.statusText }, null, 2);
        if (!res.ok) out.className = 'error-output';

        // Auto-save auth on login success
        if (url.includes('user-login') && res.ok && json.data) {
            auth.save(json.data);
        }
    } catch (e) {
        out.textContent = 'Network Error: ' + e.message;
        out.className = 'error-output';
    }
}

document.addEventListener('DOMContentLoaded', updateUI);