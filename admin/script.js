// Sidebar active state + toggle
document.addEventListener('DOMContentLoaded', function () {

    // ── Active link ──
    const currentPage = window.location.pathname.split('/').pop() || 'dashboard.php';
    document.querySelectorAll('.sidebar-menu a').forEach(link => {
        link.classList.toggle('active', link.getAttribute('href') === currentPage);
    });

    // ── Sidebar toggle (hamburger) ──
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('overlay');
    const toggle = document.getElementById('sidebarToggle');

    function openSidebar() {
        sidebar.classList.add('open');
        if (overlay) overlay.style.display = 'block';
        if (toggle) toggle.classList.add('open');
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        if (overlay) overlay.style.display = 'none';
        if (toggle) toggle.classList.remove('open');
    }

    if (toggle) toggle.addEventListener('click', () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar());
    if (overlay) overlay.addEventListener('click', closeSidebar);

    // Close sidebar on link click (mobile)
    document.querySelectorAll('.sidebar-menu a').forEach(link => {
        link.addEventListener('click', () => { if (window.innerWidth <= 768) closeSidebar(); });
    });
});


// Toggle Sidebar for mobile


// Show notification
function showNotification(message, type = 'success') {
    const notificationHTML = `
        <div class="notification ${type}" style="
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#000' : '#E50914'};
            color: #fff;
            padding: 15px 25px;
            border-radius: 0;
            z-index: 9999;
            animation: slideIn 0.3s ease;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        ">
            ${message}
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', notificationHTML);

    const notification = document.querySelector('.notification');

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Search functionality
function initializeSearch(inputId, tableBodyId) {
    const searchInput = document.getElementById(inputId);
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            const searchValue = this.value.toLowerCase();
            const tableRows = document.querySelectorAll(`#${tableBodyId} tr`);

            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
}

// Animate stats on load
window.addEventListener('load', function () {
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.5s ease';

            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        }, index * 100);
    });
});

// Confirm delete
function confirmDelete(itemName) {
    return confirm(`Are you sure you want to delete ${itemName}?`);
}

// Format currency
function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2);
}

// Format date
function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}