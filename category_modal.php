<?php
include 'db_connection.php';

// Fetch categories from the datasetcategories table
$query = "SELECT category_id, name FROM datasetcategories";
$result = $conn->query($query);

// Check if categories exist
if ($result->num_rows > 0) {
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
} else {
    echo "No categories found.";
    exit();
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
        <div class="category-grid">
            <?php foreach ($categories as $category): ?>
                <div onclick="selectCategory('<?php echo $category['category_id']; ?>')">
                    <?php echo htmlspecialchars($category['name']); ?>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="close-btn" onclick="hideModal()">Close</button>
    </div>
</div>

<script>
function selectCategory(category_id) {
    // Redirect to the page with the selected category_id in the URL
    window.location.href = 'datasetsbycategory.php?category_id=' + encodeURIComponent(category_id);
}

function hideModal() {
    // Close the modal by hiding it
    document.getElementById('categoryModal').style.display = 'none';
}

// To show the modal (you can trigger this with a button or automatically on page load)
function showModal() {
    document.getElementById('categoryModal').style.display = 'flex';
}
</script>
