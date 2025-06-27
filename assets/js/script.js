// SOS Modal - Load route-specific contacts
$(document).ready(function() {
    // Load route contacts when SOS modal is shown
    $('#sosModal').on('show.bs.modal', function() {
        $.ajax({
            url: 'functions/get_emergency_contacts.php',
            method: 'GET',
            success: function(response) {
                $('#routeContacts').html(response);
            },
            error: function() {
                $('#routeContacts').html('<p class="text-danger">Failed to load route contacts</p>');
            }
        });
    });
    
    // Live bus tracking simulation
    $('.track-bus').click(function(e) {
        e.preventDefault();
        var busId = $(this).data('bus-id');
        
        Swal.fire({
            title: 'Live Bus Tracking',
            html: '<div class="text-center"><i class="fas fa-bus fa-3x text-primary mb-3"></i>' +
                  '<p>Bus TN-01-AB-1234 is currently at:</p>' +
                  '<h4>Chennai Highway, 50km from departure</h4>' +
                  '<div class="progress mt-3">' +
                  '<div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 35%">35%</div>' +
                  '</div></div>',
            showConfirmButton: true,
            confirmButtonText: 'Close',
            showCancelButton: false,
            customClass: {
                popup: 'animated bounceIn'
            }
        });
    });
    
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Initialize popovers
    $('[data-bs-toggle="popover"]').popover();
});

// Form validation for registration
function validateRegistrationForm() {
    var password = document.getElementById('password').value;
    var confirm_password = document.getElementById('confirm_password').value;
    
    if (password != confirm_password) {
        alert('Passwords do not match!');
        return false;
    }
    
    return true;
}