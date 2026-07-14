<?php // main/layout_footer.php ?>
    </main> </div> <script>
    document.getElementById('toggle-sidebar-btn').addEventListener('click', function(e) {
        e.stopPropagation();
        document.querySelector('.app-body').classList.toggle('sidebar-open');
    });

    document.addEventListener('click', function(e) {
        const body = document.querySelector('.app-body');
        const sidebar = document.querySelector('.main-sidebar');
        if (body && body.classList.contains('sidebar-open') && sidebar && !sidebar.contains(e.target)) {
            body.classList.remove('sidebar-open');
        }
    });
</script>