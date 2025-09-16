<?php
// Basic IPN endpoint to receive MoMo payment result notifications
// NOTE: For test/demo only. In production, verify signature and update order status.

header('Content-Type: application/json');

$input = file_get_contents('php://input');
if (!$input) {
    echo json_encode(['result' => 'no_input']);
    exit;
}

$data = json_decode($input, true);
if (!is_array($data)) {
    echo json_encode(['result' => 'invalid_json']);
    exit;
}

// Here you would verify $data['signature'] using your secret key and the raw fields
// and then update your database order status by $data['orderId']

// Respond with success so MoMo stops retrying
echo json_encode(['result' => 'success']);
exit;


