<!-- dataset_layout.php - Common layout for all dataset listing pages -->
<!-- All styling for this component is defined in assets/css/datasets_styles.css -->
<?php
// The following variables should be set before including this file:
// $page_title - Title of the page (e.g., "Available Datasets", "My Datasets", etc.)
// $result - The mysqli result set containing the datasets to display
// $current_filter - The current visibility filter
// $filter_base_url - Base URL for the filter buttons
// $conn - Database connection
// $total_count - Total notification count

// Function to fix newlines in descriptions
function fixNewlines($text) {
    if (!$text) return '';
    
    // First, normalize all newlines to a standard format
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    
    // Handle literal escaped newline sequences
    $text = str_replace(['\\r\\n', '\\n', '\\r'], "\n", $text);
    
    // Handle literal "\r\n" as text
    $text = str_replace(['\r\n', '\n', '\r'], "\n", $text);
    
    return $text;
}
?>

<style>
    /* Fixed styles to override problematic CSS */
    .dataset-card {
        display: flex;
        flex-direction: column;
        position: relative;
        height: auto !important;
        min-height: 250px;
    }
    
    .dataset-title {
        display: flex;
        flex-direction: column;
    }
    
    .dataset-description {
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        word-break: break-word;
        max-height: 65px;
    }
    
    .dataset-uploader {
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .dataset-actions {
        display: flex;
        width: 100%;
        margin-top: auto !important;
        padding-top: 10px !important;
        justify-content: space-between;
        align-items: center;
        flex-wrap: nowrap;
    }
    
    .dataset-download {
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0;
        margin-right: 23px;
    }
    
    .dataset-upvote {
        display: flex;
        align-items: center;
        white-space: nowrap;
        justify-content: center;
    }
    
    .dataset-upvote button {
        width: 80px !important;
        margin: 0;
        height: 34px;
        line-height: 20px;
        box-sizing: border-box;
        padding: 5px 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
    }
    
    .dataset-upvote span {
        margin-left: 5px;
        min-width: 20px;
        text-align: center;
    }
    
    .download-btn {
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        margin: 0 !important;
        padding: 5px 12px !important;
        height: 34px !important;
        line-height: 20px;
        box-sizing: border-box;
        text-align: center;
        white-space: nowrap;
    }
    
    .actions-right-container {
        display: flex;
        align-items: center;
        gap: 10px;
        justify-content: flex-end;
    }
    
    .dataset-analytics {
        display: flex;
        gap: 8px;
        flex-shrink: 0;
        margin-right: auto;
        align-items: center;
    }
    
    .dataset-actions-buttons {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .edit-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 34px;
        padding: 5px 12px;
        box-sizing: border-box;
    }
    
    .badge-container {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        margin-top: 5px;
    }
    
    .category-badge {
        display: inline-block;
        max-width: 100%;
        word-break: break-word;
        white-space: normal;
        overflow-wrap: break-word;
        line-height: 1.2;
    }
    
    /* Table view improvements */
    .dataset-table {
        table-layout: fixed;
        width: 100%;
    }
    
    .dataset-table th, 
    .dataset-table td {
        word-wrap: break-word;
        overflow-wrap: break-word;
    }
    
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
    
    /* Table wrapper for horizontal scrolling in responsive mode */
    .table-responsive-wrapper {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .dataset-table td a {
        display: block;
        max-width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* Title cell styling - using specific selector for title links only */
    .dataset-table td:first-child a {
        color: #0066cc;
        font-weight: 500;
        text-decoration: none;
        padding: 2px 0;
    }
    
    .dataset-table td:first-child a:hover {
        text-decoration: underline;
        color: #004c99;
    }
    
    .dataset-table td:first-child {
        position: relative;
        padding-right: 10px;
    }
    
    /* Badge positioning in table */
    .dataset-table .visibility-badge {
        margin-top: 4px;
        display: inline-block;
        font-size: 11px;
        padding: 2px 6px;
        border-radius: 3px;
    }
    
    @media (max-width: 480px) {
        .dataset-actions {
            flex-wrap: wrap;
        }
        
        .dataset-analytics {
            width: 100%;
            margin-bottom: 10px;
        }
        
        .dataset-download, .dataset-upvote {
            margin-top: 0px;
        }

        .dataset-upvote {
            margin-top: -20px;
            margin-left: 20px;
        }

        .category-badge {
            font-size: 10px;
            padding: 3px 6px;
        }
        
        .visibility-badge {
            font-size: 10px;
            padding: 3px 6px;
        }
        
        /* Keep the table columns at fixed widths for scrolling */
        .dataset-table {
            min-width: 700px; /* Ensure table is wide enough to require scrolling */
        }
        
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
            width: 25%;
        }
        
        .dataset-table th:nth-child(4),
        .dataset-table td:nth-child(4) {
            width: 30%;
        }
        
        /* Improve table styling on mobile */
        .table-responsive-wrapper {
            margin: 0 -15px; /* Negative margin to allow full-width scrolling */
            padding: 0 15px;
            position: relative;
        }
        
        .table-actions {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: 5px;
        }
    }
</style>

<div class="controls-wrapper">
    <div class="filter-group">
        <div class="filter-label">
            <i class="fa-solid fa-filter"></i> VISIBILITY
        </div>
        <div class="filter-options">
            <a href="<?= $filter_base_url ?>" class="filter-btn <?= $current_filter == '' ? 'active' : '' ?>">All</a>
            <a href="<?= $filter_base_url . (strpos($filter_base_url, '?') !== false ? '&' : '?') . 'visibility=Public' ?>" class="filter-btn <?= $current_filter == 'Public' ? 'active' : '' ?>">Public</a>
            <a href="<?= $filter_base_url . (strpos($filter_base_url, '?') !== false ? '&' : '?') . 'visibility=Private' ?>" class="filter-btn <?= $current_filter == 'Private' ? 'active' : '' ?>">Private</a>
        </div>
    </div>
    <?php include 'view_toggle.php'; ?>
</div>

<!-- Card View -->
<div class="dataset-grid">
    <?php if (mysqli_num_rows($result) > 0): ?>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <?php
                // Get common variables
                $batch_id = isset($row['dataset_batch_id']) ? $row['dataset_batch_id'] : null;
                $analytics = function_exists('get_batch_analytics') ? get_batch_analytics($conn, $batch_id) : ['total_views' => 0, 'total_downloads' => 0];
                $is_private_unowned = isset($row['visibility']) && isset($row['user_id']) ? 
                    ($row['visibility'] == 'Private' && $row['user_id'] != $_SESSION['user_id']) : false;
                
                // Handle different naming conventions between files
                $dataset_id = isset($row['dataset_id']) ? $row['dataset_id'] : null;
                $title = isset($row['title']) ? $row['title'] : (isset($row['dataset_title']) ? $row['dataset_title'] : '');
                $description = isset($row['description']) ? fixNewlines($row['description']) : (isset($row['dataset_description']) ? fixNewlines($row['dataset_description']) : '');
                $category_name = isset($row['category_name']) ? $row['category_name'] : '';
                $upvotes = isset($row['upvotes']) ? $row['upvotes'] : 0;
                $user_upvoted = isset($row['user_upvoted']) ? $row['user_upvoted'] : 0;
                
                // Determine detail page URL
                $detail_page = isset($is_my_datasets) && $is_my_datasets ? 'mydataset.php' : 'dataset.php';
            ?>
            <div class="dataset-card">
                <div class="dataset-title">
                    <a href="<?= $detail_page ?>?id=<?= $dataset_id ?>&title=<?= urlencode($title) ?>" title="<?= htmlspecialchars($title) ?>">
                        <?= htmlspecialchars($title) ?>
                    </a>
                    <div class="badge-container">
                        <?php if(isset($row['visibility'])): ?>
                        <span class="visibility-badge <?= strtolower($row['visibility']) ?>">
                            <?= $row['visibility'] ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($category_name)): ?>
                        <span class="category-badge">
                            <?= htmlspecialchars($category_name) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="dataset-description">
                    <?= htmlspecialchars(mb_strimwidth($description, 0, 180, '...')) ?>
                </div>
                <div class="dataset-uploader">
                    <span class="uploader-name">Uploaded by: <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></span>
                </div>
                <div class="dataset-actions">
                    <div class="dataset-analytics">
                        <span class="analytics-item" title="Views">
                            <i class="fa-regular fa-eye"></i>
                            <?= $analytics['total_views'] ?>
                        </span>
                        <span class="analytics-item" title="Downloads">
                            <i class="fa-solid fa-download"></i>
                            <?= $analytics['total_downloads'] ?>
                        </span>
                    </div>
                    <div class="actions-right-container">
                    <div class="dataset-download">
                        <?php 
                            $has_access = function_exists('hasApprovedAccess') ? hasApprovedAccess($conn, $dataset_id, $_SESSION['user_id']) : false;
                            if (!$is_private_unowned || $has_access): 
                        ?>
                            <a href="download_batch.php?batch_id=<?= $batch_id ?>" class="download-btn">Download</a>
                        <?php else: ?>
                            <span class="download-btn private-btn" style="background-color: #ccc; cursor: not-allowed;">Private</span>
                        <?php endif; ?>
                    </div>
                    <?php if (isset($is_my_datasets) && $is_my_datasets): ?>
                    <div class="dataset-actions-buttons">
                        <a href="edit_dataset.php?id=<?= $dataset_id ?>" class="edit-btn">Edit</a>
                        <div class="dataset-upvote" data-id="<?= $dataset_id ?>">
                            <button class="<?= $user_upvoted == 1 ? 'upvoted' : '' ?>" onclick="upvoteDataset(<?= $dataset_id ?>)">
                                <?= $user_upvoted == 1 ? '⬆ Upvoted' : '⬆ Upvote' ?>
                            </button>
                            <span id="upvote-count-<?= $dataset_id ?>"><?= $upvotes ?></span>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="dataset-upvote" data-id="<?= $dataset_id ?>">
                        <button class="<?= $user_upvoted == 1 ? 'upvoted' : '' ?>" onclick="upvoteDataset(<?= $dataset_id ?>)">
                            <?= $user_upvoted == 1 ? '⬆ Upvoted' : '⬆ Upvote' ?>
                        </button>
                        <span id="upvote-count-<?= $dataset_id ?>"><?= $upvotes ?></span>
                    </div>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>

<!-- Table View -->
<div class="table-responsive-wrapper">
    <table class="dataset-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Analytics</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            // Reset the result pointer to the beginning
            mysqli_data_seek($result, 0);
            
            if (mysqli_num_rows($result) > 0): 
                while ($row = mysqli_fetch_assoc($result)): 
                    // Get common variables
                    $batch_id = isset($row['dataset_batch_id']) ? $row['dataset_batch_id'] : null;
                    $analytics = function_exists('get_batch_analytics') ? get_batch_analytics($conn, $batch_id) : ['total_views' => 0, 'total_downloads' => 0];
                    $is_private_unowned = isset($row['visibility']) && isset($row['user_id']) ? 
                        ($row['visibility'] == 'Private' && $row['user_id'] != $_SESSION['user_id']) : false;
                    
                    // Handle different naming conventions between files
                    $dataset_id = isset($row['dataset_id']) ? $row['dataset_id'] : null;
                    $title = isset($row['title']) ? $row['title'] : (isset($row['dataset_title']) ? $row['dataset_title'] : '');
                    $description = isset($row['description']) ? fixNewlines($row['description']) : (isset($row['dataset_description']) ? fixNewlines($row['dataset_description']) : '');
                    $category_name = isset($row['category_name']) ? $row['category_name'] : '';
                    $upvotes = isset($row['upvotes']) ? $row['upvotes'] : 0;
                    $user_upvoted = isset($row['user_upvoted']) ? $row['user_upvoted'] : 0;
                    
                    // Determine detail page URL
                    $detail_page = isset($is_my_datasets) && $is_my_datasets ? 'mydataset.php' : 'dataset.php';
            ?>
                <tr>
                    <td>
                        <a href="<?= $detail_page ?>?id=<?= $dataset_id ?>&title=<?= urlencode($title) ?>" title="<?= htmlspecialchars($title) ?>">
                            <?= htmlspecialchars($title) ?>
                        </a>
                        <?php if(isset($row['visibility'])): ?>
                        <span class="visibility-badge <?= strtolower($row['visibility']) ?>">
                            <?= $row['visibility'] ?>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($category_name)): ?>
                            <span class="category-badge">
                                <?= htmlspecialchars($category_name) ?>
                            </span>
                        <?php else: ?>
                            <span class="category-badge">Uncategorized</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="table-analytics">
                            <span class="analytics-item" title="Views">
                                <i class="fa-regular fa-eye"></i> <?= $analytics['total_views'] ?>
                            </span>
                            <span class="analytics-item" title="Downloads">
                                <i class="fa-solid fa-download"></i> <?= $analytics['total_downloads'] ?>
                            </span>
                            <span class="analytics-item" title="Upvotes">
                                <i class="fa-solid fa-arrow-up"></i> <?= $upvotes ?>
                            </span>
                        </div>
                    </td>
                    <td>
                        <div class="table-actions">
                            <div class="table-download">
                                <?php 
                                    $has_access = function_exists('hasApprovedAccess') ? hasApprovedAccess($conn, $dataset_id, $_SESSION['user_id']) : false;
                                    if (!$is_private_unowned || $has_access): 
                                ?>
                                    <a href="download_batch.php?batch_id=<?= $batch_id ?>" class="download-btn">Download</a>
                                <?php else: ?>
                                    <span class="download-btn private-btn" style="background-color: #ccc; cursor: not-allowed;">Private</span>
                                <?php endif; ?>
                            </div>
                            <?php if (isset($is_my_datasets) && $is_my_datasets): ?>
                            <a href="edit_dataset.php?id=<?= $dataset_id ?>" class="edit-btn">Edit</a>
                            <?php endif; ?>
                            <div class="table-upvote" data-id="<?= $dataset_id ?>">
                                <button class="<?= $user_upvoted == 1 ? 'upvoted' : '' ?>" onclick="upvoteDataset(<?= $dataset_id ?>)">
                                    <?= $user_upvoted == 1 ? '⬆ Upvoted' : '⬆ Upvote' ?>
                                </button>
                                <span class="table-upvote-count"><?= $upvotes ?></span>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php 
                endwhile; 
            endif; 
            ?>
        </tbody>
    </table>
</div>

<!-- No datasets found message outside of the grid -->
<?php if (mysqli_num_rows($result) == 0): ?>
    <div class="no-datasets">
        <img src="images/no-found1.png" alt="No data" class="no-found-img">
        <p>No dataset found.</p>
    </div>
<?php endif; ?> 