<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'unauthorized']);
    exit();
}

$dataset_id = $_POST['dataset_id'];
$user_id = $_SESSION['user_id'];

// Check if the user has already upvoted
$sql = "SELECT * FROM datasetratings WHERE dataset_id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $dataset_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    // User has already upvoted, so unvote
    $sql = "DELETE FROM datasetratings WHERE dataset_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $dataset_id, $user_id);
    mysqli_stmt_execute($stmt);

    // Get the updated upvote count
    $sql = "SELECT COUNT(*) AS upvotes FROM datasetratings WHERE dataset_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $dataset_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $upvotes = mysqli_fetch_assoc($result)['upvotes'];

    echo json_encode(['status' => 'unvoted', 'upvotes' => $upvotes]);
} else {
    // User hasn't upvoted, so upvote
    $sql = "INSERT INTO datasetratings (dataset_id, user_id) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ii', $dataset_id, $user_id);
    mysqli_stmt_execute($stmt);

    // Get the updated upvote count
    $sql = "SELECT COUNT(*) AS upvotes FROM datasetratings WHERE dataset_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $dataset_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $upvotes = mysqli_fetch_assoc($result)['upvotes'];

    echo json_encode(['status' => 'voted', 'upvotes' => $upvotes]);
}
?>
