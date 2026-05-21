<?php
include 'config.php';
session_start();

$duplicates = [];

if (isset($_POST['asset_tags']) && is_array($_POST['asset_tags'])) {
    $tags = array_map(function($t) use ($conn) {
        return mysqli_real_escape_string($conn, trim($t));
    }, $_POST['asset_tags']);

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