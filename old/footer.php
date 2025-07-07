        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>Copyright &copy; Group Chat <?= date('Y') ?></span>
                </div>
            </div>
        </footer>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
// Toggle sidebar on mobile
document.getElementById('sidebarToggle').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('toggled');
    document.getElementById('main-content').classList.toggle('toggled');
});
        </script>
        </body>

        </html>