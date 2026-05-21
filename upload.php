<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$inventory = $data['inventoryItems'];

// 1. Database Connection
$conn = new mysqli('localhost', 'root', '', 'iyong_database_name');

// 2. Loop sa bawat row at i-insert sa database
foreach ($inventory as $row) {
    // Siguraduhin na tumutugma ang mga column names sa Excel headers mo
    $item_name = $conn->real_escape_string($row['ItemName']); 
    $quantity = (int)$row['Quantity'];
    $price = (float)$row['Price'];

    $sql = "INSERT INTO inventory_table (item_name, quantity, price) VALUES ('$item_name', $quantity, $price)";
    $conn->query($sql);
}

echo json_encode(['success' => true, 'message' => 'Data imported successfully!']);
?>