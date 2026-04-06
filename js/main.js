/*
 * ConsuTrade - Main JavaScript File
 * Author: Kamogelo Phale
 */

// Wait for the HTML document to fully load before running JavaScript
// This is a common practice to ensure all elements exist
// Without this, JavaScript might try to find elements that haven't been created yet
document.addEventListener('DOMContentLoaded', function() {
    
    // ========== HAMBURGER MENU TOGGLE ==========
    
    // Step 1: Get the hamburger button element from the page
    // getElementById finds an element by its ID attribute
    // This is the three lines button (☰) that users click to open the menu
    const hamburger = document.getElementById('hamburger');
    
    // Step 2: Get the mobile side menu element
    // This is the panel that slides in from the left when hamburger is clicked
    const sideMenu = document.getElementById('mobile-side-menu');
    
    // Step 3: Get the overlay element
    // This is the dark transparent background that appears behind the menu
    // It dims the main content and closes the menu when clicked
    const overlay = document.getElementById('menu-overlay');
    
    // Step 4: Check if ALL three elements exist on the page
    // This prevents errors if someone removes the elements from HTML later
    // It's called "defensive programming" - always check before using
    if (hamburger && sideMenu && overlay) {
        
        // Step 5: Define a function to toggle the menu (open/close)
        // A function is a reusable block of code
        // "toggle" means switch between two states (open → close, close → open)
        function toggleMenu() {
            
            // classList.toggle adds the class if missing, removes if present
            // Example: if hamburger has no 'active' class, add it. If it has it, remove it.
            
            // Toggle the active class on hamburger (for X animation)
            hamburger.classList.toggle('active');
            
            // Toggle the active class on side menu (controls slide in/out)
            sideMenu.classList.toggle('active');
            
            // Toggle the active class on overlay (controls visibility of dark background)
            overlay.classList.toggle('active');
            
            // Check if the side menu is NOW active (open)
            if (sideMenu.classList.contains('active')) {
                // Add 'menu-open' class to body element
                // This class is used in CSS to dim the main content
                document.body.classList.add('menu-open');
                
                // Prevent the user from scrolling the page while menu is open
                // 'hidden' means no scrolling allowed
                document.body.style.overflow = 'hidden';
            } else {
                // Menu is now closed, so remove the 'menu-open' class
                document.body.classList.remove('menu-open');
                
                // Restore scrolling ability to the page
                // Empty string means revert to default behavior
                document.body.style.overflow = '';
            }
        }
        
        // Step 6: Add click event listener to hamburger button
        // 'click' is the event type (user clicks the button)
        // toggleMenu is the function to run when click happens
        // When user clicks the hamburger icon, the menu opens or closes
        hamburger.addEventListener('click', toggleMenu);
        
        // Step 7: Close menu when clicking on overlay
        // When user clicks the dark background behind the menu, the menu closes
        // This is standard behavior for side menus (like on mobile apps)
        overlay.addEventListener('click', toggleMenu);
        
        // Step 8: Close menu when clicking on any navigation link inside the side menu
        // First, find all the links inside .mobile-nav-links and the cart link
        // querySelectorAll finds ALL elements that match the selector
        const menuLinks = document.querySelectorAll('.mobile-nav-links a, .mobile-menu-cart');
        
        // forEach loops through each link one by one
        // For each link, add a click event listener that closes the menu
        menuLinks.forEach(function(link) {
            link.addEventListener('click', toggleMenu);
        });
        
        // Step 9: Close menu if window is resized to desktop size (above 768px)
        // This ensures if someone opens menu on mobile then rotates phone to landscape
        // or resizes browser window, the menu doesn't stay stuck open
        window.addEventListener('resize', function() {
            // Check if window width is greater than 768px (desktop size)
            if (window.innerWidth > 768) {
                // Check if side menu is currently active (open)
                if (sideMenu.classList.contains('active')) {
                    // Close the menu
                    toggleMenu();
                }
            }
        });
    }
    
    // ========== MOBILE SEARCH TOGGLE ==========
    // This controls the expandable search bar on mobile header
    
    // Step 1: Get the mobile search icon button
    // This is the magnifying glass icon in the mobile header
    const mobileSearchIcon = document.getElementById('mobileSearchIcon');
    
    // Step 2: Get the mobile search container (the search input that appears)
    // This is the hidden search bar that expands when icon is clicked
    const mobileSearchContainer = document.getElementById('mobileSearchContainer');
    
    // Step 3: Check if BOTH elements exist
    if (mobileSearchIcon && mobileSearchContainer) {
        
        // Step 4: Add click listener to the search icon
        mobileSearchIcon.addEventListener('click', function() {
            // Toggle the 'active' class on search container
            // This makes the search input appear/disappear
            mobileSearchContainer.classList.toggle('active');
            
            // Auto-focus the search input when opened
            // This means the keyboard will automatically pop up on phones
            // Check if the search container is now active (open)
            if (mobileSearchContainer.classList.contains('active')) {
                // Find the search input field inside the container
                const searchInput = document.getElementById('mobile-search');
                // If the input exists, focus on it (cursor appears ready to type)
                if (searchInput) {
                    searchInput.focus();
                }
            }
        });
    }
    
    // ========== CLOSE SEARCH WHEN CLICKING OUTSIDE ==========
    // This closes the search bar if user clicks anywhere else on the page
    
    // Add a click listener to the entire document (the whole webpage)
    document.addEventListener('click', function(event) {
        // Check if both search elements exist
        if (mobileSearchContainer && mobileSearchIcon) {
            
            // Check three conditions:
            // 1. Click was NOT inside the search container
            // 2. Click was NOT on the search icon button
            // 3. The search container IS currently active (open)
            // If ALL three are true, close the search bar
            
            // .contains(event.target) checks if the clicked element is inside this element
            // ! means NOT (opposite)
            if (!mobileSearchContainer.contains(event.target) && 
                !mobileSearchIcon.contains(event.target) && 
                mobileSearchContainer.classList.contains('active')) {
                
                // Remove the 'active' class to hide the search container
                mobileSearchContainer.classList.remove('active');
            }
        }
    });
});

// ========== FEATURES TO BE IMPLEMENTED LATER ==========
// - Product image preview on upload
// - Shopping cart add/remove logic
// - Form validation (register, login, listing)
// - Lazy loading for product images (low-data optimisation)