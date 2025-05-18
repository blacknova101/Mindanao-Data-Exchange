<?php
session_start();
include 'db_connection.php';

// Include session update to ensure organization_id is synchronized
include 'update_session.php';

// Now check login
if (!isset($_SESSION['user_id'])) {
    header("Location: unauthorized.php");
    exit();
}
// Check if valid files exist in the session
if (!isset($_SESSION['valid_files']) || empty($_SESSION['valid_files'])) {
    $_SESSION['error_message'] = "No valid files found. Please upload files first.";
    header("Location: uploadselection.php");
    exit();
}

// Fetch categories from the datasetcategories table
$query = "SELECT * FROM datasetcategories";
$result = mysqli_query($conn, $query);

if (!$result) {
    $_SESSION['error_message'] = "Error fetching categories: " . mysqli_error($conn);
    header("Location: uploadselection.php");
    exit();
}

$validFiles = $_SESSION['valid_files'];

// Get saved form data if available
$formData = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
$validationErrors = isset($_SESSION['validation_errors']) ? $_SESSION['validation_errors'] : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fill Dataset Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        html, body {
            height: 100%;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fc;
            margin: 0;
            padding: 0;
            color: #333;
        }
        
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 5%;
            padding-left: 30px;
            background-color: #0099ff;
            color: #ffffff;
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            position: relative;
            margin: 10px 0;
            max-width: 1327px;
            width: 95%;
            margin-top: 30px;
            margin-left: auto;
            margin-right: auto;
            font-weight: bold;
            box-sizing: border-box;
        }
        
        .logo {
            display: flex;
            align-items: center;
        }
        
        .logo img {
            height: auto;
            width: 80px;
            max-width: 100%;
            margin-right: 15px;
        }
        
        .nav-links a {
            color: white;
            margin-left: 20px;
            text-decoration: none;
            font-size: 18px;
            transition: transform 0.3s ease;
        }
        
        .nav-links a:hover {
            transform: scale(1.2);
        }

        h1 {
            color: #0c1a36;
            margin-bottom: 25px;
            font-size: 26px;
            text-align: center;
        }

        h2 {
            color: #0c1a36;
            margin: 0;
            font-size: 22px;
        }

        h3 {
            color: #0099ff;
            margin-bottom: 15px;
            font-size: 20px;
            font-weight: 600;
        }

        .error-message {
            color: #ff3d3d;
            background-color: #ffecec;
            border-left: 4px solid #ff3d3d;
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 16px;
            width: 100%;
            max-width: 600px;
            box-sizing: border-box;
            text-align: left;
        }
        
        .field-error {
            color: #ff3d3d;
            font-size: 13px;
            margin-top: -15px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            animation: shake 0.5s ease-in-out;
        }
        
        .field-error i {
            margin-right: 5px;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        input.error, textarea.error, select.error {
            border-color: #ff3d3d;
            background-color: #fff8f8;
        }

        .main-wrapper {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 20px;
            padding-bottom: 50px;
        }

        .container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
            width: 90%;
            max-width: 1200px;
            margin-top: 20px;
        }

        .file-list {
            flex: 1 1 300px;
            background-color: #fff;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            max-height: 400px;
            overflow-y: auto;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            align-self: flex-start;
        }
        
        .file-list:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.15);
        }

        .file-list ul {
            padding-left: 20px;
            margin-top: 10px;
        }

        .file-item {
            margin: 10px 0;
            font-size: 15px;
            color: #333;
            padding: 8px 12px;
            background-color: #e9f7ff;
            border-radius: 8px;
            list-style-type: none;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
        }
        
        .file-item:hover {
            background-color: #d4eeff;
            transform: translateX(5px);
        }
        
        .file-item:before {
            content: '\f15b';
            font-family: 'Font Awesome 5 Free';
            font-weight: 400;
            margin-right: 10px;
            color: #0099ff;
        }

        form {
            flex: 1 1 500px;
            display: flex;
            flex-direction: column;
            background-color: #fff;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            text-align: left;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            max-width: 800px;
        }
        
        form:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.15);
        }

        label {
            font-size: 16px;
            color: #0c1a36;
            margin-bottom: 8px;
            font-weight: 600;
        }

        input[type="text"],
        input[type="date"],
        input[type="url"],
        textarea,
        select {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            box-sizing: border-box;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        input[type="text"]:focus,
        input[type="date"]:focus,
        input[type="url"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #0099ff;
            box-shadow: 0 0 0 2px rgba(0,153,255,0.2);
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        .submit-button {
            background-color: #0099ff;
            color: white;
            border: none;
            padding: 14px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            max-width: 250px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px rgba(0,153,255,0.2);
            margin: 10px auto 0;
        }

        .submit-button:hover {
            background-color: #007acc;
            box-shadow: 0 6px 8px rgba(0,153,255,0.3);
            transform: translateY(-2px);
        }
        
        .submit-button i {
            margin-right: 10px;
        }

        .flex-row {
            display: flex;
            gap: 20px;
            margin-bottom: 10px;
        }
        
        .flex-col {
            flex: 1;
        }
        
        .section-heading {
            font-size: 18px;
            color: #0099ff;
            margin: 10px 0 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .help-text {
            margin-bottom: 5px;
            font-size: 13px;
            color: #666;
        }
        
        .action-button {
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .success-button {
            background-color: #28a745;
            color: white;
            box-shadow: 0 2px 4px rgba(40,167,69,0.2);
        }
        
        .success-button:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(40,167,69,0.3);
        }
        
        .danger-button {
            background-color: #dc3545;
            color: white;
            box-shadow: 0 2px 4px rgba(220,53,69,0.2);
        }
        
        .danger-button:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(220,53,69,0.3);
        }
        
        .location-entry {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
        }
        
        .location-entry:hover {
            background-color: #f0f7ff;
        }

        #background-video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }
        
        #custom_category_container {
            background-color: #e9f7ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                width: 95%;
            }
            
            .navbar {
                width: 95%;
                padding: 10px 15px;
            }
            
            .file-list, form {
                width: 100%;
                flex: none;
            }
            
            .flex-row {
                flex-direction: column;
                gap: 0;
            }
            
            input[type="text"],
            input[type="date"],
            input[type="url"],
            textarea,
            select {
                font-size: 14px;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <video autoplay muted loop id="background-video">
        <source src="videos/bg6.mp4" type="video/mp4">
    </video>
    
    <header class="navbar">
        <div class="logo">
            <img src="images/mdx_logo.png" alt="Mangasay Data Exchange Logo">
            <h2>Dataset Details</h2>
        </div>
        <nav class="nav-links">
            <a href="HomeLogin.php">HOME</a>
            <a href="datasets.php">DATASETS</a>
        </nav>
    </header>
    
    <div class="main-wrapper">
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message']; ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <div class="container">
            <!-- File list card -->
            <div class="file-list">
                <h3><i class="fas fa-file-alt"></i> Uploaded Files</h3>
                <ul>
                    <?php foreach ($validFiles as $file): ?>
                        <li class="file-item"><?php echo basename($file); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Form card -->
            <form action="save_dataset.php" method="post" id="datasetForm" novalidate>
                <h1>Complete Your Dataset Information</h1>
                
                <div class="section-heading"><i class="fas fa-info-circle"></i> Basic Information</div>
                
                <label for="title">Title:</label>
                <input type="text" name="title" id="title" required placeholder="Enter a descriptive title" 
                       value="<?php echo isset($formData['title']) ? htmlspecialchars($formData['title']) : ''; ?>">
                <div id="title-error" class="field-error" style="display: none;"><i class="fas fa-exclamation-circle"></i> Please enter a title for your dataset</div>

                <label for="description">Description:</label>
                <textarea name="description" id="description" required placeholder="Provide a detailed description of your dataset"><?php echo isset($formData['description']) ? htmlspecialchars($formData['description']) : ''; ?></textarea>
                <div id="description-error" class="field-error" style="display: none;"><i class="fas fa-exclamation-circle"></i> Please provide a description</div>

                <div class="flex-row">
                    <div class="flex-col">
                        <label for="start_period">Start Period:</label>
                        <input type="date" name="start_period" id="start_period" required
                               value="<?php echo isset($formData['start_period']) ? $formData['start_period'] : ''; ?>">
                        <div id="start_period-error" class="field-error" style="display: none;"><i class="fas fa-exclamation-circle"></i> Required</div>
                        <?php if (isset($validationErrors['date'])): ?>
                            <div class="field-error" style="display: flex;"><i class="fas fa-exclamation-circle"></i> <?php echo $validationErrors['date']; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-col">
                        <label for="end_period">End Period:</label>
                        <input type="date" name="end_period" id="end_period" required
                               value="<?php echo isset($formData['end_period']) ? $formData['end_period'] : ''; ?>">
                        <div id="end_period-error" class="field-error" style="display: none;"><i class="fas fa-exclamation-circle"></i> Required</div>
                    </div>
                </div>

                <div class="flex-row">
                    <div class="flex-col">
                        <label for="source">Source:</label>
                        <input type="text" name="source" id="source" required placeholder="Data source or organization"
                               value="<?php echo isset($formData['source']) ? htmlspecialchars($formData['source']) : ''; ?>">
                        <div id="source-error" class="field-error" style="display: none;"><i class="fas fa-exclamation-circle"></i> Please enter the data source</div>
                    </div>
                    <div class="flex-col">
                        <label for="link">Link:</label>
                        <input type="url" name="link" id="link" required placeholder="URL to original source (if available)"
                               value="<?php echo isset($formData['link']) ? htmlspecialchars($formData['link']) : ''; ?>">
                        <div id="link-error" class="field-error" style="display: none;"><i class="fas fa-exclamation-circle"></i> Please enter a valid URL</div>
                    </div>
                </div>
                
                <div class="section-heading"><i class="fas fa-map-marker-alt"></i> Location Information</div>

                <label for="locations">Location in Mindanao:</label>
                <div id="location-container">
                    <div class="location-entry">
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <select name="locations[0][province]" class="province-select" style="flex: 1;" required>
                                <option value="">Select Province</option>
                                <!-- Provinces will be populated via JavaScript -->
                            </select>
                            <select name="locations[0][city]" class="city-select" style="flex: 1;" required disabled>
                                <option value="">Select City/Municipality</option>
                                <!-- Cities will be populated via JavaScript -->
                            </select>
                            <select name="locations[0][barangay]" class="barangay-select" style="flex: 1;" required disabled>
                                <option value="">Select Barangay</option>
                                <!-- Barangays will be populated via JavaScript -->
                            </select>
                        </div>
                        <div id="location-error" class="field-error" style="display: none; margin-top: 5px;">
                            <i class="fas fa-exclamation-circle"></i> Please select a complete location (Province, City/Municipality, and Barangay)
                        </div>
                    </div>
                </div>
                
                <div class="section-heading"><i class="fas fa-tags"></i> Categorization &amp; Visibility</div>

                <label for="category">Category:</label>
                <div class="help-text">
                    <i class="fas fa-info-circle"></i> Can't find your category? Click the "Add New Category" button below.
                </div>
                <?php 
                $savedCategory = isset($formData['category']) ? $formData['category'] : '';
                $isCustomCategory = strpos($savedCategory, 'custom:') === 0;
                $customCategoryName = $isCustomCategory ? substr($savedCategory, 7) : '';
                ?>
                <div style="display: flex; gap: 10px; margin-bottom: 20px; align-items: flex-start;">
                    <div style="flex: 1;">
                        <select name="category_selection" id="category_selection" style="width: 100%; margin-bottom: 0;" <?php echo $isCustomCategory ? 'disabled' : ''; ?>>
                            <option value="">Select Category</option>
                            <?php mysqli_data_seek($result, 0); ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <option value="<?php echo $row['category_id']; ?>" <?php echo (!$isCustomCategory && $savedCategory == $row['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo $row['name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <button type="button" id="add_category_btn" onclick="showCustomCategory()" class="action-button success-button" <?php echo $isCustomCategory ? 'style="display: none;"' : ''; ?>>
                            <i class="fas fa-plus"></i> Add New Category
                        </button>
                    </div>
                </div>
                
                <div id="custom_category_container" style="<?php echo $isCustomCategory ? 'display: block;' : 'display: none;'; ?>">
                    <div style="margin-bottom: 10px; font-size: 14px; color: #0099ff;">
                        <i class="fas fa-info-circle"></i> Creating a new category. Please enter the name below:
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="text" name="custom_category" id="custom_category" placeholder="Enter new category name" style="flex: 1; margin-bottom: 0;" value="<?php echo $customCategoryName; ?>" <?php echo $isCustomCategory ? 'required' : ''; ?>>
                        <button type="button" onclick="cancelCustomCategory()" class="action-button danger-button">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </div>
                
                <?php if (isset($validationErrors['category'])): ?>
                    <div class="field-error" style="display: flex; margin-bottom: 20px;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $validationErrors['category']; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Hidden input to store the final category value (either selected or custom) -->
                <input type="hidden" name="category" id="category" required value="<?php echo $savedCategory; ?>">

                <label for="visibility">Visibility:</label>
                <select name="visibility" id="visibility" required>
                    <option value="public" <?php echo (isset($formData['visibility']) && $formData['visibility'] == 'public') ? 'selected' : ''; ?>>Public</option>
                    <option value="private" <?php echo (isset($formData['visibility']) && $formData['visibility'] == 'private') ? 'selected' : ''; ?>>Private</option>
                </select>

                <button type="submit" class="submit-button">
                    <i class="fas fa-save"></i> Save Dataset
                </button>
            </form>
        </div>
    </div>

<script>
let mindanaoLocations = {};

// Load Mindanao locations data
fetch('mindanao_locations.json')
    .then(response => response.json())
    .then(data => {
        mindanaoLocations = data;
        populateProvinces();
        populateSavedLocation();
    })
    .catch(error => {
        console.error('Error loading location data:', error);
        // Fallback with basic data
        mindanaoLocations = {
            "Davao Region": {
                "Davao City": ["Buhangin", "Panacan", "Poblacion", "Tugbok"],
                "Tagum City": ["Apokon", "Pagsabangan", "San Miguel", "Visayan Village"]
            },
            "Northern Mindanao": {
                "Cagayan de Oro City": ["Carmen", "Gusa", "Lapasan", "Nazareth"],
                "Iligan City": ["Poblacion", "Tibanga", "Pala-o", "Bagong Silang"]
            }
        };
        populateProvinces();
        populateSavedLocation();
    });

function populateProvinces() {
    const provinceSelect = document.querySelector('.province-select');
    provinceSelect.innerHTML = '<option value="">Select Province</option>';
    Object.keys(mindanaoLocations).forEach(province => {
        provinceSelect.innerHTML += `<option value="${province}">${province}</option>`;
    });
}

function populateCities(provinceSelect) {
    const locationEntry = provinceSelect.closest('.location-entry');
    const citySelect = locationEntry.querySelector('.city-select');
    const barangaySelect = locationEntry.querySelector('.barangay-select');
    
    citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
    barangaySelect.disabled = true;
    
    if (provinceSelect.value) {
        citySelect.disabled = false;
        const cities = mindanaoLocations[provinceSelect.value];
        Object.keys(cities).forEach(city => {
            citySelect.innerHTML += `<option value="${city}">${city}</option>`;
        });
    } else {
        citySelect.disabled = true;
    }
}

function populateBarangays(citySelect) {
    const locationEntry = citySelect.closest('.location-entry');
    const provinceSelect = locationEntry.querySelector('.province-select');
    const barangaySelect = locationEntry.querySelector('.barangay-select');
    
    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
    
    if (citySelect.value && provinceSelect.value) {
        barangaySelect.disabled = false;
        const barangays = mindanaoLocations[provinceSelect.value][citySelect.value];
        barangays.forEach(barangay => {
            barangaySelect.innerHTML += `<option value="${barangay}">${barangay}</option>`;
        });
    } else {
        barangaySelect.disabled = true;
    }
}

// Event delegation for selects
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('province-select')) {
        populateCities(e.target);
    } else if (e.target.classList.contains('city-select')) {
        populateBarangays(e.target);
    }
});

// Initialize first province select when page loads
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(populateProvinces, 100);
});

// Function to show custom category input
function showCustomCategory() {
    const categorySelection = document.getElementById('category_selection');
    const customCategoryContainer = document.getElementById('custom_category_container');
    const customCategoryInput = document.getElementById('custom_category');
    const addCategoryBtn = document.getElementById('add_category_btn');
    
    // Show custom category input and focus on it
    customCategoryContainer.style.display = 'block';
    customCategoryInput.focus();
    customCategoryInput.required = true;
    
    // Hide the add category button while in custom category mode
    addCategoryBtn.style.display = 'none';
    
    // Disable the category dropdown
    categorySelection.disabled = true;
    
    // Clear dropdown selection
    categorySelection.value = '';
    
    // Update hidden input to track state
    document.getElementById('category').value = '';
}

// Function to cancel custom category entry
function cancelCustomCategory() {
    const customCategoryContainer = document.getElementById('custom_category_container');
    const customCategoryInput = document.getElementById('custom_category');
    const addCategoryBtn = document.getElementById('add_category_btn');
    const categorySelection = document.getElementById('category_selection');
    
    // Hide custom category input
    customCategoryContainer.style.display = 'none';
    customCategoryInput.required = false;
    customCategoryInput.value = '';
    
    // Show the add category button again
    addCategoryBtn.style.display = 'block';
    
    // Re-enable the category dropdown
    categorySelection.disabled = false;
    
    // Reset the hidden category field to match the dropdown selection
    document.getElementById('category').value = categorySelection.value;
}

// Set the hidden category field when custom category changes
document.getElementById('custom_category').addEventListener('input', function() {
    document.getElementById('category').value = 'custom:' + this.value;
});

// Set the hidden category field when dropdown selection changes
document.getElementById('category_selection').addEventListener('change', function() {
    // Only update if we're not in custom category mode
    if (document.getElementById('custom_category_container').style.display === 'none') {
        document.getElementById('category').value = this.value;
    }
});

// Initialize the category field with the default selection
document.addEventListener('DOMContentLoaded', function() {
    // Set initial hidden category value based on dropdown
    const categorySelection = document.getElementById('category_selection');
    document.getElementById('category').value = categorySelection.value;
});

// Form validation 
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('datasetForm');
    
    form.addEventListener('submit', function(e) {
        let hasErrors = false;
        
        // Clear previous errors
        const errorElements = document.querySelectorAll('.field-error');
        errorElements.forEach(element => {
            element.style.display = 'none';
        });
        
        const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
        inputs.forEach(input => {
            input.classList.remove('error');
            
            // Check for empty required fields
            if (!input.value.trim()) {
                const errorElement = document.getElementById(`${input.id}-error`);
                if (errorElement) {
                    errorElement.style.display = 'flex';
                    input.classList.add('error');
                    hasErrors = true;
                }
            }
            
            // Special validation for URL
            if (input.type === 'url' && input.value.trim()) {
                try {
                    new URL(input.value);
                } catch (error) {
                    const errorElement = document.getElementById(`${input.id}-error`);
                    if (errorElement) {
                        errorElement.style.display = 'flex';
                        input.classList.add('error');
                        hasErrors = true;
                    }
                }
            }
        });
        
        // Check if location fields are filled correctly
        const locationEntry = document.querySelector('.location-entry');
        const provinceSelect = locationEntry.querySelector('.province-select');
        const citySelect = locationEntry.querySelector('.city-select');
        const barangaySelect = locationEntry.querySelector('.barangay-select');
        const locationError = document.getElementById('location-error');
        
        if (!provinceSelect.value || !citySelect.value || !barangaySelect.value) {
            provinceSelect.classList.add('error');
            citySelect.classList.add('error');
            barangaySelect.classList.add('error');
            locationError.style.display = 'flex';
            hasErrors = true;
        }
        
        // Check category selection
        const categoryField = document.getElementById('category');
        if (!categoryField.value) {
            const categorySelection = document.getElementById('category_selection');
            const customCategory = document.getElementById('custom_category');
            categorySelection.classList.add('error');
            if (customCategory.required) customCategory.classList.add('error');
            hasErrors = true;
        }
        
        if (hasErrors) {
            e.preventDefault();
            // Scroll to the first error
            const firstError = document.querySelector('.error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }
        }
    });
});

// Add this at the beginning of the script to populate values from session
document.addEventListener('DOMContentLoaded', function() {
    // Setup error display for validation errors from server
    <?php if (!empty($validationErrors)): ?>
    const inputs = document.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        if (!input.value.trim() && input.required) {
            input.classList.add('error');
            const errorEl = document.getElementById(`${input.id}-error`);
            if (errorEl) {
                errorEl.style.display = 'flex';
            }
        }
    });
    <?php endif; ?>
});

// Add custom function to populate saved location data
function populateSavedLocation() {
    // Get the saved location data from the session
    <?php if (isset($formData['locations']) && !empty($formData['locations'])): ?>
    const savedLocation = {
        province: "<?php echo isset($formData['locations'][0]['province']) ? $formData['locations'][0]['province'] : ''; ?>",
        city: "<?php echo isset($formData['locations'][0]['city']) ? $formData['locations'][0]['city'] : ''; ?>",
        barangay: "<?php echo isset($formData['locations'][0]['barangay']) ? $formData['locations'][0]['barangay'] : ''; ?>"
    };
    
    if (!mindanaoLocations || !savedLocation.province) return;
    
    const provinceSelect = document.querySelector('.province-select');
    const citySelect = document.querySelector('.city-select');
    const barangaySelect = document.querySelector('.barangay-select');
    
    // Set province value
    if (savedLocation.province && mindanaoLocations[savedLocation.province]) {
        provinceSelect.value = savedLocation.province;
        
        // Populate cities based on selected province
        citySelect.disabled = false;
        citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
        Object.keys(mindanaoLocations[savedLocation.province]).forEach(city => {
            citySelect.innerHTML += `<option value="${city}">${city}</option>`;
        });
        
        // Set city value
        if (savedLocation.city && mindanaoLocations[savedLocation.province][savedLocation.city]) {
            citySelect.value = savedLocation.city;
            
            // Populate barangays based on selected city
            barangaySelect.disabled = false;
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            mindanaoLocations[savedLocation.province][savedLocation.city].forEach(barangay => {
                barangaySelect.innerHTML += `<option value="${barangay}">${barangay}</option>`;
            });
            
            // Set barangay value
            if (savedLocation.barangay) {
                barangaySelect.value = savedLocation.barangay;
            }
        }
    }
    <?php endif; ?>
}
</script>
</body>
</html>