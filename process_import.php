<?php
include 'config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized session request route.']);
    exit();
}

// Read raw body stream directly to bypass max_input_vars parameters limit completely
$rawPayload = file_get_contents('php://input');
$requestData = json_decode($rawPayload, true);

if (isset($requestData['assets']) && is_array($requestData['assets'])) {
    $assets = $requestData['assets'];
    $inserted = 0;
    $skipped = 0;

    mysqli_begin_transaction($conn);

    try {
        foreach ($assets as $item) {
            $inv_date = mysqli_real_escape_string($conn, $item['inventory_date']);
            $tag      = mysqli_real_escape_string($conn, trim($item['asset_tag']));
            $serial   = mysqli_real_escape_string($conn, trim($item['serial_number']));
            $brand    = mysqli_real_escape_string($conn, trim($item['brand_model']));
            $type     = mysqli_real_escape_string($conn, trim($item['asset_type']));
            $year     = mysqli_real_escape_string($conn, trim($item['year_model']));
            $location = mysqli_real_escape_string($conn, trim($item['location']));
            
            $rawStatus = strtolower(trim($item['status']));
            $status = 'Active';
            if (strpos($rawStatus, 'disposal') !== false) {
                $status = 'For Disposal';
            } elseif (strpos($rawStatus, 'replacement') !== false) {
                $status = 'Replacement';
            }

            if (empty($tag)) {
                $skipped++;
                continue;
            }

            // Database inline lookup confirmation module 
            $dup_check = mysqli_query($conn, "SELECT id FROM assets WHERE asset_tag = '$tag' LIMIT 1");
            if (mysqli_num_rows($dup_check) > 0) {
                $skipped++;
                continue;
            }

            $query = "INSERT INTO assets (inventory_date, asset_tag, serial_number, brand_model, asset_type, year_model, location, status) 
                      VALUES ('$inv_date', '$tag', '$serial', '$brand', '$type', '$year', '$location', '$status')";
            
            if (mysqli_query($conn, $query)) {
                $inserted++;
            } else {
                throw new Exception(mysqli_error($conn));
            }
        }

        mysqli_commit($conn);
        echo json_encode([
            'status' => 'success',
            'message' => "Successfully uploaded {$inserted} items! (Skipped {$skipped} matching duplicate records)"
        ]);

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['status' => 'error', 'message' => 'Transaction aborted: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Missing valid payload parameters or empty file context source.']);
}