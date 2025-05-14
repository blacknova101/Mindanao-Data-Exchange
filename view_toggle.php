<!-- view_toggle.php - Toggle component to switch between card and list views -->
<style>
    .view-toggle {
        display: flex;
        justify-content: flex-end;
        margin: 10px 0;
        padding-right: 20px;
    }

    .view-toggle button {
        background-color: #f0f0f0;
        border: 1px solid #ddd;
        padding: 5px 10px;
        margin-left: 10px;
        cursor: pointer;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }

    .view-toggle button.active {
        background-color: #0099ff;
        color: white;
        border-color: #0088ee;
    }

    .view-toggle button i {
        margin-right: 5px;
    }

    /* Table view styles */
    .dataset-table {
        width: 100%;
        border-collapse: collapse;
        background-color: #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 20px;
        display: none; /* Hidden by default */
    }

    .dataset-table th {
        background-color: #f6f6f6;
        padding: 15px;
        text-align: left;
        font-weight: bold;
        color: #333;
        border-bottom: 1px solid #ddd;
    }

    .dataset-table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
    }

    .dataset-table tr:last-child td {
        border-bottom: none;
    }

    .dataset-table tr:hover {
        background-color: #f9f9f9;
    }

    .category-badge {
        display: inline-block;
        padding: 4px 8px;
        margin: 2px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: bold;
        background-color: #e0e0e0;
        color: #333;
    }

    /* Improved table actions layout */
    .table-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
    }

    /* Download button container */
    .table-download {
        flex: 0 0 100px; /* Fixed width */
        margin-right: 10px;
    }

    /* Upvote container */
    .table-upvote {
        display: flex;
        align-items: center;
        flex: 0 0 110px; /* Fixed width */
    }

    .table-upvote button {
        background-color: white;
        color: #333;
        border: 1px solid #ccc;
        border-radius: 5px;
        padding: 5px 8px;
        cursor: pointer;
        transition: background-color 0.3s ease;
        width: 80px; /* Fixed width */
        text-align: center;
    }

    .table-upvote button.upvoted {
        background-color: #f0f0f0;
        box-shadow: inset 0 0 0 1px black;
    }

    .table-upvote-count {
        display: inline-block;
        margin-left: 5px;
        font-weight: bold;
        width: 25px; /* Fixed width */
        text-align: center;
    }

    /* Table analytics */
    .table-analytics {
        display: flex;
        gap: 10px;
    }

    .table-analytics .analytics-item {
        display: flex;
        align-items: center;
        color: #888;
    }

    .table-analytics .analytics-item i {
        margin-right: 4px;
    }

    /* Visibility badges in table */
    .table-visibility {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: bold;
    }

    .table-visibility.public {
        background-color: #4CAF50;
        color: white;
    }

    .table-visibility.private {
        background-color: #f44336;
        color: white;
    }
    
    /* Consistent download button width */
    .download-btn {
        width: 90px; /* Fixed width */
        display: inline-block;
        text-align: center;
    }
</style>

<div class="view-toggle">
    <button id="card-view-btn" class="active" onclick="toggleView('card')">
        <i class="fa-solid fa-th-large"></i> Card View
    </button>
    <button id="list-view-btn" onclick="toggleView('list')">
        <i class="fa-solid fa-list"></i> List View
    </button>
</div>

<script>
    // Function to toggle between card and list views
    function toggleView(viewType) {
        const cardView = document.querySelector('.dataset-grid');
        const listView = document.querySelector('.dataset-table');
        const cardBtn = document.getElementById('card-view-btn');
        const listBtn = document.getElementById('list-view-btn');
        
        if (viewType === 'card') {
            cardView.style.display = 'grid';
            listView.style.display = 'none';
            cardBtn.classList.add('active');
            listBtn.classList.remove('active');
        } else {
            cardView.style.display = 'none';
            listView.style.display = 'table';
            listBtn.classList.add('active');
            cardBtn.classList.remove('active');
        }
        
        // Save the user's preference to localStorage
        localStorage.setItem('datasetViewPreference', viewType);
    }
    
    // When the page loads, check for saved preference
    document.addEventListener('DOMContentLoaded', function() {
        const savedView = localStorage.getItem('datasetViewPreference');
        if (savedView) {
            toggleView(savedView);
        }
        
        // Override the existing upvoteDataset function if it exists
        if (typeof window.upvoteDataset !== 'undefined') {
            window.originalUpvoteDataset = window.upvoteDataset;
            
            // Replace with our enhanced version
            window.upvoteDataset = function(datasetId) {
                // Check if we're in card view or list view
                const cardView = document.querySelector('.dataset-grid');
                const isCardView = (window.getComputedStyle(cardView).display !== 'none');
                
                if (isCardView) {
                    // Card view - use the original selectors
                    const upvoteButton = document.querySelector(`[data-id="${datasetId}"] button`);
                    const countSpan = document.getElementById(`upvote-count-${datasetId}`);
                    
                    // Check if the button is already upvoted (has 'upvoted' class)
                    const isUpvoted = upvoteButton.classList.contains('upvoted');
                    
                    // Toggle the class based on the upvote state
                    if (isUpvoted) {
                        upvoteButton.classList.remove('upvoted');
                        upvoteButton.textContent = '⬆ Upvote'; // Change text to 'Upvote' when unvoted
                    } else {
                        upvoteButton.classList.add('upvoted');
                        upvoteButton.textContent = '⬆ Upvoted'; // Change text to 'Upvoted' when clicked
                    }
                    
                    // Make the API call to update the upvote
                    fetch('upvote.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `dataset_id=${datasetId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        let current = parseInt(countSpan.textContent);
                        
                        if (data.status === 'voted' && !isUpvoted) {
                            countSpan.textContent = current + 1;
                        } else if (data.status === 'unvoted' && isUpvoted) {
                            countSpan.textContent = current - 1;
                        } else if (data.status === 'unauthorized') {
                            alert('You must be logged in to upvote.');
                        } else {
                            alert('Something went wrong.');
                        }
                    });
                } else {
                    // List view - use table selectors
                    const tableUpvoteButton = document.querySelector(`.table-upvote[data-id="${datasetId}"] button`);
                    const tableCountSpan = document.querySelector(`.table-upvote[data-id="${datasetId}"] .table-upvote-count`);
                    
                    if (!tableUpvoteButton || !tableCountSpan) {
                        console.error('Could not find upvote elements for dataset ID', datasetId);
                        return;
                    }
                    
                    // Check if the button is already upvoted
                    const isUpvoted = tableUpvoteButton.classList.contains('upvoted');
                    
                    // Toggle the class based on the upvote state
                    if (isUpvoted) {
                        tableUpvoteButton.classList.remove('upvoted');
                        tableUpvoteButton.textContent = '⬆ Upvote';
                    } else {
                        tableUpvoteButton.classList.add('upvoted');
                        tableUpvoteButton.textContent = '⬆ Upvoted';
                    }
                    
                    // Make the API call to update the upvote
                    fetch('upvote.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `dataset_id=${datasetId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        let current = parseInt(tableCountSpan.textContent);
                        
                        if (data.status === 'voted' && !isUpvoted) {
                            tableCountSpan.textContent = current + 1;
                        } else if (data.status === 'unvoted' && isUpvoted) {
                            tableCountSpan.textContent = current - 1;
                        } else if (data.status === 'unauthorized') {
                            alert('You must be logged in to upvote.');
                        } else {
                            alert('Something went wrong.');
                        }
                    });
                }
            };
        }
    });
</script> 