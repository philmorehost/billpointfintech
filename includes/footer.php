</main>

    <!-- Mobile Footer Navigation -->
    <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
    <nav class="mobile-footer-nav">
        <a href="dashboard.php" class="nav-item <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="transactions.php" class="nav-item <?php echo ($currentPage == 'transactions.php') ? 'active' : ''; ?>">
            <i class="fas fa-receipt"></i>
            <span>Transactions</span>
        </a>
        <a href="bonus.php" class="nav-item <?php echo ($currentPage == 'bonus.php') ? 'active' : ''; ?>">
            <i class="fas fa-gift"></i>
            <span>Bonus</span>
        </a>
        <a href="profile.php" class="nav-item <?php echo ($currentPage == 'profile.php') ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </nav>

    <!-- Generic Modal -->
    <div id="genericModal" class="modal">
        <!-- ... (generic modal content) ... -->
    </div>

    <script src="../assets/js/script.js"></script>
</body>
</html>
