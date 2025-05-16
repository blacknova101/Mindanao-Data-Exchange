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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fill Dataset Details</title>
    <style>
        html, body {
            height: 100%;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            text-align: center;
        }
        .navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 5%; /* Adjusted padding for a more compact navbar */
            padding-left: 30px;
            background-color: #0099ff; /* Transparent background */
            color: #cfd9ff;
            border-radius: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            position: relative;
            margin: 10px 0;
            backdrop-filter: blur(10px);
            max-width: 1200px; /* Limit the maximum width */
            width: 100%; /* Ensure it takes up the full width but doesn't exceed 1200px */
            margin-top:30px;
            margin-left: auto; /* Center align the navbar */
            margin-right: auto; /* Center align the navbar */
            font-weight: bold;
        }
        .logo {
        display: flex;
        align-items: center;
        }
        .logo img {
            height: auto;
            width: 80px; /* Adjust logo size */
            max-width: 100%;
        }
        .nav-links a {
            color: white;
            margin-left: 20px;
            text-decoration: none;
            font-size: 18px;
            transition: transform 0.3s ease; /* Smooth transition for scaling */
        }
        .nav-links a:hover {
            transform: scale(1.2); /* Scale up on hover */
        }


        h1 {
            color: #0099ff;
            margin-bottom: 20px;
            font-size: 28px;
        }

        h3 {
            color: #0099ff;
            text-align: left;
            margin-bottom: 10px;
            font-size: 20px;
        }

        .error-message {
            color: red;
            margin-bottom: 20px;
            font-size: 16px;
        }

        .main-wrapper {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 20px;
        }

        .container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center; /* <- changed from space-between to center */
            align-items: flex-start;
            gap: 20px;
            width: 90%;
            max-width: 1200px;
        }


        .file-list {
            flex: 1 1 40%;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid #0099ff;
            max-height: 300px;
            overflow-y: auto;
        }

        .file-item {
            margin: 5px 0;
            font-size: 14px;
            color: #333;
        }

        .file-item a {
            color: #0099ff;
            text-decoration: none;
        }

        .file-item a:hover {
            text-decoration: underline;
        }

        form {
            flex: 1 1 55%;
            display: flex;
            flex-direction: column;
            background-color: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid #0099ff;
            text-align:left;
        }

        label {
            font-size: 16px;
            color: #333;
            margin-bottom: 8px;
        }

        input[type="text"],
        input[type="date"],
        input[type="url"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }

        textarea {
            resize: vertical;
            height: 100px;
        }

        button {
            background-color: #0099ff;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #007acc;
        }
        #background-video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1; /* stays behind everything */
        }
        #error{
            text-align:center;
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
        <h2>Upload Datasets</h2>
    </div>
    <nav class="nav-links">
        <a href="HomeLogin.php">HOME</a>
    </nav>
</header>
<div class="main-wrapper">
    <div class="container">
        <!-- File list on the left -->
        <div class="file-list">
        <h3>Uploaded Files:</h3>
            <ul>
                <?php foreach ($validFiles as $file): ?>
                    <li class="file-item"><?php echo basename($file); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

    <form action="save_dataset.php" method="post">
     <div id="error">  
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="error-message"><?php echo $_SESSION['error_message']; ?></div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    </div> 
    <h1>Dataset Details</h1>
    <label for="title">Title:</label>
    <input type="text" name="title" id="title" required>

    <label for="description">Description:</label>
    <textarea name="description" id="description" required></textarea>

    <div style="display: flex; gap: 20px;">
        <div style="flex: 1;">
            <label for="start_period">Start Period:</label>
            <input type="date" name="start_period" id="start_period" required>
        </div>
        <div style="flex: 1;">
            <label for="end_period">End Period:</label>
            <input type="date" name="end_period" id="end_period" required>
        </div>
    </div>

    <div style="display: flex; gap: 20px;">
        <div style="flex: 1;">
            <label for="source">Source:</label>
            <input type="text" name="source" id="source" required>
        </div>
        <div style="flex: 1;">
            <label for="link">Link:</label>
            <input type="url" name="link" id="link" required>
        </div>
    </div>

    <label for="locations">Location(s) in Mindanao:</label>
    <div id="location-container">
        <div class="location-entry">
            <div style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                <select name="locations[0][province]" class="province-select" style="flex: 1;" required>
                    <option value="">Select Province</option>
                </select>
                <select name="locations[0][city]" class="city-select" style="flex: 1;" required disabled>
                    <option value="">Select City/Municipality</option>
                </select>
                <select name="locations[0][barangay]" class="barangay-select" style="flex: 1;" required disabled>
                    <option value="">Select Barangay</option>
                </select>
                <button type="button" class="remove-location" onclick="removeLocation(this)" style="background-color: #dc3545; padding: 10px; border-radius: 5px; color: white; border: none; cursor: pointer; display: none;">Remove</button>
            </div>
        </div>
    </div>
    <button type="button" id="add-location" onclick="addLocation()" style="background-color: #28a745; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; margin-bottom: 20px;">Add More Location</button>

    <label for="category">Category:</label>
    <select name="category" id="category" required>
        <option value="">Select Category</option>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <option value="<?php echo $row['category_id']; ?>"><?php echo $row['name']; ?></option>
        <?php endwhile; ?>
    </select>


    <label for="visibility">Visibility:</label>
    <select name="visibility" id="visibility" required>
        <option value="public">Public</option>
        <option value="private">Private</option>
    </select>

    <button type="submit">Add Dataset</button>
    </form>
    </div>
</div>

<script>
let locationCount = 1;
let mindanaoLocations = {};

// Load Mindanao locations data
fetch('mindanao_locations.json')
    .then(response => response.json())
    .then(data => {
        mindanaoLocations = data;
        populateProvinces();
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
    });

function populateProvinces() {
    const provinceSelects = document.querySelectorAll('.province-select');
    provinceSelects.forEach(select => {
        select.innerHTML = '<option value="">Select Province</option>';
        Object.keys(mindanaoLocations).forEach(province => {
            select.innerHTML += `<option value="${province}">${province}</option>`;
        });
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

function addLocation() {
    const container = document.getElementById('location-container');
    const locationEntry = document.createElement('div');
    locationEntry.className = 'location-entry';
    
    locationEntry.innerHTML = `
        <div style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
            <select name="locations[${locationCount}][province]" class="province-select" style="flex: 1;" required>
                <option value="">Select Province</option>
            </select>
            <select name="locations[${locationCount}][city]" class="city-select" style="flex: 1;" required disabled>
                <option value="">Select City/Municipality</option>
            </select>
            <select name="locations[${locationCount}][barangay]" class="barangay-select" style="flex: 1;" required disabled>
                <option value="">Select Barangay</option>
            </select>
            <button type="button" class="remove-location" onclick="removeLocation(this)" style="background-color: #dc3545; padding: 10px; border-radius: 5px; color: white; border: none; cursor: pointer;">Remove</button>
        </div>
    `;
    
    container.appendChild(locationEntry);
    locationCount++;
    
    // Populate provinces for the new entry
    const newProvinceSelect = locationEntry.querySelector('.province-select');
    Object.keys(mindanaoLocations).forEach(province => {
        newProvinceSelect.innerHTML += `<option value="${province}">${province}</option>`;
    });
    
    // Show remove button for all entries
    updateRemoveButtons();
}

function removeLocation(button) {
    const locationEntry = button.closest('.location-entry');
    locationEntry.remove();
    updateRemoveButtons();
}

function updateRemoveButtons() {
    const removeButtons = document.querySelectorAll('.remove-location');
    const locationEntries = document.querySelectorAll('.location-entry');
    
    removeButtons.forEach((button, index) => {
        button.style.display = locationEntries.length > 1 ? 'block' : 'none';
    });
}

// Event delegation for dynamically added selects
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
</script>
</body>
</html>