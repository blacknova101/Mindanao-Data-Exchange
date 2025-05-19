<!-- Dataset Preview Modal -->
<div id="previewModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <div class="preview-header">
            <h2 id="preview-title">Dataset Preview</h2>
            <p id="preview-description"></p>
            <div class="preview-meta">
                <div class="meta-item">
                    <strong>File Type:</strong> <span id="preview-file-type"></span>
                </div>
                <div class="meta-item">
                    <button id="preview-download-btn" class="download-btn">Download Dataset</button>
                </div>
            </div>
        </div>
        <div class="preview-loading" id="preview-loading">
            <div class="spinner"></div>
            <p>Loading preview...</p>
        </div>
        <div class="preview-error" id="preview-error">
            <i class="fas fa-exclamation-circle"></i>
            <p id="preview-error-message">Failed to load preview data.</p>
        </div>
        <div class="preview-container" id="preview-container">
            <table class="preview-table">
                <thead id="preview-headers">
                    <!-- Headers will be inserted here -->
                </thead>
                <tbody id="preview-data">
                    <!-- Data rows will be inserted here -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.7);
    justify-content: center;
    align-items: center;
}

.modal-content {
    background-color: #fefefe;
    margin: auto;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    width: 80%;
    max-width: 1000px;
    max-height: 80vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    animation: modalFadeIn 0.3s ease;
}

@keyframes modalFadeIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.2s;
}

.close:hover {
    color: #555;
}

/* Preview Header Styles */
.preview-header {
    margin-bottom: 20px;
    border-bottom: 1px solid #eee;
    padding-bottom: 15px;
}

.preview-header h2 {
    margin-top: 0;
    color: #0099ff;
}

.preview-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
}

.meta-item {
    margin-right: 20px;
}

.download-btn {
    background-color: #0099ff;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.2s;
}

.download-btn:hover {
    background-color: #007acc;
}

/* Preview Container Styles */
.preview-container {
    overflow-x: auto;
    flex-grow: 1;
    max-height: calc(80vh - 150px);
}

.preview-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.preview-table th {
    background-color: #f5f5f5;
    padding: 10px;
    text-align: left;
    font-weight: bold;
    position: sticky;
    top: 0;
    z-index: 10;
    border-bottom: 2px solid #ddd;
}

.preview-table td {
    padding: 8px 10px;
    border-bottom: 1px solid #eee;
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.preview-table tr:nth-child(even) {
    background-color: #f9f9f9;
}

.preview-table tr:hover {
    background-color: #f1f1f1;
}

/* Loading Indicator */
.preview-loading {
    display: none;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
}

.spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #0099ff;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin-bottom: 15px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Error Message */
.preview-error {
    display: none;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
    color: #721c24;
}

.preview-error i {
    font-size: 40px;
    margin-bottom: 15px;
    color: #dc3545;
}
</style>

<script>
function showPreviewModal(datasetId) {
    // Show the modal with loading state
    document.getElementById('previewModal').style.display = 'flex';
    document.getElementById('preview-title').innerText = 'Loading preview...';
    document.getElementById('preview-description').innerText = '';
    document.getElementById('preview-file-type').innerText = '';
    document.getElementById('preview-headers').innerHTML = '';
    document.getElementById('preview-data').innerHTML = '';
    
    // Show loading indicator, hide error and content
    document.getElementById('preview-loading').style.display = 'flex';
    document.getElementById('preview-error').style.display = 'none';
    document.getElementById('preview-container').style.display = 'none';
    
    // Set up download button
    document.getElementById('preview-download-btn').onclick = function() {
        window.location.href = 'download_dataset.php?dataset_id=' + datasetId;
    };
    
    // Fetch preview data
    fetch('preview_dataset.php?dataset_id=' + datasetId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Hide loading indicator
            document.getElementById('preview-loading').style.display = 'none';
            
            if (!data.success) {
                // Show error message
                document.getElementById('preview-error').style.display = 'flex';
                document.getElementById('preview-error-message').innerText = data.error || 'Failed to load preview data. Please try again later.';
                return;
            }
            
            // Update modal content
            document.getElementById('preview-title').innerText = data.title || 'Dataset Preview';
            document.getElementById('preview-description').innerText = data.description || '';
            document.getElementById('preview-file-type').innerText = data.file_type.toUpperCase() || '';
            
            // Generate table headers
            let headerHtml = '<tr>';
            data.headers.forEach(header => {
                headerHtml += `<th>${header}</th>`;
            });
            headerHtml += '</tr>';
            document.getElementById('preview-headers').innerHTML = headerHtml;
            
            // Generate table rows
            let rowsHtml = '';
            data.data.forEach(row => {
                rowsHtml += '<tr>';
                row.forEach(cell => {
                    rowsHtml += `<td>${cell !== null ? cell : ''}</td>`;
                });
                rowsHtml += '</tr>';
            });
            document.getElementById('preview-data').innerHTML = rowsHtml;
            
            // Show the preview container
            document.getElementById('preview-container').style.display = 'block';
        })
        .catch(error => {
            // Hide loading indicator and show error
            document.getElementById('preview-loading').style.display = 'none';
            document.getElementById('preview-error').style.display = 'flex';
            document.getElementById('preview-error-message').innerText = 'Failed to load preview data. Please try again later.';
            console.error('Error fetching preview data:', error);
        });
}

// Close the modal when clicking the close button or outside the modal
document.querySelector('.close').addEventListener('click', function() {
    document.getElementById('previewModal').style.display = 'none';
});

window.addEventListener('click', function(event) {
    if (event.target == document.getElementById('previewModal')) {
        document.getElementById('previewModal').style.display = 'none';
    }
});
</script> 