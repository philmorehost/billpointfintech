</main>

    <!-- Mobile Footer Navigation -->
    <nav class="mobile-footer-nav">
        <a href="dashboard.php" class="nav-item">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="transactions.php" class="nav-item active"> <!-- 'active' class for current page -->
            <i class="fas fa-receipt"></i>
            <span>Transaction</span>
        </a>
        <a href="#" class="nav-item"> <!-- Link to be updated -->
            <i class="fas fa-bell"></i>
            <span>Notifications</span>
        </a>
        <a href="profile.php" class="nav-item">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </nav>

    <!-- Generic Modal -->
    <div id="genericModal" class="modal">
        <!-- ... (generic modal content) ... -->
    </div>

    <!-- Security PIN Modal -->
    <div id="pinModal" class="modal">
        <div class="modal-content">
            <h3>Enter Security PIN</h3>
            <p>For your security, please enter your 4-digit PIN to proceed.</p>
            <form id="pinForm">
                <input type="password" id="pinInput" inputmode="numeric" pattern="\d{4}" maxlength="4" required>
                <button type="submit">Authorize</button>
            </form>
            <p id="pinError" style="color:red; display:none;"></p>
        </div>
    </div>


    <script src="../assets/js/script.js"></script>
    <script>
    // ... (generic modal and nav JS) ...

    // --- PIN Modal Logic ---
    const pinModal = document.getElementById('pinModal');
    const pinForm = document.getElementById('pinForm');
    const pinInput = document.getElementById('pinInput');
    const pinError = document.getElementById('pinError');
    let resolvePinPromise;

    async function requestPin() {
        pinModal.style.display = 'block';
        pinInput.focus();
        return new Promise((resolve) => {
            resolvePinPromise = resolve;
        });
    }

    pinForm.onsubmit = async function(e) {
        e.preventDefault();
        const pin = pinInput.value;
        pinError.style.display = 'none';

        try {
            const formData = new FormData();
            formData.append('pin', pin);

            const response = await fetch('ajax_verify_pin.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.status === 'success') {
                pinModal.style.display = 'none';
                pinInput.value = '';
                if (resolvePinPromise) resolvePinPromise(true);
            } else {
                pinError.textContent = data.message || 'Invalid PIN.';
                pinError.style.display = 'block';
                pinInput.select();
                if (resolvePinPromise) resolvePinPromise(false);
            }
        } catch (error) {
            pinError.textContent = 'An error occurred. Please try again.';
            pinError.style.display = 'block';
            if (resolvePinPromise) resolvePinPromise(false);
        }
    };
    </script>
</body>
</html>
