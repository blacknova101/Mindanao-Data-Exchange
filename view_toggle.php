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
        table-layout: fixed; /* Use fixed layout for better control */
    }
    
    /* Column widths for better control */
    .dataset-table th:nth-child(1),
    .dataset-table td:nth-child(1) {
        width: 35%;
    }
    
    .dataset-table th:nth-child(2),
    .dataset-table td:nth-child(2) {
        width: 15%;
    }
    
    .dataset-table th:nth-child(3),
    .dataset-table td:nth-child(3) {
        width: 20%;
    }
    
    .dataset-table th:nth-child(4),
    .dataset-table td:nth-child(4) {
        width: 30%;
    }
    
    /* Responsive table wrapper */
    .table-responsive-wrapper {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        display: none; /* Hidden by default */
        position: relative; /* Ensure proper positioning */
        box-sizing: border-box; /* Include padding in width calculation */
    }
    
    /* Show table wrapper when view-list class is added to body */
    body.view-list .table-responsive-wrapper {
        display: block !important; /* Force display with !important */
    }

    /* Show table when view-list class is added to body */
    body.view-list .dataset-table {
        display: table !important;
        min-width: 800px; /* Wider to ensure all content is visible */
        table-layout: fixed;
    }
    
    body.view-list .dataset-grid {
        display: none !important;
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
        white-space: nowrap; /* Prevent wrapping */
    }

    /* Download button container */
    .table-download {
        flex: 0 0 100px; /* Fixed width */
        margin-right: 10px;
    }

    /* Table upvote container and button styles */
    .table-upvote {
        display: flex;
        align-items: center;
        flex: 0 0 120px; /* Increased fixed width */
        white-space: nowrap; /* Prevent wrapping */
        min-width: 120px; /* Minimum width to ensure count is visible */
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
        display: inline-block;
    }

    .table-upvote button.upvoted {
        background-color: #f0f0f0;
        box-shadow: inset 0 0 0 1px black;
    }

    .table-upvote-count {
        display: inline-block;
        margin-left: 10px;
        font-weight: bold;
        min-width: 25px; /* Fixed minimum width */
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
    .download-btn, 
    .private-btn {
        width: 90px; /* Fixed width */
        display: inline-block;
        text-align: center;
        box-sizing: border-box;
    }

    /* Responsive styles for mobile devices */
    @media (max-width: 768px) {
        .view-toggle {
            padding-right: 10px;
        }
        
        .table-actions {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }
        
        .table-download {
            margin-bottom: 5px;
        }
        
        .table-upvote {
            flex: 0 0 auto;
        }
        
        .dataset-table th, 
        .dataset-table td {
            padding: 10px 8px;
        }
        
        /* Hide description column on mobile */
        .hide-on-mobile {
            display: none;
        }
    }
    
    @media (max-width: 480px) {
        .view-toggle {
            justify-content: center;
            padding-right: 0;
            width: 100%;
            margin-top: 10px;
            margin-bottom: 15px;
        }
        
        .view-toggle button {
            padding: 5px 8px;
            font-size: 13px;
            flex: 0 1 auto;
            min-width: 90px;
            text-align: center;
        }
        
        .view-toggle button i {
            margin-right: 4px;
        }
        
        .dataset-table {
            font-size: 13px;
        }
        
        /* Improved table display on mobile */
        .table-responsive-wrapper {
            margin: 0 -15px; /* Negative margin to allow full-width scrolling */
            padding: 0 15px;
            position: relative;
            width: 100vw; /* Full viewport width */
            max-width: 100vw;
            box-sizing: border-box;
            overflow-x: auto;
        }
        
        /* Force table display in list view on mobile */
        body.view-list .table-responsive-wrapper {
            display: block !important;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        body.view-list .dataset-table {
            display: table !important;
            min-width: 800px; /* Wider to ensure all content is visible */
            table-layout: fixed;
        }
        
        body.view-list .dataset-grid {
            display: none !important;
        }
        
        /* Adjust column widths for mobile */
        .dataset-table th:nth-child(1),
        .dataset-table td:nth-child(1) {
            width: 30%;
        }
        
        .dataset-table th:nth-child(2),
        .dataset-table td:nth-child(2) {
            width: 15%;
        }
        
        .dataset-table th:nth-child(3),
        .dataset-table td:nth-child(3) {
            width: 20%;
        }
        
        .dataset-table th:nth-child(4),
        .dataset-table td:nth-child(4) {
            width: 35%; /* Wider for actions column */
        }
        
        /* Improve table actions layout on mobile */
        .table-actions {
            flex-direction: row;
            align-items: center;
            flex-wrap: nowrap;
            gap: 10px;
            justify-content: flex-start;
            white-space: nowrap;
            width: 100%;
        }
        
        /* Make download and private buttons smaller */
        .download-btn, 
        .private-btn {
            width: auto;
            padding: 6px 10px;
            min-width: 80px;
            margin-right: 15px;
        }
        
        /* Improve upvote button and count layout */
        .table-upvote {
            display: flex;
            align-items: center;
            white-space: nowrap;
            min-width: 120px; /* Ensure enough space for button and count */
        }
        
        .table-upvote button {
            width: auto;
            padding: 6px 8px;
            min-width: 70px;
        }
        
        .table-upvote-count {
            display: inline-block;
            margin-left: 10px;
            min-width: 20px;
            text-align: center;
            font-weight: bold;
        }
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
        const tableWrapper = document.querySelector('.table-responsive-wrapper');
        const listView = document.querySelector('.dataset-table');
        const cardBtn = document.getElementById('card-view-btn');
        const listBtn = document.getElementById('list-view-btn');
        
        if (viewType === 'card') {
            document.body.classList.remove('view-list');
            cardBtn.classList.add('active');
            listBtn.classList.remove('active');
            
            // Ensure grid is visible and table is hidden
            if (cardView) cardView.style.display = '';
            if (tableWrapper) tableWrapper.style.display = 'none';
            if (listView) listView.style.display = 'none';
        } else {
            document.body.classList.add('view-list');
            listBtn.classList.add('active');
            cardBtn.classList.remove('active');
            
            // Ensure table is visible and grid is hidden
            if (cardView) cardView.style.display = 'none';
            if (tableWrapper) tableWrapper.style.display = 'block';
            if (listView) listView.style.display = 'table';
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
                    const tableUpvoteBtn = document.querySelector(`.table-upvote[data-id="${datasetId}"] button`);
                    const tableCountSpan = document.querySelector(`.table-upvote[data-id="${datasetId}"] .table-upvote-count`);
                    
                    // Check if the button is already upvoted
                    const isUpvoted = tableUpvoteBtn.classList.contains('upvoted');
                    
                    // Toggle the class based on the upvote state
                    if (isUpvoted) {
                        tableUpvoteBtn.classList.remove('upvoted');
                        tableUpvoteBtn.textContent = '⬆ Upvote';
                    } else {
                        tableUpvoteBtn.classList.add('upvoted');
                        tableUpvoteBtn.textContent = '⬆ Upvoted';
                    }
                    
                    // Make the API call
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