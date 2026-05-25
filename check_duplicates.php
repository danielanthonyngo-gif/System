<?php
include 'config.php';
session_start();

header('Content-Type: application/json');
$duplicates = [];

// Read raw body stream directly to bypass post parameter limitations
$rawPayload = file_get_contents('php://input');
$requestData = json_decode($rawPayload, true);

if (isset($requestData['asset_tags']) && is_array($requestData['asset_tags'])) {
    $tags = array_map(function($t) use ($conn) {
        return mysqli_real_escape_string($conn, trim($t));
    }, $requestData['asset_tags']);

    $tags = array_filter($tags);

    if (!empty($tags)) {
        $tag_list = "'" . implode("','", $tags) . "'";
        $query = "SELECT asset_tag FROM assets WHERE asset_tag IN ($tag_list)";
        $result = mysqli_query($conn, $query);

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $duplicates[] = $row['asset_tag'];
            }
        }
    }
}

echo json_encode($duplicates);