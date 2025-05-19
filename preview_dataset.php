<?php
session_start();
include 'db_connection.php';
include 'includes/error_handler.php';
include 'includes/path_handler.php';

// Set headers to prevent caching and set content type
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

try {
    // Check if dataset_id is provided
    if (!isset($_GET['dataset_id'])) {
        throw new Exception("Dataset ID is required");
    }
    
    $dataset_id = (int)$_GET['dataset_id'];
    
    // Get dataset information
    $sql = "SELECT d.*, dv.file_path 
            FROM datasets d 
            JOIN datasetversions dv ON d.dataset_batch_id = dv.dataset_batch_id 
            WHERE d.dataset_id = ? AND dv.is_current = 1";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $dataset_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Dataset not found");
    }
    
    $dataset = $result->fetch_assoc();
    
    // Get the file path
    $file_path = $dataset['file_path'];
    
    // Convert to absolute path if necessary
    if (strpos($file_path, '/') !== 0 && strpos($file_path, ':\\') !== 1) {
        $file_path = get_absolute_path($file_path);
    }
    
    // Check if file exists
    if (!file_exists($file_path)) {
        log_error("Dataset file not found", ERROR_FILE, [
            'dataset_id' => $dataset_id,
            'file_path' => $file_path
        ]);
        throw new Exception("Dataset file not found");
    }
    
    // Get file extension
    $file_extension = get_file_extension($file_path);
    
    // Prepare response data
    $response = [
        'success' => true,
        'title' => $dataset['title'],
        'description' => $dataset['description'],
        'file_type' => $file_extension,
        'headers' => [],
        'data' => []
    ];
    
    // Process file based on extension
    switch ($file_extension) {
        case 'csv':
            // Process CSV file
            if (($handle = fopen($file_path, "r")) !== FALSE) {
                // Try to detect if the file has a BOM (Byte Order Mark) for UTF-8
                $bom = fread($handle, 3);
                if ($bom !== "\xEF\xBB\xBF") {
                    // If not a BOM, reset the file pointer
                    rewind($handle);
                }
                
                // Read headers
                if (($data = fgetcsv($handle, 4096, ",")) !== FALSE) {
                    $response['headers'] = $data;
                    
                    // Read up to 10 rows of data
                    $count = 0;
                    while (($data = fgetcsv($handle, 4096, ",")) !== FALSE && $count < 10) {
                        $response['data'][] = $data;
                        $count++;
                    }
                }
                
                fclose($handle);
            } else {
                throw new Exception("Failed to open CSV file");
            }
            break;
            
        case 'json':
            // Process JSON file
            $json_string = file_get_contents($file_path);
            $json_data = json_decode($json_string, true);
            
            if ($json_data === null) {
                throw new Exception("Invalid JSON file");
            }
            
            // Handle different JSON structures
            if (is_array($json_data) && !empty($json_data)) {
                if (isset($json_data[0]) && is_array($json_data[0])) {
                    // Array of objects/arrays
                    $first_item = $json_data[0];
                    
                    // Extract headers from first item
                    $response['headers'] = array_keys($first_item);
                    
                    // Get up to 10 rows of data
                    for ($i = 0; $i < min(10, count($json_data)); $i++) {
                        $response['data'][] = array_values($json_data[$i]);
                    }
                } else {
                    // Single object
                    $response['headers'] = array_keys($json_data);
                    $response['data'][] = array_values($json_data);
                }
            }
            break;
            
        case 'xlsx':
        case 'xls':
            // Process Excel file - requires PhpSpreadsheet
            if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                throw new Exception("PhpSpreadsheet library is required to preview Excel files");
            }
            
            require 'vendor/autoload.php';
            
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file_path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file_path);
            $worksheet = $spreadsheet->getActiveSheet();
            
            $highestRow = min($worksheet->getHighestRow(), 11); // Get up to 11 rows (1 header + 10 data)
            $highestColumn = $worksheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
            
            // Get headers (first row)
            $headers = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $headers[] = $worksheet->getCellByColumnAndRow($col, 1)->getValue();
            }
            $response['headers'] = $headers;
            
            // Get data (up to 10 rows)
            for ($row = 2; $row <= $highestRow; $row++) {
                $rowData = [];
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $rowData[] = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
                }
                $response['data'][] = $rowData;
            }
            break;
            
        case 'xml':
            // Process XML file
            $xml = simplexml_load_file($file_path);
            if ($xml === false) {
                throw new Exception("Invalid XML file");
            }
            
            // Convert XML to array
            $json = json_encode($xml);
            $array = json_decode($json, true);
            
            // Try to find repeating elements (common in XML data)
            $data_array = null;
            foreach ($array as $key => $value) {
                if (is_array($value) && isset($value[0])) {
                    $data_array = $value;
                    break;
                }
            }
            
            if ($data_array !== null) {
                // We found a repeating element
                $response['headers'] = array_keys($data_array[0]);
                
                // Get up to 10 rows of data
                for ($i = 0; $i < min(10, count($data_array)); $i++) {
                    $response['data'][] = array_values($data_array[$i]);
                }
            } else {
                // No repeating elements found, use the root element
                $response['headers'] = array_keys($array);
                $response['data'][] = array_values($array);
            }
            break;
            
        default:
            throw new Exception("Unsupported file type: " . $file_extension);
    }
    
    // Log successful preview
    log_error("Dataset preview generated", "dataset", [
        'dataset_id' => $dataset_id,
        'file_type' => $file_extension
    ]);
    
    // Return the response as JSON
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log the error
    log_error("Dataset preview error", ERROR_GENERAL, [
        'exception' => $e->getMessage(),
        'dataset_id' => $dataset_id ?? null,
        'trace' => $e->getTraceAsString()
    ]);
    
    // Return error response
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 