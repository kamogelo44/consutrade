// Toggle filter sidebar on mobile
const filterBtn = document.querySelector('.filter-btn');
const sidebar = document.querySelector('.side-bar');

if (filterBtn && sidebar) {
    filterBtn.addEventListener('click', function() {
        sidebar.classList.toggle('active');
    });
}