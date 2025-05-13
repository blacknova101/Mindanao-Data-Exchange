<?php
// batch_analytics.php

function increment_batch_views($conn, $batch_id) {
    $sql = "INSERT INTO dataset_batch_analytics (dataset_batch_id, total_views, last_accessed)
            VALUES (?, 1, NOW())
            ON DUPLICATE KEY UPDATE
                total_views = total_views + 1,
                last_accessed = NOW()";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $batch_id);
        $stmt->execute();
        $stmt->close();
    }
}

function increment_batch_downloads($conn, $batch_id) {
    $sql = "INSERT INTO dataset_batch_analytics (dataset_batch_id, total_downloads, last_accessed)
            VALUES (?, 1, NOW())
            ON DUPLICATE KEY UPDATE
                total_downloads = total_downloads + 1,
                last_accessed = NOW()";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $batch_id);
        $stmt->execute();
        $stmt->close();
    }
}

function get_batch_analytics($conn, $batch_id) {
    $analytics = [
        'total_downloads' => 0,
        'total_views' => 0
    ];
    $sql = "SELECT total_downloads, total_views FROM dataset_batch_analytics WHERE dataset_batch_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $batch_id);
        $stmt->execute();
        $stmt->bind_result($total_downloads, $total_views);
        if ($stmt->fetch()) {
            $analytics['total_downloads'] = $total_downloads;
            $analytics['total_views'] = $total_views;
        }
        $stmt->close();
    }
    return $analytics;
}
?>