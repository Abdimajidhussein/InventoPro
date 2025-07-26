<?php
// includes/footer.php
?>
            </div> <div class="footer">
                <p>&copy; <?= date('Y') ?> InventoPro Inventory Management System. All rights reserved.</p>
            </div>
        </div> </div> <script>
        // Toggle sidebar on mobile
        document.querySelector('.toggle-sidebar').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        // Add active class to menu items (this would typically be handled server-side for initial load)
        const menuItems = document.querySelectorAll('.menu-item');
        menuItems.forEach(item => {
            item.addEventListener('click', function() {
                // Remove active from all
                menuItems.forEach(i => i.classList.remove('active'));
                // Add active to the clicked one
                this.classList.add('active');
                // In a real PHP app, clicking would trigger a page load,
                // and the active class would be set by PHP on the new page.
            });
        });
    </script>
</body>
</html>