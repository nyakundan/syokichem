<?php
/**
 * Admin Footer Template
 * Includes all scripts and closes HTML structure
 */
?>

        </main><!-- End main content -->
    </div><!-- End row -->
</div><!-- End container-fluid -->

<footer class="footer mt-auto py-3 bg-dark text-white">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center">
                    <img src="/assets/images/logo-sm.png" alt="Logo" width="30" class="me-2">
                    <span>
                        &copy; <?= date('Y') ?> <?= htmlspecialchars($config['site_name'] ?? 'Admin Panel') ?>
                        <span class="text-muted ms-2">v<?= htmlspecialchars($config['version'] ?? '1.0.0') ?></span>
                    </span>
                </div>
            </div>
            <div class="col-md-6 text-md-end mt-2 mt-md-0">
                <small class="text-muted">
                    Server: <?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? '') ?> | 
                    PHP: <?= phpversion() ?> | 
                    Load: <?= sys_getloadavg()[0] ?? '0' ?>
                </small>
            </div>
        </div>
    </div>
</footer>

<!-- Toast Notification Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto">System Notification</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <?= $_SESSION['toast_message'] ?? '' ?>
        </div>
    </div>
</div>

<!-- JavaScript Libraries -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/v/bs5/dt-1.13.4/datatables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Custom Admin JS -->
<script src="/assets/js/admin.min.js?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'].'/assets/js/admin.min.js') ?>"></script>

<?php if (!empty($page_scripts)): ?>
    <!-- Page-specific JS -->
    <?php foreach ((array)$page_scripts as $script): ?>
        <?php if (filter_var($script, FILTER_VALIDATE_URL)): ?>
            <script src="<?= htmlspecialchars($script) ?>"></script>
        <?php else: ?>
            <script src="/assets/js/<?= htmlspecialchars(ltrim($script, '/')) ?>?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'].'/assets/js/'.ltrim($script, '/')) ?>"></script>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif; ?>

<script>
// Initialize core functionality
$(document).ready(function() {
    // Initialize DataTables
    $('.data-table').DataTable({
        responsive: true,
        dom: '<"top"lf>rt<"bottom"ip>',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search...",
            lengthMenu: "Show _MENU_ entries"
        }
    });

    // Tooltips
    $('[data-bs-toggle="tooltip"]').tooltip({
        trigger: 'hover'
    });

    // Toast notifications
    <?php if (isset($_SESSION['toast_message'])): ?>
        const toast = new bootstrap.Toast(document.getElementById('liveToast'));
        toast.show();
        <?php unset($_SESSION['toast_message']); ?>
    <?php endif; ?>

    // Confirm destructive actions
    $('.confirm-action').on('click', function(e) {
        if (!confirm($(this).data('confirm') || 'Are you sure?')) {
            e.preventDefault();
        }
    });
});

// Global AJAX error handling
$(document).ajaxError(function(event, jqxhr, settings, thrownError) {
    console.error("AJAX Error:", settings.url, thrownError);
    alert('Operation failed. Please try again.');
});
</script>

<?php
// Debug bar for development environment
if ($_ENV['APP_ENV'] === 'development'): ?>
<script src="//cdnjs.cloudflare.com/ajax/libs/eruda/2.4.1/eruda.min.js"></script>
<script>eruda.init();</script>
<?php endif; ?>

</body>
</html>