<?php
/**
 * Displays session-based alert messages (success, error, warning, info)
 * Clears messages after displaying them
 */

// Check if we should display alerts (only show once per page load)
if (isset($_SESSION['alerts'])): 
    // Store alerts in local variable and clear session
    $alerts = $_SESSION['alerts'];
    unset($_SESSION['alerts']);
?>
    <div class="alerts-container" style="position: fixed; top: 20px; right: 20px; z-index: 1050; width: 350px;">
        <?php foreach ($alerts as $alert): ?>
            <div class="alert alert-<?= htmlspecialchars($alert['type']) ?> alert-dismissible fade show" role="alert">
                <?php if ($alert['type'] === 'success'): ?>
                    <i class="fas fa-check-circle me-2"></i>
                <?php elseif ($alert['type'] === 'error'): ?>
                    <i class="fas fa-exclamation-circle me-2"></i>
                <?php elseif ($alert['type'] === 'warning'): ?>
                    <i class="fas fa-exclamation-triangle me-2"></i>
                <?php elseif ($alert['type'] === 'info'): ?>
                    <i class="fas fa-info-circle me-2"></i>
                <?php endif; ?>
                
                <?= htmlspecialchars($alert['message']) ?>
                
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
    // Auto-dismiss alerts after 5 seconds
    $(document).ready(function() {
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    });
    </script>
<?php endif; ?>