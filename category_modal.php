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
.modal {
    display: none; /* Default to hidden */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.4);
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

h2 {
    color: #cfd9ff;
}

.modal-content {
    background-color: #0c1a36;
    padding: 20px;
    border-radius: 10px;
    min-width: 300px;
    max-height: 80vh; /* Limiting modal's height */
    overflow: hidden; /* Hide overflow in the modal */
    padding-right: 20px; /* Adds space for the scrollbar */
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
    background: #e3f2fd;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s;
}

.category-grid div:hover {
    background: #bbdefb;
}

.close-btn {
    background: red;
    color: white;
    padding: 5px 10px;
    border: none;
    cursor: pointer;
    border-radius: 5px;
    margin-top: 10px;
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
