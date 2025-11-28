      </main>
    </div>
    <!-- Overlay for mobile -->
    <div class="overlay"></div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
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
