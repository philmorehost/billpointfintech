      </main>
    </div>
    <!-- Overlay for mobile -->
    <div class="overlay"></div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    $(document).ready(function () {
        // Desktop toggle
        $('#sidebar-toggle').on('click', function () {
            if ($(window).width() > 768) {
                $('#sidebar').toggleClass('collapsed');
            } else {
                // Mobile toggle
                $('#sidebar').toggleClass('active');
                $('.overlay').toggleClass('active');
            }
        });

        // Hide sidebar when overlay is clicked
        $('.overlay').on('click', function () {
            $('#sidebar').removeClass('active');
            $('.overlay').removeClass('active');
        });
    });
</script>
</body>
</html>
