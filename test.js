// Open modal (call this when user clicks Register/Login button)
function openModal() {
    const modal = document.getElementById('register-modal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden'; // Prevent scrolling
}

// Close modal
function closeModal() {
    const modal = document.getElementById('register-modal');
    modal.classList.remove('active');
    document.body.style.overflow = ''; // Restore scrolling
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('register-modal');
    const closeBtn = document.querySelector('.btn-close');
    
    // Close when clicking close button
    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }
    
    // Close when clicking outside modal content
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }
});