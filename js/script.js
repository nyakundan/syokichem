let navbar = document.querySelector('.header .flex .navbar');
let profile = document.querySelector('.header .flex .profile');

document.querySelector('#menu-btn').onclick = () =>{
   navbar.classList.toggle('active');
   profile.classList.remove('active');
}

document.querySelector('#user-btn').onclick = () =>{
   profile.classList.toggle('active');
   navbar.classList.remove('active');
}

window.onscroll = () =>{
   navbar.classList.remove('active');
   profile.classList.remove('active');
}

let mainImage = document.querySelector('.quick-view .box .row .image-container .main-image img');
let subImages = document.querySelectorAll('.quick-view .box .row .image-container .sub-image img');

subImages.forEach(images =>{
   images.onclick = () =>{
      src = images.getAttribute('src');
      mainImage.src = src;
   }
});

// File upload preview functionality
document.addEventListener('DOMContentLoaded', function() {
   const fileInput = document.querySelector('input[type="file"]');
   const filePreview = document.createElement('div');
   filePreview.className = 'file-preview';
   fileInput.parentNode.insertBefore(filePreview, fileInput.nextSibling);

   fileInput.addEventListener('change', function(e) {
       const file = e.target.files[0];
       if (!file) return;

       filePreview.innerHTML = '';
       filePreview.classList.add('active');

       // Create elements based on file type
       if (file.type.match('image.*')) {
           const reader = new FileReader();
           reader.onload = function(e) {
               const img = document.createElement('img');
               img.src = e.target.result;
               filePreview.appendChild(img);
               showFileInfo(file);
           };
           reader.readAsDataURL(file);
       } else if (file.type === 'application/pdf') {
           const fileIcon = document.createElement('div');
           fileIcon.className = 'file-icon';
           fileIcon.innerHTML = '<i class="fas fa-file-pdf"></i>';
           filePreview.appendChild(fileIcon);
           showFileInfo(file);
       } else {
           const fileIcon = document.createElement('div');
           fileIcon.className = 'file-icon';
           fileIcon.innerHTML = '<i class="fas fa-file-alt"></i>';
           filePreview.appendChild(fileIcon);
           showFileInfo(file);
       }
   });

   function showFileInfo(file) {
       const fileName = document.createElement('div');
       fileName.className = 'file-name';
       fileName.textContent = file.name;
       filePreview.appendChild(fileName);

       const fileSize = document.createElement('div');
       fileSize.className = 'file-size';
       fileSize.textContent = formatFileSize(file.size);
       filePreview.appendChild(fileSize);
   }

   function formatFileSize(bytes) {
       if (bytes === 0) return '0 Bytes';
       const k = 1024;
       const sizes = ['Bytes', 'KB', 'MB', 'GB'];
       const i = Math.floor(Math.log(bytes) / Math.log(k));
       return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
   }
});


document.addEventListener('DOMContentLoaded', function() {
    const mainCategories = document.querySelectorAll('.main-category');
    const subcategoryGroups = document.querySelectorAll('.subcategory-group');
    
    // Show first category by default
    if (subcategoryGroups.length > 0) {
        subcategoryGroups[0].classList.add('active');
        mainCategories[0].classList.add('active');
    }
    
    mainCategories.forEach(category => {
        category.addEventListener('mouseenter', function() {
            const target = this.getAttribute('data-target');
            
            // Remove active class from all
            mainCategories.forEach(c => c.classList.remove('active'));
            subcategoryGroups.forEach(g => g.classList.remove('active'));
            
            // Add active class to current
            this.classList.add('active');
            document.getElementById(target).classList.add('active');
        });
    });
    
    // For touch devices
    if ('ontouchstart' in window) {
        mainCategories.forEach(category => {
            category.addEventListener('click', function(e) {
                e.preventDefault();
                const target = this.getAttribute('data-target');
                
                // Remove active class from all
                mainCategories.forEach(c => c.classList.remove('active'));
                subcategoryGroups.forEach(g => g.classList.remove('active'));
                
                // Add active class to current
                this.classList.add('active');
                document.getElementById(target).classList.add('active');
            });
        });
    }
});

// Handle all form submissions
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const submitter = e.submitter || this.querySelector('[type="submit"]');
        
        if (!submitter) return;
        
        const isCartAction = submitter.name === 'add_to_cart';
        const isWishlistAction = submitter.name === 'add_to_wishlist';
        
        if (!isCartAction && !isWishlistAction) return;
        
        const originalContent = submitter.innerHTML;
        
        try {
            submitter.disabled = true;
            submitter.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + 
                (isCartAction ? 'Adding...' : 'Saving...');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(result => {
                // Parse the response to check for messages
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = result;
                const messages = tempDiv.querySelectorAll('.message');
                
                // Show messages
                messages.forEach(msg => {
                    showToast(msg.textContent);
                });
                
                // Update cart count if added to cart
                if (isCartAction) {
                    updateCartCount();
                }
                
                // Update wishlist button if added to wishlist
                if (isWishlistAction && !result.includes('already exists')) {
                    submitter.classList.add('in-wishlist');
                    submitter.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred. Please try again.', 'error');
            })
            .finally(() => {
                if (isCartAction || (isWishlistAction && !submitter.classList.contains('in-wishlist'))) {
                    submitter.disabled = false;
                    submitter.innerHTML = originalContent;
                }
            });
        } catch (error) {
            console.error('Error:', error);
            showToast('An error occurred. Please try again.', 'error');
            submitter.disabled = false;
            submitter.innerHTML = originalContent;
        }
    });
});

// Toast notification function
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Update cart count
function updateCartCount() {
    fetch('components/get_cart_count.php')
    .then(response => response.text())
    .then(count => {
        const cartCount = document.querySelector('.cart-count');
        if (cartCount) {
            cartCount.textContent = count;
            cartCount.classList.add('pulse');
            setTimeout(() => cartCount.classList.remove('pulse'), 500);
        }
    })
    .catch(error => {
        console.error('Error updating cart count:', error);
    });
}
