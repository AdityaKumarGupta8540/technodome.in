        </main>

        <!-- Footer -->
        <footer class="bg-white/60 border-t border-gray-100 py-6 px-6 md:px-8 mt-auto text-center md:flex md:justify-between md:items-center text-xs text-gray-400 font-semibold">
            <div>&copy; <?php echo date('Y'); ?> Technodome Education System &bull; Admin Management Portal</div>
            <div class="mt-2 md:mt-0 flex items-center justify-center gap-4">
                <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Server Operational</span>
                <span>PHP 8.2 &bull; MySQL</span>
            </div>
        </footer>
    </div>

    <!-- Interactive Sidebar and UI Scripts -->
    <script>
        // Sidebar Mobile Toggle
        function openSidebarMobile() {
            const sidebar = document.getElementById('admin-sidebar');
            const overlay = document.getElementById('mobile-sidebar-overlay');
            if (sidebar && overlay) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                setTimeout(() => {
                    overlay.classList.remove('opacity-0');
                    overlay.classList.add('opacity-100');
                }, 10);
            }
        }

        function closeSidebarMobile() {
            const sidebar = document.getElementById('admin-sidebar');
            const overlay = document.getElementById('mobile-sidebar-overlay');
            if (sidebar && overlay) {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.remove('opacity-100');
                overlay.classList.add('opacity-0');
                setTimeout(() => {
                    overlay.classList.add('hidden');
                }, 300);
            }
        }

        // Desktop Sidebar Collapse / Expand
        function toggleSidebarCollapse() {
            const sidebar = document.getElementById('admin-sidebar');
            const mainWrapper = document.getElementById('main-wrapper');
            if (!sidebar || !mainWrapper) return;

            const isCollapsed = sidebar.classList.toggle('sidebar-collapsed');
            mainWrapper.classList.toggle('main-content-expanded', isCollapsed);

            // Store preference in localStorage
            localStorage.setItem('admin_sidebar_collapsed', isCollapsed ? 'true' : 'false');
        }

        // Apply saved collapse preference on load
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
            const isCollapsed = localStorage.getItem('admin_sidebar_collapsed') === 'true';
            if (isCollapsed && window.innerWidth >= 768) {
                const sidebar = document.getElementById('admin-sidebar');
                const mainWrapper = document.getElementById('main-wrapper');
                if (sidebar && mainWrapper) {
                    sidebar.classList.add('sidebar-collapsed');
                    mainWrapper.classList.add('main-content-expanded');
                }
            }
        });

        // Simple Toast Notification Helper
        function showAdminToast(message, type = 'success') {
            const toast = document.createElement('div');
            const colors = {
                success: 'bg-emerald-600 text-white',
                error: 'bg-rose-600 text-white',
                info: 'bg-brand text-white'
            };
            toast.className = `fixed bottom-6 right-6 z-50 px-6 py-3.5 rounded-2xl shadow-xl font-bold text-sm flex items-center gap-3 transition-all transform translate-y-8 opacity-0 ${colors[type] || colors.info}`;
            toast.innerHTML = `<i class="fa-solid fa-${type === 'success' ? 'circle-check' : (type === 'error' ? 'circle-exclamation' : 'circle-info')}"></i> <span>${message}</span>`;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.remove('translate-y-8', 'opacity-0');
            }, 10);

            setTimeout(() => {
                toast.classList.add('translate-y-8', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        }
    </script>
</body>
</html>
