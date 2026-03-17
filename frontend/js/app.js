const API_BASE = '/Tour_Guide_System/backend';

// Helper: fetch with credentials (sends PHP session cookies)
async function apiFetch(url, options = {}) {
    return fetch(url, { credentials: 'include', ...options });
}

document.addEventListener('DOMContentLoaded', () => {
    checkSession();
    if (document.getElementById('tour-list')) { loadTours(); }
    if (document.getElementById('search-btn')) {
        document.getElementById('search-btn').addEventListener('click', () => {
            const query = document.getElementById('search-input').value;
            loadTours(query);
        });
        document.getElementById('search-input').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                const query = document.getElementById('search-input').value;
                loadTours(query);
            }
        });
    }
    if (document.getElementById('login-form')) { document.getElementById('login-form').addEventListener('submit', handleLogin); }
    if (document.getElementById('register-form')) { document.getElementById('register-form').addEventListener('submit', handleRegister); }
    if (document.getElementById('logout-btn')) { document.getElementById('logout-btn').addEventListener('click', handleLogout); }
});

async function checkSession() {
    try {
        const response = await apiFetch(`${API_BASE}/auth.php?action=check_session`);
        const data = await response.json();
        if (data.loggedIn) {
            updateNavbar(data.user);
            localStorage.setItem('user', JSON.stringify(data.user));
        } else {
            localStorage.removeItem('user');
            updateNavbar(null);
        }
    } catch (error) {
        console.error('Session check failed', error);
        const user = JSON.parse(localStorage.getItem('user') || 'null');
        updateNavbar(user);
    }
}

function updateNavbar(user) {
    const loginLink = document.getElementById('nav-login');
    const registerLink = document.getElementById('nav-register');
    const dashboardLink = document.getElementById('nav-dashboard');
    const logoutLink = document.getElementById('nav-logout');
    const dashboardAnchor = document.getElementById('dashboard-link');
    if (user) {
        if (loginLink) loginLink.style.display = 'none';
        if (registerLink) registerLink.style.display = 'none';
        if (dashboardLink) dashboardLink.style.display = 'block';
        if (logoutLink) logoutLink.style.display = 'block';
        if (dashboardAnchor) {
            if (user.role === 'admin') dashboardAnchor.href = 'dashboard_admin.html';
            else if (user.role === 'guide') dashboardAnchor.href = 'dashboard_guide.html';
            else dashboardAnchor.href = 'dashboard_tourist.html';
        }
    } else {
        if (loginLink) loginLink.style.display = 'block';
        if (registerLink) registerLink.style.display = 'block';
        if (dashboardLink) dashboardLink.style.display = 'none';
        if (logoutLink) logoutLink.style.display = 'none';
    }
}

async function loadTours(query = '') {
    try {
        let url = `${API_BASE}/tours.php`;
        if (query) url += `?search=${encodeURIComponent(query)}`;
        const response = await apiFetch(url);
        const tours = await response.json();
        const container = document.getElementById('tour-list');
        if (!container) return;
        container.innerHTML = '';
        if (!Array.isArray(tours) || tours.length === 0) {
            container.innerHTML = '<div style="text-align:center;padding:60px 20px;color:#718096;grid-column:1/-1"><div style="font-size:3rem;margin-bottom:12px;">🗺️</div><p>No tours available yet. Check back soon!</p></div>';
            return;
        }
        tours.forEach(tour => {
            const card = document.createElement('div');
            card.className = 'tour-card';
            const img = tour.image ? tour.image : 'https://images.unsplash.com/photo-1518509562904-e7ef99cdcc86?w=400&q=60';
            card.innerHTML = `
                <img src="${img}" alt="${tour.title}" onerror="this.src='https://images.unsplash.com/photo-1518509562904-e7ef99cdcc86?w=400&q=60'">
                <div class="tour-info">
                    <h3>${tour.title}</h3>
                    <p>📍 ${tour.location} &nbsp;|&nbsp; ⏱ ${tour.duration} hrs</p>
                    <p class="price">₱${parseFloat(tour.price).toLocaleString()}</p>
                    <a href="tour_details.html?id=${tour.id}" class="btn">View Details</a>
                </div>
            `;
            container.appendChild(card);
        });
    } catch (error) {
        console.error('Failed to load tours', error);
    }
}

async function handleLogin(e) {
    e.preventDefault();
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    try {
        const response = await apiFetch(`${API_BASE}/auth.php?action=login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });
        const data = await response.json();
        if (response.ok) {
            localStorage.setItem('user', JSON.stringify(data.user));
            if (data.user.role === 'admin') window.location.href = 'dashboard_admin.html';
            else if (data.user.role === 'guide') window.location.href = 'dashboard_guide.html';
            else window.location.href = 'index.html';
        } else { alert(data.message); }
    } catch (error) {
        console.error('Login error', error);
        alert('An error occurred during login.');
    }
}

async function handleRegister(e) {
    e.preventDefault();
    const username = document.getElementById('username').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const role = document.getElementById('role').value;
    try {
        const response = await apiFetch(`${API_BASE}/auth.php?action=register`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, email, password, role })
        });
        const data = await response.json();
        if (response.ok) {
            alert('Registration successful! Please login.');
            window.location.href = 'login.html';
        } else { alert(data.message); }
    } catch (error) {
        console.error('Registration error', error);
        alert('An error occurred during registration.');
    }
}

async function handleLogout(e) {
    if (e) e.preventDefault();
    try {
        await apiFetch(`${API_BASE}/auth.php?action=logout`, { method: 'POST' });
    } catch (error) {
        console.error('Logout error', error);
    } finally {
        localStorage.removeItem('user');
        window.location.href = 'login.html';
    }
}
