
<style>
.footer-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background-color: #ffffff;
    display: flex;
    justify-content: space-around;
    align-items: center;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    padding: 5px 0;
    border-top: 1px solid #f0f0f0;
}

.footer-nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-decoration: none;
    color: #9ca3af; /* Gray color for inactive items */
    font-size: 12px;
    padding: 5px 0;
    flex-grow: 1;
}

.footer-nav-item i {
    font-size: 22px;
    margin-bottom: 4px;
}

.footer-nav-item.active {
    color: #4f46e5; /* Primary color for the active item */
}
</style>

<div class="footer-nav">
    <a href="dashboard.php" class="footer-nav-item" id="nav-home">
        <i class="fas fa-home"></i>
        <span>Home</span>
    </a>
    <a href="transactions.php" class="footer-nav-item" id="nav-transactions">
        <i class="fas fa-history"></i>
        <span>History</span>
    </a>
    <a href="support.php" class="footer-nav-item" id="nav-support">
        <i class="fas fa-headset"></i>
        <span>Support</span>
    </a>
    <a href="profile.php" class="footer-nav-item" id="nav-profile">
        <i class="fas fa-user"></i>
        <span>Profile</span>
    </a>
</div>

<script>
// JavaScript to set the active state on the current page's nav item
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    if (currentPage === 'dashboard.php') {
        document.getElementById('nav-home').classList.add('active');
    } else if (currentPage === 'transactions.php') {
        document.getElementById('nav-transactions').classList.add('active');
    } else if (currentPage === 'support.php' || currentPage === 'view_ticket.php') {
        document.getElementById('nav-support').classList.add('active');
    } else if (currentPage === 'profile.php') {
        document.getElementById('nav-profile').classList.add('active');
    }
});
</script>

</body>
</html>
