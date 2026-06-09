        </main>
    </div>
</div>
<script>
// Close sidebar on mobile when clicking outside
document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('adminSidebar');
    const toggle = document.querySelector('.menu-toggle');
    if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
        sidebar.classList.remove('open');
    }
});
</script>
</body>
</html>
