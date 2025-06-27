<?php
require_once 'config.php';
?>
</div> <!-- Close container div -->
    </main>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>About Us</h5>
                    <p>Bus Booking System provides convenient and reliable bus ticket booking services across major cities.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo BASE_URL; ?>search.php" class="text-white">Book Tickets</a></li>
                        <li><a href="<?php echo BASE_URL; ?>mybookings.php" class="text-white">My Bookings</a></li>
                        <li>
                            <a href="#" class="text-white" data-bs-toggle="modal" data-bs-target="#cancellationPolicyModal">Cancellation Policy</a>
                        </li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <address>
                        <i class="fas fa-map-marker-alt"></i> 123 Bus Street, City<br>
                        <i class="fas fa-phone"></i> +91 9876543210<br>
                        <i class="fas fa-envelope"></i> info@busbookingsystem.com
                    </address>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> Bus Booking System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Cancellation Policy Modal -->
    <div class="modal fade" id="cancellationPolicyModal" tabindex="-1" aria-labelledby="cancellationPolicyModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-dark">
          <div class="modal-header">
            <h5 class="modal-title" id="cancellationPolicyModalLabel">Cancellation Policy</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <ul>
                <li>Tickets cancelled more than 24 hours before departure: <strong>80% refund</strong></li>
                <li>Tickets cancelled 12-24 hours before departure: <strong>50% refund</strong></li>
                <li>Tickets cancelled less than 12 hours before departure: <strong>No refund</strong></li>
                <li>Refunds will be processed to the original payment method within 5-7 business days.</li>
                <li>For any issues, please contact our support team.</li>
            </ul>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/script.js"></script>
</body>
</html>