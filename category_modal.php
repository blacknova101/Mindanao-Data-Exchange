<style>.modal {
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
        h2{
            color: #cfd9ff;
        }
        .modal-content {
            background-color: #0c1a36;
            padding: 20px;
            border-radius: 10px;
            min-width: 300px;
        }
        .category-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 20px;
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
                <div onclick="selectCategory('Business & Finance')">Business & Finance</div>
                <div onclick="selectCategory('Education & Academia')">Education & Academia</div>
                <div onclick="selectCategory('Science & Research')">Science & Research</div>
                <div onclick="selectCategory('Agriculture & Environment')">Agriculture & Environment</div>
                <div onclick="selectCategory('Technology & IT')">Technology & IT</div>
                <div onclick="selectCategory('Government & Public Data')">Government & Public Data</div>
                <div onclick="selectCategory('Geography & Mapping')">Geography & Mapping</div>
                <div onclick="selectCategory('Commerce & Consumer Data')">Commerce & Consumer Data</div>
                <div onclick="selectCategory('Social & Media')">Social & Media</div>
                <div onclick="selectCategory('Health & Medicine')">Health & Medicine</div>
            </div>
            <button class="close-btn" onclick="hideModal()">Close</button>
        </div>
    </div>

</div>
<script>
function selectCategory(category) {
    // Redirect to the page with the selected category in the URL
    window.location.href = 'datasetsbycategory.php?category=' + encodeURIComponent(category);
}

function hideModal() {
    // Close the modal by hiding it
    document.getElementById('categoryModal').style.display = 'none';
}

// To show the modal (you can trigger this with a button or automatically on page load)
function showModal() {
    document.getElementById('categoryModal').style.display = 'flex';
}

// You can call `showModal()` whenever you want to display the modal.
</script>
