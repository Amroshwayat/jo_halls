</div> 
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {

        $('.sidebar-toggle').on('click', function() {
            $('body').toggleClass('sidebar-collapsed');
        });

        $('.alert').delay(5000).fadeOut(500);

        $('[data-toggle="tooltip"]').tooltip();

        $('[data-toggle="popover"]').popover();
    });
    </script>

    <?php if (isset($extraScripts)): ?>
    <script>
    $(document).ready(function() {
        <?php echo $extraScripts; ?>
    });
    </script>
    <?php endif; ?>
</body>
</html>