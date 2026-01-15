/**
 * TestRunner
 * Creates temporary schemes, runs validation test cases, and optionally cleans up.
 */
class TestRunner {
    constructor(config) {
        this.ui = {
            results: document.getElementById(config.resultsId),
            btn: document.getElementById(config.btnId),
            cleanup: document.getElementById(config.cleanupId)
        };
        this.uid = Math.floor(Math.random() * 9999);
        this.state = {
            helperId: null,
            forbiddenId: null,
            masterId: null,
            helperEntryId: null,
            forbiddenEntryId: null
        };
    }

    /**
     * Runs the full suite: session check, setup, tests, then optional cleanup.
     * Always re-enables the Run button at the end.
     */
    async start() {
        this.ui.btn.disabled = true;
        this.ui.results.innerHTML = '';

        try {
            if (!this.verifySession()) return;

            await this.setupEnvironment();
            await this.runTests();

            if (this.ui.cleanup.checked) {
                await this.cleanup();
            } else {
                this.log("Skipping Cleanup (Data Preserved)", 'step');
            }
        } catch (err) {
            console.error("Test Suite Error:", err);
            this.log(`CRITICAL ERROR: ${err.message}`, 'fail');
        } finally {
            this.ui.btn.disabled = false;
        }
    }

    /**
     * Ensures the user is authenticated and has ADMIN role.
     * @returns {boolean}
     */
    verifySession() {
        const session = auth.get();
        if (!session.token) {
            this.log("Authentication missing. Please log in.", 'fail');
            return false;
        }
        if (session.user.role !== 'ADMIN') {
            this.log(`Admin privileges required. Current role: ${session.user.role}`, 'fail');
            return false;
        }
        return true;
    }

    /**
     * Creates Helper/Forbidden/Master schemes and seeds reference entries.
     * Builds the Master scheme fields used by the test cases.
     */
    async setupEnvironment() {
        this.log("Setting up Environment...", 'header');

        // Helper Scheme & Entry
        this.state.helperId = await this.createScheme(`Helper_${this.uid}`);
        await this.addField(this.state.helperId, { key: "val", type: "STRING", required: true, "is-array": false });
        this.state.helperEntryId = await this.createEntry(this.state.helperId, { val: "Target" });

        // Forbidden Scheme & Entry
        this.state.forbiddenId = await this.createScheme(`Forbidden_${this.uid}`);
        await this.addField(this.state.forbiddenId, { key: "val", type: "STRING", required: true, "is-array": false });
        this.state.forbiddenEntryId = await this.createEntry(this.state.forbiddenId, { val: "Spy" });

        // Master Scheme (The Megazord)
        this.state.masterId = await this.createScheme(`Megazord_${this.uid}`);

        // Define all field types for the Master scheme
        const fields = [
            { key: "s_len", type: "STRING", required: true, rules: { "min-char": 2, "max-char": 5 } },
            { key: "s_enum", type: "STRING", required: true, rules: { "enum": ["A", "B"] } },
            { key: "s_reg", type: "STRING", required: true, rules: { "pattern": "^\\d{3}$" } },
            { key: "s_hex", type: "STRING", required: true, rules: { "format": "hex-color" } },
            { key: "s_json", type: "STRING", required: true, rules: { "format": "json" } },
            { key: "s_html", type: "STRING", required: true, rules: { "format": "html" } },
            { key: "n_int", type: "NUMBER", required: true, rules: { "format": "int", "min-value": 10, "max-value": 20 } },
            { key: "n_float", type: "NUMBER", required: true, rules: { "step": 0.5 } },
            { key: "b_bool", type: "BOOLEAN", required: true, rules: {} },
            { key: "ref", type: "ENTRIES", required: true, rules: { "schemes": [this.state.helperId] } },
            { key: "arr_str", type: "STRING", required: true, "is-array": true, rules: { "min-length": 2, "max-length": 3 } },
            { key: "opt", type: "STRING", required: false, rules: {} }
        ];

        for (const f of fields) {
            // Fill defaults expected by the backend scheme format
            const fullField = { ...f, label: f.key, "is-array": f["is-array"] || false, access: ["ADMIN"] };
            await this.addField(this.state.masterId, fullField);
        }

        this.log("Schemes Ready. Starting Tests...", 'step');
    }

    /**
     * Executes all test cases (from tests-data.js) by creating entries on the Master scheme.
     * Optionally deletes each successful entry immediately if cleanup is enabled.
     */
    async runTests() {
        const testCases = getTestCases(this.state.helperEntryId, this.state.forbiddenEntryId);
        let passed = 0;

        for (let i = 0; i < testCases.length; i++) {
            const test = testCases[i];
            const payload = JSON.parse(JSON.stringify(test.d));

            // Light rate limiting to avoid flooding the API/UI
            if (i % 5 === 0) await this.sleep(5);

            const res = await this.api('/entries-create', 'POST', {
                schemeId: this.state.masterId,
                data: payload
            });

            const success = res.status === 'success';

            if (success === test.exp) {
                passed++;
                this.log(test.l, 'pass');
            } else {
                let msg = res.message || 'Unknown error';
                if (success) msg = `Unexpected SUCCESS (ID: ${res.data?.id})`;

                console.groupCollapsed(`FAIL: ${test.l}`);
                console.log("Payload:", payload);
                console.log("Response:", res);
                console.groupEnd();

                this.log(`${test.l} | ${msg}`, 'fail');
            }

            // Prevent DB pollution when running with cleanup enabled
            if (this.ui.cleanup.checked && success && res.data?.id) {
                await this.api('/entries-remove', 'DELETE', { entryId: res.data.id });
            }
        }

        const score = Math.round((passed / testCases.length) * 100);
        this.log(`FINAL SCORE: ${passed}/${testCases.length} (${score}%)`, score === 100 ? 'pass' : 'fail');
    }

    /**
     * Removes the temporary schemes created by setupEnvironment().
     * Safe to call even if some IDs are missing.
     */
    async cleanup() {
        this.log("Cleaning up Schemes...", 'header');

        if (this.state.masterId) {
            await this.api('/schemes-remove', 'DELETE', { schemeID: this.state.masterId });
            this.log(`Removed Master Scheme (ID: ${this.state.masterId})`, 'step');
        }

        if (this.state.helperId) {
            await this.api('/schemes-remove', 'DELETE', { schemeID: this.state.helperId });
            this.log(`Removed Helper Scheme (ID: ${this.state.helperId})`, 'step');
        }

        if (this.state.forbiddenId) {
            await this.api('/schemes-remove', 'DELETE', { schemeID: this.state.forbiddenId });
            this.log(`Removed Forbidden Scheme (ID: ${this.state.forbiddenId})`, 'step');
        }
    }

    // --- API & Utils ---

    /**
     * Creates a scheme and returns its ID. Throws on failure.
     * @param {string} name
     * @returns {Promise<number>}
     */
    async createScheme(name) {
        const res = await this.api('/schemes-create', 'POST', { name });
        if (res.status !== 'success') throw new Error(`Failed to create scheme ${name}: ${res.message}`);
        return res.data.id;
    }

    /**
     * Adds a field to a given scheme. The backend is expected to validate the payload.
     * @param {number} schemeID
     * @param {Object} field
     */
    async addField(schemeID, field) {
        await this.api('/schemes-add-field', 'POST', { schemeID, field });
    }

    /**
     * Creates an entry for a given scheme and returns its entry ID. Throws on failure.
     * @param {number} schemeId
     * @param {Object} data
     * @returns {Promise<number>}
     */
    async createEntry(schemeId, data) {
        const res = await this.api('/entries-create', 'POST', { schemeId, data });
        if (res.status !== 'success') throw new Error(`Failed to create entry: ${res.message}`);
        return res.data.id;
    }

    /**
     * Wrapper around fetch() that always returns a structured object:
     * { status: 'success'|'error', message?: string, data?: any }.
     */
    async api(endpoint, method, body = null) {
        const session = auth.get();
        try {
            const res = await fetch('../api' + endpoint, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${session.token}`
                },
                body: body ? JSON.stringify(body) : null
            });

            if (res.status === 401) {
                return { status: 'error', message: 'Unauthorized (401). Session expired or invalid.' };
            }

            const text = await res.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                return { status: 'error', message: `Invalid JSON response from ${endpoint}. Response : ${text}` };
            }
        } catch (e) {
            return { status: 'error', message: 'Network Error' };
        }
    }

    /**
     * Appends a single log row to the UI.
     * @param {string} message
     * @param {string} type
     */
    log(message, type) {
        const div = document.createElement('div');
        div.className = `test-row test-${type}`;
        div.textContent = message;
        this.ui.results.appendChild(div);
        window.scrollTo(0, document.body.scrollHeight);
    }

    /**
     * Small async delay used for rate limiting.
     * @param {number} ms
     * @returns {Promise<void>}
     */
    sleep(ms) {
        return new Promise(r => setTimeout(r, ms));
    }
}
