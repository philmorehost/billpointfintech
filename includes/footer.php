</main>

    <nav class="footer-nav">
        <!-- ... (footer nav links) ... -->
    </nav>

    <!-- Generic Modal -->
    <div id="genericModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h3 id="modalTitle">Notice</h3>
            <p id="modalMessage"></p>
        </div>
    </div>

    <!-- Security PIN Modal -->
    <div id="pinModal" class="modal">
        <!-- ... (PIN modal content) ... -->
    </div>

    <script src="../assets/js/script.js"></script>
    <script>
    // --- Generic Modal Logic (Now in global scope) ---
    const modal = document.getElementById('genericModal');
    const closeBtn = modal.querySelector('.close-btn');
    const modalTitle = document.getElementById('modalTitle');
    const modalMessage = document.getElementById('modalMessage');

    function showModal(title, message) {
        modalTitle.textContent = title;
        modalMessage.textContent = message;
        modal.style.display = 'block';
    }

    closeBtn.onclick = function() {
        modal.style.display = 'none';
    }
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }

    // ... (PIN Modal and Nav Logic) ...
    </script>
</body>
</html>
