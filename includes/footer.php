</main>
    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_NAME); ?>. All rights reserved.</p>
    </footer>

    <!-- Generic Modal -->
    <div id="genericModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h3 id="modalTitle">Notice</h3>
            <p id="modalMessage"></p>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <style>
    .modal { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
    .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 90%; max-width: 500px; border-radius: 10px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
    .close-btn { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
    </style>
    <script>
    // JavaScript to control the modal
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
    </script>
</body>
</html>
