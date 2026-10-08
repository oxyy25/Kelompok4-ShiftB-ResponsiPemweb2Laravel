/**
 * Helper frontend SiPinLab: pemanggil API (fetch), penyimpanan token, toast, error validasi.
 */
const Api = {
    base: '/api',
    TOKEN_KEY: 'sipinlab_token',
    USER_KEY: 'sipinlab_user',

    token() { return localStorage.getItem(this.TOKEN_KEY); },
    user() {
        try { return JSON.parse(localStorage.getItem(this.USER_KEY)); } catch { return null; }
    },
    save(token, user) {
        localStorage.setItem(this.TOKEN_KEY, token);
        localStorage.setItem(this.USER_KEY, JSON.stringify(user));
    },
    clear() {
        localStorage.removeItem(this.TOKEN_KEY);
        localStorage.removeItem(this.USER_KEY);
    },
    isLoggedIn() { return !!this.token(); },

    /**
     * @param {string} method GET|POST|PUT|PATCH|DELETE
     * @param {string} url    path setelah /api, contoh '/auth/login'
     * @param {object|null} body
     * @param {{auth?: boolean}} options auth=false untuk endpoint publik
     */
    async request(method, url, body = null, { auth = true } = {}) {
        const headers = { Accept: 'application/json' };
        if (body) headers['Content-Type'] = 'application/json';
        if (auth && this.token()) headers['Authorization'] = 'Bearer ' + this.token();

        let res;
        try {
            res = await fetch(this.base + url, {
                method, headers, body: body ? JSON.stringify(body) : null,
            });
        } catch (e) {
            throw { status: 0, message: 'Tidak dapat terhubung ke server', errors: {} };
        }

        const json = await res.json().catch(() => ({ success: false, message: 'Respons server tidak valid' }));

        // Token salah/kedaluwarsa pada halaman yang butuh login -> ke halaman login
        if (res.status === 401 && auth) {
            this.clear();
            window.location.href = '/login';
        }

        if (!res.ok) throw { status: res.status, ...json };
        return json;
    },

    get(url, opts) { return this.request('GET', url, null, opts); },
    post(url, body, opts) { return this.request('POST', url, body, opts); },
    put(url, body, opts) { return this.request('PUT', url, body, opts); },
    patch(url, body, opts) { return this.request('PATCH', url, body, opts); },
    delete(url, opts) { return this.request('DELETE', url, null, opts); },
};

/* ---------- Toast ---------- */
function showToast(message, type = 'success') {
    const box = document.getElementById('toastBox');
    if (!box) return alert(message);
    const el = document.createElement('div');
    el.className = `toast align-items-center text-bg-${type === 'error' ? 'danger' : 'success'} border-0`;
    el.setAttribute('role', 'alert');
    el.innerHTML = `<div class="d-flex"><div class="toast-body"></div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Tutup"></button></div>`;
    el.querySelector('.toast-body').textContent = message; // textContent: aman dari XSS
    box.appendChild(el);
    const t = new bootstrap.Toast(el, { delay: 4000 });
    el.addEventListener('hidden.bs.toast', () => el.remove());
    t.show();
}

/* ---------- Error validasi per field ---------- */
function clearFieldErrors(form) {
    form.querySelectorAll('.is-invalid').forEach(i => i.classList.remove('is-invalid'));
    form.querySelectorAll('[data-error-for]').forEach(d => d.textContent = '');
}
function showFieldErrors(form, errors = {}) {
    Object.entries(errors).forEach(([field, messages]) => {
        const input = form.querySelector(`[name="${field}"]`);
        const box = form.querySelector(`[data-error-for="${field}"]`);
        if (input) input.classList.add('is-invalid');
        if (box) box.textContent = messages[0];
    });
}

/* ---------- Loading state tombol ---------- */
function setLoading(button, loading, text = 'Memproses...') {
    if (loading) {
        button.dataset.label = button.innerHTML;
        button.disabled = true;
        button.innerHTML = `<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>${text}`;
    } else {
        button.disabled = false;
        button.innerHTML = button.dataset.label;
    }
}

/* ---------- Navbar sesuai status login ---------- */
function renderNavAuth() {
    const slot = document.getElementById('navAuth');
    if (!slot) return;
    const user = Api.user();
    if (Api.isLoggedIn() && user) {
        slot.innerHTML = `<span class="navbar-text text-white-50 me-3 small"></span>
            <button class="btn btn-sm btn-outline-light" id="btnLogout">Keluar</button>`;
        slot.querySelector('span').textContent = `${user.name} (${user.role_label})`;
        document.getElementById('btnLogout').addEventListener('click', async () => {
            if (!confirm('Yakin ingin keluar?')) return;
            try { await Api.post('/auth/logout'); } catch (e) { /* token mungkin sudah tidak berlaku */ }
            Api.clear();
            window.location.href = '/login';
        });
    } else {
        slot.innerHTML = `<a class="btn btn-sm btn-outline-light me-2" href="/login">Masuk</a>
            <a class="btn btn-sm btn-light" href="/register">Daftar</a>`;
    }
}
document.addEventListener('DOMContentLoaded', renderNavAuth);

// Diekspos ke window karena dimuat sebagai ES module via Vite (@vite),
// sedangkan inline <script> di blade mengaksesnya sebagai global.
window.Api = Api;
window.showToast = showToast;
window.clearFieldErrors = clearFieldErrors;
window.showFieldErrors = showFieldErrors;
window.setLoading = setLoading;
window.renderNavAuth = renderNavAuth;