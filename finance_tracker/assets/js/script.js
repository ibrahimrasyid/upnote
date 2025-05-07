// Custom JavaScript for Finance Tracker

// Document Ready Function
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    });

    // Handle delete confirmations
    const deleteButtons = document.querySelectorAll('.btn-delete-confirm');
    if (deleteButtons) {
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                    e.preventDefault();
                }
            });
        });
    }

    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    if (forms) {
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }

    // Transaction type toggle
    const transactionTypeRadios = document.querySelectorAll('input[name="type"]');
    const categorySelectGroup = document.getElementById('category-select-group');
    
    if (transactionTypeRadios && categorySelectGroup) {
        transactionTypeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                updateCategoryOptions(this.value);
            });
        });
        
        // Initialize categories on page load if a type is already selected
        const selectedType = document.querySelector('input[name="type"]:checked');
        if (selectedType) {
            updateCategoryOptions(selectedType.value);
        }
    }

    // Function to update category options based on transaction type
    function updateCategoryOptions(type) {
        const categorySelect = document.getElementById('category_id');
        
        if (categorySelect) {
            const options = categorySelect.querySelectorAll('option');
            
            options.forEach(option => {
                const dataType = option.getAttribute('data-type');
                
                if (dataType && dataType !== type && dataType !== 'both') {
                    option.style.display = 'none';
                } else {
                    option.style.display = '';
                }
            });
            
            // Select the first visible option
            for (let i = 0; i < options.length; i++) {
                if (options[i].style.display !== 'none' && i > 0) {
                    categorySelect.selectedIndex = i;
                    break;
                }
            }
        }
    }
    
    // Date range picker for reports
    const startDateInput = document.getElementById('period_start');
    const endDateInput = document.getElementById('period_end');
    
    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', function() {
            endDateInput.min = this.value;
            if (endDateInput.value && endDateInput.value < this.value) {
                endDateInput.value = this.value;
            }
        });
        
        endDateInput.addEventListener('change', function() {
            startDateInput.max = this.value;
            if (startDateInput.value && startDateInput.value > this.value) {
                startDateInput.value = this.value;
            }
        });
    }
});

// Chart color configuration
const chartColors = {
    income: 'rgba(46, 204, 113, 0.7)',
    expense: 'rgba(231, 76, 60, 0.7)',
    balance: 'rgba(52, 152, 219, 0.7)',
    categories: [
        'rgba(52, 152, 219, 0.7)',   // Blue
        'rgba(46, 204, 113, 0.7)',   // Green
        'rgba(155, 89, 182, 0.7)',   // Purple
        'rgba(231, 76, 60, 0.7)',    // Red
        'rgba(241, 196, 15, 0.7)',   // Yellow
        'rgba(230, 126, 34, 0.7)',   // Orange
        'rgba(26, 188, 156, 0.7)',   // Turquoise
        'rgba(52, 73, 94, 0.7)',     // Dark Blue
        'rgba(149, 165, 166, 0.7)',  // Gray
        'rgba(211, 84, 0, 0.7)'      // Dark Orange
    ]
};

// Function to create bar chart
function createBarChart(elementId, labels, datasets) {
    const ctx = document.getElementById(elementId);
    
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
}

// Function to create pie chart
function createPieChart(elementId, labels, data, backgroundColor) {
    const ctx = document.getElementById(elementId);
    
    if (ctx) {
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: backgroundColor || chartColors.categories
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
}

// Function to create line chart
function createLineChart(elementId, labels, datasets) {
    const ctx = document.getElementById(elementId);
    
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
}


document.addEventListener('DOMContentLoaded', function() {
    const categorySearch = document.getElementById('category_search');
    const categoryDropdown = document.getElementById('category_dropdown');
    const categoryItems = document.querySelectorAll('.category-item');
    const categoryInput = document.getElementById('category_id');
    const categoryDisplay = document.getElementById('selected_category_display');
    const categoryPlaceholder = document.getElementById('category_placeholder');
    const categoryName = document.getElementById('category_name');
    const categoryTypeBadge = document.getElementById('category_type_badge');
    
    // Initialize selected category if any
    const initCategoryId = categoryInput.value;
    if (initCategoryId) {
        for (const item of categoryItems) {
            if (item.dataset.id === initCategoryId) {
                selectCategory(item);
                break;
            }
        }
    }
    
    // Open dropdown when clicking on the display area
    categoryDisplay.addEventListener('click', function() {
        const dropdownButton = document.querySelector('.dropdown-toggle');
        dropdownButton.click();
    });
    
    // Search functionality
    categorySearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        
        for (const item of categoryItems) {
            const categoryText = item.textContent.toLowerCase();
            if (searchTerm === '' || categoryText.includes(searchTerm)) {
                item.parentElement.style.display = '';
            } else {
                item.parentElement.style.display = 'none';
            }
        }
    });
    
    // Category selection
    for (const item of categoryItems) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            selectCategory(this);
        });
    }
    
    function selectCategory(item) {
        // Get category details
        const categoryId = item.dataset.id;
        const categoryType = item.dataset.type;
        
        // Get category name without the badge text
        const fullText = item.textContent.trim();
        const categoryNameText = fullText.replace(/(Income|Expense)/i, '').trim();
        
        // Set the hidden input value
        categoryInput.value = categoryId;
        
        // Update the display
        categoryPlaceholder.classList.add('d-none');
        categoryName.textContent = categoryNameText;
        categoryName.classList.remove('d-none');
        
        // Set the badge
        categoryTypeBadge.textContent = categoryType === 'income' ? 'Income' : 'Expense';
        categoryTypeBadge.className = `badge ms-1 bg-${categoryType === 'income' ? 'success' : 'danger'}`;
        categoryTypeBadge.classList.remove('d-none');
        
        // Close dropdown
        const dropdownToggle = document.querySelector('[data-bs-toggle="dropdown"]');
        if (dropdownToggle && typeof bootstrap !== 'undefined') {
            const dropdown = bootstrap.Dropdown.getInstance(dropdownToggle);
            if (dropdown) dropdown.hide();
        }
    }
    
    // Form validation
    const form = categoryInput.closest('form');
    form.addEventListener('submit', function(e) {
        if (!categoryInput.value) {
            e.preventDefault();
            categoryDisplay.classList.add('is-invalid');
        } else {
            categoryDisplay.classList.remove('is-invalid');
        }
    });
});

// Add this to your JavaScript file or within <script> tags
document.addEventListener('DOMContentLoaded', function() {
    // Get DOM elements
    const categorySearch = document.getElementById('category_search');
    const categoryDropdown = document.getElementById('category_dropdown');
    const categoryId = document.getElementById('category_id');
    const categoryItems = document.querySelectorAll('.category-item');

    // Show selected category in search input if already selected
    if (categoryId.value) {
        const selectedItem = document.querySelector(`.category-item[data-id="${categoryId.value}"]`);
        if (selectedItem) {
            categorySearch.value = selectedItem.dataset.name;
            categorySearch.classList.add('selected');
            
            // Set the input background based on category type
            if (selectedItem.dataset.type === 'income') {
                categorySearch.classList.add('bg-light-success');
            } else {
                categorySearch.classList.add('bg-light-danger');
            }
        }
    }

    // Filter categories as user types
    categorySearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        document.querySelectorAll('#category_dropdown .category-item').forEach(function(item) {
            const categoryName = item.dataset.name.toLowerCase();
            const shouldShow = categoryName.includes(searchTerm);
            item.parentElement.style.display = shouldShow ? 'block' : 'none';
        });
    });

    // Handle category selection
    categoryItems.forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Update hidden input with category ID
            categoryId.value = this.dataset.id;
            
            // Update search input with category name
            categorySearch.value = this.dataset.name;
            
            // Add visual indicator
            categorySearch.classList.add('selected');
            
            // Clear any previous type classes
            categorySearch.classList.remove('bg-light-success', 'bg-light-danger');
            
            // Add type-specific styling
            if (this.dataset.type === 'income') {
                categorySearch.classList.add('bg-light-success');
            } else {
                categorySearch.classList.add('bg-light-danger');
            }
            
            // Hide dropdown
            const dropdown = bootstrap.Dropdown.getInstance(categorySearch);
            if (dropdown) dropdown.hide();
        });
    });
});