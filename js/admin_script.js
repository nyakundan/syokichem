// Main Navigation Functions
document.addEventListener('DOMContentLoaded', function() {
   // Mobile navbar toggle
   const navbar = document.querySelector('.header .flex .navbar');
   const profile = document.querySelector('.header .flex .profile');
   
   if (document.querySelector('#menu-btn')) {
       document.querySelector('#menu-btn').onclick = () => {
           navbar.classList.toggle('active');
           profile.classList.remove('active');
       };
   }
   
   if (document.querySelector('#user-btn')) {
       document.querySelector('#user-btn').onclick = () => {
           profile.classList.toggle('active');
           navbar.classList.remove('active');
       };
   }
   
   // Close menus on scroll
   window.onscroll = () => {
       navbar.classList.remove('active');
       profile.classList.remove('active');
   };
   
   // Product image switcher
   const mainImage = document.querySelector('.update-product .image-container .main-image img');
   if (mainImage) {
       const subImages = document.querySelectorAll('.update-product .image-container .sub-image img');
       subImages.forEach(image => {
           image.onclick = () => {
               mainImage.src = image.getAttribute('src');
           };
       });
   }
   
   // Admin Sidebar Functionality
   const sidebarToggler = document.querySelector('.sidebar-toggler');
   if (sidebarToggler) {
       sidebarToggler.addEventListener('click', function() {
           document.querySelector('.sidebar').classList.toggle('active');
           document.querySelector('.main-content').classList.toggle('sidebar-collapsed');
       });
   }
   
   // Initialize dropdown functionality
   document.querySelectorAll('.nav-link[data-toggle="collapse"]').forEach(link => {
       link.addEventListener('click', function(e) {
           e.preventDefault();
           const target = document.querySelector(this.getAttribute('data-target'));
           const isCollapsed = this.classList.contains('collapsed');
           
           // Close all other open dropdowns first
           document.querySelectorAll('.collapse.show').forEach(openCollapse => {
               if (openCollapse !== target) {
                   openCollapse.classList.remove('show');
                   const correspondingToggle = document.querySelector(
                       '[data-target="#' + openCollapse.id + '"]'
                   );
                   if (correspondingToggle) {
                       correspondingToggle.classList.add('collapsed');
                   }
               }
           });
           
           // Toggle current dropdown
           target.classList.toggle('show');
           this.classList.toggle('collapsed');
           
           // Store state in localStorage for persistence
           localStorage.setItem(target.id + '_state', target.classList.contains('show') ? 'open' : 'closed');
       });
   });
   
   // Set active state for current page
   const currentPath = window.location.pathname;
   
   // Check all links in sidebar
   document.querySelectorAll('.sidebar a').forEach(link => {
       const linkPath = link.getAttribute('href');
       
       // Basic exact match
       if (currentPath.endsWith(linkPath)) {
           link.classList.add('active');
           
           // If this is a dropdown item, open its parent
           if (link.classList.contains('collapse-item')) {
               const parentCollapse = link.closest('.collapse');
               if (parentCollapse) {
                   parentCollapse.classList.add('show');
                   const toggle = document.querySelector('[data-target="#' + parentCollapse.id + '"]');
                   if (toggle) {
                       toggle.classList.remove('collapsed');
                   }
               }
           }
       }
       
       // More flexible matching for nested paths
       if (linkPath !== '#' && currentPath.includes(linkPath.replace('../admin/', ''))) {
           link.classList.add('active');
       }
   });
   
   // Restore dropdown states from localStorage
   document.querySelectorAll('.collapse').forEach(collapse => {
       const state = localStorage.getItem(collapse.id + '_state');
       if (state === 'open') {
           collapse.classList.add('show');
           const toggle = document.querySelector('[data-target="#' + collapse.id + '"]');
           if (toggle) {
               toggle.classList.remove('collapsed');
           }
       }
   });
   
   // Products submenu handler
   const productsLink = document.querySelector('a[href="/admin/products/"]');
   if (productsLink) {
       productsLink.addEventListener('click', function(e) {
           e.preventDefault();
           const submenu = document.getElementById('productsSubmenu');
           if (submenu) {
               submenu.classList.toggle('show');
           }
           window.location.href = '/admin/products/';
       });
   }
});

// Category Tree Management
document.addEventListener('DOMContentLoaded', function() {
   // Initialize tree toggles
   document.querySelectorAll('.toggle-children').forEach(toggle => {
       toggle.addEventListener('click', function() {
           const id = this.dataset.id;
           const icon = this.querySelector('i');
           const childRows = document.querySelectorAll(`tr.child-row[data-id="${id}"]`);
           
           if (icon.classList.contains('fa-caret-right')) {
               icon.classList.replace('fa-caret-right', 'fa-caret-down');
               childRows.forEach(row => row.style.display = '');
           } else {
               icon.classList.replace('fa-caret-down', 'fa-caret-right');
               childRows.forEach(row => row.style.display = 'none');
           }
       });
   });
   
   // Select all checkbox
   const selectAll = document.getElementById('select-all');
   if (selectAll) {
       selectAll.addEventListener('change', function() {
           document.querySelectorAll('input[name="selected[]"]').forEach(checkbox => {
               checkbox.checked = this.checked;
           });
       });
   }
   
   // Drag and drop sorting
   if (document.getElementById('categories-table')) {
       new Sortable(document.querySelector('#categories-table tbody'), {
           handle: '.drag-handle',
           animation: 150,
           onEnd: function(evt) {
               const rows = Array.from(evt.from.children);
               const ids = rows.map(row => row.dataset.id);
               
               fetch(admin_url('categories/update_sort.php'), {
                   method: 'POST',
                   headers: {
                       'Content-Type': 'application/json',
                       'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                   },
                   body: JSON.stringify({ order: ids })
               }).then(response => {
                   if (!response.ok) {
                       throw new Error('Sort update failed');
                   }
                   return response.json();
               }).then(data => {
                   if (data.success) {
                       showToast('Category order updated successfully');
                   }
               }).catch(error => {
                   console.error('Error:', error);
                   showToast('Failed to update sort order', 'error');
                   window.location.reload();
               });
           }
       });
   }
});

// Toast notifications
function showToast(message, type = 'success') {
   const toast = document.createElement('div');
   toast.className = `toast toast-${type}`;
   toast.innerHTML = message;
   document.body.appendChild(toast);
   
   setTimeout(() => {
       toast.classList.add('show');
       setTimeout(() => {
           toast.classList.remove('show');
           setTimeout(() => toast.remove(), 300);
       }, 3000);
   }, 100);
}


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


// Check for new notifications every 30 seconds
function checkNewNotifications() {
    fetch('notifications/check_new.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update notification badge
                const badge = document.getElementById('notification-badge');
                if (badge) {
                    badge.textContent = data.count > 0 ? data.count : '';
                    badge.classList.toggle('d-none', data.count === 0);
                }
                
                // Optional: Show desktop notification
                if (data.count > 0 && Notification.permission === 'granted') {
                    new Notification(`You have ${data.count} new notifications`);
                }
            }
        });
}

// Start checking
if (window.Notification && Notification.permission !== 'denied') {
    Notification.requestPermission().then(() => {
        setInterval(checkNewNotifications, 30000);
        checkNewNotifications(); // Initial check
    });
}
