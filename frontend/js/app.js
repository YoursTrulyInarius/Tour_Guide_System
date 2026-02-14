const API_BASE = 'http://localhost/Tour_Guide_System/backend';

document.addEventListener('DOMContentLoaded', () => {
    checkSession();
    
    // Page specific logic
    if (document.getElementById('tour-list')) {
        loadTours();
    }
    
    if (document.getElementById('search-btn')) {
        document.getElementById('search-btn').addEventListener('click', () => {
            const query = document.getElementById('search-input').value;
            loadTours(query);
        });
    }

    if (document.getElementById('login-form')) {
        document.getElementById('login-form').addEventListener('submit', handleLogin);
    }
    
    if (document.getElementById('register-form')) {
        document.getElementById('register-form').addEventListener('submit', handleRegister);
    }

    if (document.getElementById('logout-btn')) {
        document.getElementById('logout-btn').addEventListener('click', handleLogout);
    }
});

async function checkSession() {
    try {
        const response = await fetch(`${API_BASE}/auth.php?action=check_session`);
        const data = await response.json();
        
        if (data.loggedIn) {
            updateNavbar(data.user);
            // Store user info
            localStorage.setItem('user', JSON.stringify(data.user));
        } else {
            localStorage.removeItem('user');
            updateNavbar(null);
        }
    } catch (error) {
        console.error('Session check failed', error);
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
        
        // redirect dashboard link based on role
        if (user.role === 'admin') dashboardAnchor.href = 'dashboard_admin.html';
        else if (user.role === 'guide') dashboardAnchor.href = 'dashboard_guide.html';
        else dashboardAnchor.href = 'dashboard_tourist.html';
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
        
        const response = await fetch(url);
        const tours = await response.json();
        
        const container = document.getElementById('tour-list');
        container.innerHTML = '';
        
        if (tours.length === 0) {
            container.innerHTML = '<p>No tours found.</p>';
            return;
        }
        
        tours.forEach(tour => {
            const card = document.createElement('div');
            card.className = 'tour-card';
            const img = tour.image ? tour.image : 'https://via.placeholder.com/300x200';
            card.innerHTML = `
                <img src="${img}" alt="${tour.title}">
                <div class="tour-info">
                    <h3>${tour.title}</h3>
                    <p>${tour.location} | ${tour.duration} hours</p>
                    <p class="price">$${tour.price}</p>
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
        const response = await fetch(`${API_BASE}/auth.php?action=login`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ email, password })
        });
        
        const data = await response.json();
        if (response.ok) {
            // Redirect based on role
            if (data.user.role === 'admin') window.location.href = 'dashboard_admin.html';
            else if (data.user.role === 'guide') window.location.href = 'dashboard_guide.html';
            else window.location.href = 'index.html';
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Login error', error);
        alert('An error occurred during login');
    }
}

async function handleRegister(e) {
    e.preventDefault();
    const username = document.getElementById('username').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const role = document.getElementById('role').value;
    
    try {
        const response = await fetch(`${API_BASE}/auth.php?action=register`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ username, email, password, role })
        });
        
        const data = await response.json();
        if (response.ok) {
            alert('Registration successful! Please login.');
            window.location.href = 'login.html';
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error('Registration error', error);
        alert('An error occurred during registration');
    }
}

async function handleLogout(e) {
    e.preventDefault();
    try {
        await fetch(`${API_BASE}/auth.php?action=logout`, { method: 'POST' });
        window.location.href = 'login.html';
    } catch (error) {
        console.error('Logout error', error);
    }
}
