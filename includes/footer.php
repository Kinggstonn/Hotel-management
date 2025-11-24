        </main>
        
        <footer class="footer bg-light text-center py-4 mt-5 rounded">
            <div class="container">
                <p class="mb-2">&copy; <?php echo date('Y'); ?> Made by Kingston. All rights reserved.</p>
                <div class="footer-links">
                    <small class="text-muted">
                        <strong>Phiên bản:</strong> 2.0 | 
                        <strong>Cập nhật:</strong> <?php echo date('d/m/Y'); ?> |
                        <strong>Thời gian:</strong> <span id="current-time"></span>
                    </small>
                </div>
            </div>
        </footer>
    </div>

    <!-- JavaScript for enhanced UX -->
    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Add loading state to buttons
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (this.href && !this.href.includes('#')) {
                    const originalText = this.innerHTML;
                    this.innerHTML = originalText + ' <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
                    this.disabled = true;
                    
                    // Re-enable button after navigation
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }, 2000);
                }
            });
        });

        // Add current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('vi-VN');
            const timeEl = document.getElementById('current-time');
            if (timeEl) {
                timeEl.textContent = timeString;
            }
        }
        
        setInterval(updateTime, 1000);
        updateTime();
        
        console.log('%c🏨 Hotel Management System v2.0', 'color: #667eea; font-size: 16px; font-weight: bold;');
        console.log('%cSystem loaded successfully! 🎉', 'color: #27ae60; font-size: 12px;');
    </script>
    
    <!-- DataTables Global Configuration -->
    <script>
        // Global DataTables configuration
        $.extend(true, $.fn.dataTable.defaults, {
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json'
            },
            pageLength: 25,
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Tất cả"]],
            initComplete: function() {
                // Thêm class Bootstrap cho các elements của DataTables
                $('.dataTables_length select').addClass('form-select form-select-sm');
                $('.dataTables_filter input').addClass('form-control form-control-sm');
            },
            // Fix column count issues
            autoWidth: false,
            scrollX: true,
            // Ensure proper table structure
            createdRow: function(row, data, dataIndex) {
                // Ensure all cells have proper structure
                $(row).find('td').each(function(index) {
                    if (!$(this).html().trim()) {
                        $(this).html('&nbsp;');
                    }
                });
            }
        });
        
        // Fix DataTables responsive issues
        $(document).ready(function() {
            // Recalculate DataTables on window resize
            $(window).on('resize', function() {
                $('.dataTables_wrapper').each(function() {
                    if ($.fn.DataTable.isDataTable($(this).find('table').first())) {
                        $(this).find('table').DataTable().columns.adjust().responsive.recalc();
                    }
                });
            });
        });
    </script>
</body>
</html>
