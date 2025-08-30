<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Add Product Modal - Fashion Inventory</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-gold: #d4af37;
            --secondary-gold: #f4e4bc;
            --dark-bg: #1a1a1a;
            --light-dark: #2d2d2d;
            --card-bg: #ffffff;
            --text-dark: #1a1a1a;
            --text-muted: #666;
            --border-light: #e8e8e8;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d1b2e 25%, #1a1a1a 50%, #2e2420 75%, #1a1a1a 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            padding: 2rem;
        }

        .demo-button {
            position: fixed;
            top: 30px;
            right: 30px;
            z-index: 1000;
        }

        .btn-gold {
            background: linear-gradient(135deg, var(--primary-gold), var(--secondary-gold));
            color: var(--dark-bg);
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(212, 175, 55, 0.4);
            color: var(--dark-bg);
        }

        .btn-outline-gold {
            background: transparent;
            color: var(--primary-gold);
            border: 2px solid var(--primary-gold);
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline-gold:hover {
            background: var(--primary-gold);
            color: white;
        }

        /* Modal Styling */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-gold), var(--secondary-gold));
            color: var(--dark-bg);
            border-radius: 20px 20px 0 0;
            border: none;
            padding: 1.5rem 2rem;
        }

        .modal-title {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1.3rem;
        }

        .modal-body {
            padding: 2rem;
            background: rgba(255, 255, 255, 0.98);
        }

        .modal-footer {
            border: none;
            padding: 1rem 2rem 2rem;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 0 0 20px 20px;
        }

        /* Form Styling */
        .form-label {
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-control, .form-select {
            background: rgba(248, 248, 248, 0.9);
            border: 2px solid var(--border-light);
            border-radius: 12px;
            padding: 0.8rem 1rem;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }

        .form-control:focus, .form-select:focus {
            background: white;
            border-color: var(--primary-gold);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
            outline: none;
        }

        .required {
            color: var(--danger-color);
        }

        .input-group-text {
            background: rgba(212, 175, 55, 0.1);
            border: 2px solid var(--border-light);
            border-radius: 12px 0 0 12px;
            color: var(--text-dark);
            font-weight: 600;
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 12px 12px 0;
        }

        .input-group:focus-within .input-group-text {
            border-color: var(--primary-gold);
        }

        /* Image Preview */
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 12px;
            margin-top: 10px;
            display: none;
            border: 2px solid rgba(212, 175, 55, 0.2);
            object-fit: cover;
        }

        /* Size Selection */
        .size-selection {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }

        .size-option {
            padding: 0.5rem 1rem;
            border: 2px solid var(--border-light);
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .size-option:hover, .size-option.selected {
            border-color: var(--primary-gold);
            background: rgba(212, 175, 55, 0.1);
            color: var(--text-dark);
        }

        /* Color Selection */
        .color-selection {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }

        .color-option {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 3px solid transparent;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .color-option.selected {
            border-color: var(--primary-gold);
            transform: scale(1.1);
        }

        /* Form Validation */
        .is-invalid {
            border-color: var(--danger-color) !important;
        }

        .invalid-feedback {
            color: var(--danger-color);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        /* Form Sections */
        .form-section {
            background: rgba(248, 248, 248, 0.5);
            padding: 1.5rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(212, 175, 55, 0.1);
        }

        .form-section h6 {
            font-family: 'Playfair Display', serif;
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid rgba(212, 175, 55, 0.2);
        }

        /* Check boxes styling */
        .form-check-input:checked {
            background-color: var(--primary-gold);
            border-color: var(--primary-gold);
        }

        .form-check-input:focus {
            box-shadow: 0 0 0 0.2rem rgba(212, 175, 55, 0.25);
        }

        .form-check-label {
            font-weight: 500;
            color: var(--text-dark);
        }

        /* Progress indicator */
        .progress-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding: 0 1rem;
        }

        .progress-step {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .progress-step::before {
            content: '';
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--border-light);
            display: block;
            margin: 0 auto 0.5rem;
            transition: all 0.3s ease;
        }

        .progress-step.active::before {
            background: var(--primary-gold);
        }

        .progress-step span {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .progress-step.active span {
            color: var(--primary-gold);
            font-weight: 600;
        }
    </style>
</head>

<body>
    <button class="btn btn-gold demo-button" data-bs-toggle="modal" data-bs-target="#addProductModal">
        <i class="fas fa-plus me-2"></i>Add New Product
    </button>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Add New Product
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Progress Indicator -->
                    <div class="progress-indicator">
                        <div class="progress-step active">
                            <span>Basic Info</span>
                        </div>
                        <div class="progress-step">
                            <span>Pricing & Stock</span>
                        </div>
                        <div class="progress-step">
                            <span>Details</span>
                        </div>
                    </div>

                    <form id="addProductForm">
                        <!-- Basic Information Section -->
                        <div class="form-section">
                            <h6><i class="fas fa-info-circle me-2"></i>Basic Information</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Product Name <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="product_name" id="product_name" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">SKU <span class="required">*</span></label>
                                        <input type="text" class="form-control" name="sku" id="sku" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Category <span class="required">*</span></label>
                                        <select class="form-select" name="category" id="category" required>
                                            <option value="">Select Category</option>
                                            <option value="mens">Men's Clothing</option>
                                            <option value="womens">Women's Clothing</option>
                                            <option value="footwear">Footwear</option>
                                            <option value="accessories">Accessories</option>
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Brand</label>
                                        <input type="text" class="form-control" name="brand" id="brand" placeholder="e.g., Nike, Zara, Gucci">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" id="description" rows="4" placeholder="Detailed product description..."></textarea>
                            </div>
                        </div>

                        <!-- Pricing & Inventory Section -->
                        <div class="form-section">
                            <h6><i class="fas fa-dollar-sign me-2"></i>Pricing & Inventory</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Regular Price <span class="required">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" name="price" id="price" step="0.01" min="0" required>
                                        </div>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Sale Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" name="sale_price" id="sale_price" step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Cost Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" name="cost_price" id="cost_price" step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Stock Quantity <span class="required">*</span></label>
                                        <input type="number" class="form-control" name="quantity" id="quantity" min="0" required>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Low Stock Alert</label>
                                        <input type="number" class="form-control" name="min_stock" id="min_stock" min="0" placeholder="Minimum quantity">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status" id="status">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                            <option value="draft">Draft</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Product Details Section -->
                        <div class="form-section">
                            <h6><i class="fas fa-cogs me-2"></i>Product Details</h6>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Available Sizes</label>
                                        <div class="size-selection">
                                            <div class="size-option" data-size="XS">XS</div>
                                            <div class="size-option" data-size="S">S</div>
                                            <div class="size-option" data-size="M">M</div>
                                            <div class="size-option" data-size="L">L</div>
                                            <div class="size-option" data-size="XL">XL</div>
                                            <div class="size-option" data-size="XXL">XXL</div>
                                        </div>
                                        <input type="hidden" name="sizes" id="sizes">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Available Colors</label>
                                        <div class="color-selection">
                                            <div class="color-option" data-color="Black" style="background-color: #000000;"></div>
                                            <div class="color-option" data-color="White" style="background-color: #FFFFFF; border: 1px solid #ccc;"></div>
                                            <div class="color-option" data-color="Red" style="background-color: #EF4444;"></div>
                                            <div class="color-option" data-color="Blue" style="background-color: #3B82F6;"></div>
                                            <div class="color-option" data-color="Green" style="background-color: #10B981;"></div>
                                            <div class="color-option" data-color="Pink" style="background-color: #EC4899;"></div>
                                            <div class="color-option" data-color="Gold" style="background-color: #D4AF37;"></div>
                                        </div>
                                        <input type="hidden" name="colors" id="colors">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Product Image</label>
                                        <input type="file" class="form-control" name="product_image" id="product_image" accept="image/*">
                                        <img id="imagePreview" class="image-preview" alt="Image preview">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Tags</label>
                                        <input type="text" class="form-control" name="tags" id="tags" placeholder="e.g., trendy, summer, casual">
                                        <small class="text-muted">Separate tags with commas</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Weight (kg)</label>
                                        <input type="number" class="form-control" name="weight" id="weight" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Material</label>
                                        <input type="text" class="form-control" name="material" id="material" placeholder="e.g., Cotton, Silk, Leather">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Season</label>
                                        <select class="form-select" name="season" id="season">
                                            <option value="">Select Season</option>
                                            <option value="spring">Spring</option>
                                            <option value="summer">Summer</option>
                                            <option value="fall">Fall</option>
                                            <option value="winter">Winter</option>
                                            <option value="all-season">All Season</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured">
                                            <label class="form-check-label" for="is_featured">
                                                <i class="fas fa-star text-warning me-1"></i>Featured Product
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="track_inventory" id="track_inventory" checked>
                                            <label class="form-check-label" for="track_inventory">
                                                <i class="fas fa-boxes text-info me-1"></i>Track Inventory
                                            </label>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="allow_backorder" id="allow_backorder">
                                            <label class="form-check-label" for="allow_backorder">
                                                <i class="fas fa-clock text-primary me-1"></i>Allow Backorder
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-gold" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-gold" id="saveProduct">
                        <i class="fas fa-save me-2"></i>Save Product
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview functionality
        document.getElementById('product_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('imagePreview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });

        // Auto-generate SKU based on product name
        document.getElementById('product_name').addEventListener('input', function(e) {
            const productName = e.target.value;
            if (productName && document.getElementById('sku').value === '') {
                const sku = productName
                    .toUpperCase()
                    .replace(/[^A-Z0-9]/g, '')
                    .substring(0, 6) + '-' + Math.floor(Math.random() * 1000).toString().padStart(3, '0');
                document.getElementById('sku').value = sku;
            }
        });

        // Size selection functionality
        document.querySelectorAll('.size-option').forEach(option => {
            option.addEventListener('click', function() {
                this.classList.toggle('selected');
                updateSizes();
            });
        });

        function updateSizes() {
            const selectedSizes = Array.from(document.querySelectorAll('.size-option.selected'))
                .map(option => option.dataset.size);
            document.getElementById('sizes').value = selectedSizes.join(',');
        }

        // Color selection functionality
        document.querySelectorAll('.color-option').forEach(option => {
            option.addEventListener('click', function() {
                this.classList.toggle('selected');
                updateColors();
            });
        });

        function updateColors() {
            const selectedColors = Array.from(document.querySelectorAll('.color-option.selected'))
                .map(option => option.dataset.color);
            document.getElementById('colors').value = selectedColors.join(',');
        }

        // Form validation
        function validateForm() {
            const requiredFields = ['product_name', 'sku', 'category', 'price', 'quantity'];
            let isValid = true;
            
            requiredFields.forEach(field => {
                const input = document.getElementById(field);
                const value = input.value.trim();
                
                if (!value) {
                    input.classList.add('is-invalid');
                    const feedback = input.parentNode.querySelector('.invalid-feedback') || 
                                   input.nextElementSibling;
                    if (feedback && feedback.classList.contains('invalid-feedback')) {
                        feedback.textContent = 'This field is required';
                    }
                    isValid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            return isValid;
        }

        // Calculate profit margin
        function calculateProfitMargin() {
            const price = parseFloat(document.getElementById('price').value) || 0;
            const costPrice = parseFloat(document.getElementById('cost_price').value) || 0;
            
            if (price > 0 && costPrice > 0) {
                const margin = ((price - costPrice) / price * 100).toFixed(2);
                console.log(`Profit margin: ${margin}%`);
                
                // You could display this in the UI
                // For now, just logging to console
            }
        }

        document.getElementById('price').addEventListener('input', calculateProfitMargin);
        document.getElementById('cost_price').addEventListener('input', calculateProfitMargin);

        // Clear validation on input
        document.querySelectorAll('.form-control, .form-select').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
            });
        });

        // Form submission
        document.getElementById('saveProduct').addEventListener('click', function() {
            if (validateForm()) {
                const form = document.getElementById('addProductForm');
                const formData = new FormData(form);
                
                // Add selected sizes and colors
                formData.set('sizes', document.getElementById('sizes').value);
                formData.set('colors', document.getElementById('colors').value);
                
                // Add checkboxes
                formData.set('is_featured', document.getElementById('is_featured').checked);
                formData.set('track_inventory', document.getElementById('track_inventory').checked);
                formData.set('allow_backorder', document.getElementById('allow_backorder').checked);
                
                // Convert to object for display
                const productData = {};
                for (let [key, value] of formData.entries()) {
                    productData[key] = value;
                }
                
                console.log('Product data to save:', productData);
                
                // Show success message
                alert('✅ Product saved successfully!\n\nIn a real application, this would be sent to your backend API.');
                
                // Close modal and reset form
                const modal = bootstrap.Modal.getInstance(document.getElementById('addProductModal'));
                modal.hide();
                resetForm();
            } else {
                alert('❌ Please fill in all required fields correctly.');
            }
        });

        // Reset form function
        function resetForm() {
            document.getElementById('addProductForm').reset();
            document.getElementById('imagePreview').style.display = 'none';
            document.querySelectorAll('.size-option.selected').forEach(option => {
                option.classList.remove('selected');
            });
            document.querySelectorAll('.color-option.selected').forEach(option => {
                option.classList.remove('selected');
            });
            document.querySelectorAll('.is-invalid').forEach(input => {
                input.classList.remove('is-invalid');
            });
            document.getElementById('sizes').value = '';
            document.getElementById('colors').value = '';
        }

        // Reset form when modal is closed
        document.getElementById('addProductModal').addEventListener('hidden.bs.modal', function() {
            resetForm();
        });

        // Progress indicator simulation (you could expand this for multi-step form)
        document.querySelectorAll('.form-control, .form-select').forEach(input => {
            input.addEventListener('input', updateProgress);
        });

        function updateProgress() {
            const requiredFields = ['product_name', 'sku', 'category'];
            const pricingFields = ['price', 'quantity'];
            
            const basicComplete = requiredFields.every(field => 
                document.getElementById(field).value.trim() !== ''
            );
            const pricingComplete = pricingFields.every(field => 
                document.getElementById(field).value.trim() !== ''
            );
            
            const steps = document.querySelectorAll('.progress-step');
            
            // Reset all steps
            steps.forEach(step => step.classList.remove('active'));
            
            // Activate completed steps
            steps[0].classList.add('active'); // Always show first step as active
            if (basicComplete) {
                steps[1].classList.add('active');
            }
            if (basicComplete && pricingComplete) {
                steps[2].classList.add('active');
            }
        }