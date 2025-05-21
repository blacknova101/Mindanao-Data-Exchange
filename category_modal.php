<?php
include 'db_connection.php';

// Fetch categories from the datasetcategories table
$query = "SELECT category_id, name FROM datasetcategories";
$result = $conn->query($query);

// Initialize categories array
$categories = [];

// Check if categories exist
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>
<style>
/* Category Modal specific styles */
#categoryModal {
    display: none; /* Default to hidden */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

#categoryModal h2 {
    color: #0099ff;
    margin-top: 0;
    margin-bottom: 20px;
    font-size: 24px;
    text-align: center;
}

#categoryModal .modal-content {
    background-color: #fff;
    padding: 30px;
    border-radius: 16px;
    width: 450px; /* Fixed width */
    max-width: 90%;
    max-height: 80vh; /* Limiting modal's height */
    overflow: hidden; /* Hide overflow in the modal */
    border: 2px solid #0099ff;
    box-shadow: 0 0 20px rgba(0, 153, 255, 0.2);
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.category-search {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    margin-bottom: 15px;
    font-size: 16px;
    box-sizing: border-box;
}

.category-search-container {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
}

.category-search-input {
    flex-grow: 1;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px 0 0 5px;
    font-size: 16px;
    box-sizing: border-box;
}

.category-search-input:focus {
    outline: none;
    border-color: #0099ff;
    box-shadow: 0 0 5px rgba(0, 153, 255, 0.3);
}

.category-search-button {
    background: #0099ff;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 0 5px 5px 0;
    cursor: pointer;
    font-size: 16px;
}

.category-search-button:hover {
    background: #007acc;
}

.category-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-top: 20px;
    max-height: 400px; /* Set max height */
    overflow-y: auto; /* Enable vertical scrolling */
    padding-right: 15px; /* Adjusts the space from the scrollbar */
}

.category-grid div {
    padding: 10px;
    background: #f0f8ff;
    border: 1px solid #0099ff;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s;
    color: #333;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 44px; /* Fixed height for uniformity */
    font-size: 16px;
}

.category-grid div:hover {
    background: #0099ff;
    color: white;
}

#categoryModal .close-btn {
    background: #fff;
    color: #0099ff;
    padding: 10px 20px;
    border: 1.5px solid #0099ff;
    cursor: pointer;
    border-radius: 5px;
    margin-top: 20px;
    font-weight: bold;
    transition: all 0.3s;
    display: block;
    margin-left: auto;
    margin-right: auto;
}

#categoryModal .close-btn:hover {
    background: #0099ff;
    color: white;
}
</style>

<div id="categoryModal" class="modal" onclick="hideModal()">
    <div class="modal-content" onclick="event.stopPropagation();">
        <h2>Select a Category</h2>
        <div class="category-search-container">
            <input type="text" class="category-search-input" placeholder="Search categories...">
            <button class="category-search-button"><i class="fa-solid fa-search"></i> Search</button>
        </div>
        <div class="category-grid">
            <?php if (empty($categories)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 20px; color: #666;">
                    No categories found.
                </div>
            <?php else: ?>
                <?php foreach ($categories as $category): ?>
                    <div onclick="selectCategory('<?php echo $category['category_id']; ?>')">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <button class="close-btn" onclick="hideModal()">Close</button>
    </div>
</div>

<script>
function selectCategory(category_id) {
    // Get the current page URL
    const currentPath = window.location.pathname;
    
    // If on my_search_results.php, filter by category on that page
    if (currentPath.includes('my_search_results.php')) {
        // Get the category name instead of ID for my_search_results.php
        const categoryName = event.target.textContent.trim();
        window.location.href = 'my_search_results.php?category=' + encodeURIComponent(categoryName);
    } else {
        // For all other pages, including search_results.php, redirect to datasetsbycategory.php
        window.location.href = 'datasetsbycategory.php?category_id=' + encodeURIComponent(category_id);
    }
}

function hideModal() {
    // Close the modal by hiding it
    document.getElementById('categoryModal').style.display = 'none';
}

// To show the modal (you can trigger this with a button or automatically on page load)
function showModal() {
    document.getElementById('categoryModal').style.display = 'flex';
    
    // Clear search input when modal is opened
    const searchInput = document.querySelector('.category-search-input');
    if (searchInput) {
        searchInput.value = '';
        // Reset visibility of all category items
        const categoryItems = document.querySelectorAll('.category-grid div');
        categoryItems.forEach(item => {
            item.style.display = 'flex';
        });
    }
}

function searchCategories() {
    const searchInput = document.querySelector('.category-search-input');
    const searchText = searchInput.value.toLowerCase();
    const categoryItems = document.querySelectorAll('.category-grid div');
    
    categoryItems.forEach(function(item) {
        const categoryName = item.textContent.trim().toLowerCase();
        if (categoryName.includes(searchText)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

// Add search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('.category-search-input');
    const searchButton = document.querySelector('.category-search-button');
    
    if (searchInput) {
        // Real-time filtering as user types
        searchInput.addEventListener('input', searchCategories);
        
        // Also filter when user presses Enter
        searchInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchCategories();
            }
        });
    }
    
    if (searchButton) {
        searchButton.addEventListener('click', searchCategories);
    }
});
</script>
