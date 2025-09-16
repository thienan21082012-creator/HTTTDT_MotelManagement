<?php
// MoMo IPN (Instant Payment Notification) endpoint for Motel Management System
// This file receives payment notifications from MoMo

session_start();
require_once 'includes/db';
require_once 'momo_config.php';

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

// Verify signature (in production, you should verify the signature)
$secretKey = MOMO_SECRET_KEY;
$rawHash = "accessKey=" . $data['accessKey'] . "&amount=" . $data['amount'] . "&extraData=" . $data['extraData'] . "&message=" . $data['message'] . "&orderId=" . $data['orderId'] . "&orderInfo=" . $data['orderInfo'] . "&orderType=" . $data['orderType'] . "&partnerCode=" . $data['partnerCode'] . "&payType=" . $data['payType'] . "&requestId=" . $data['requestId'] . "&responseTime=" . $data['responseTime'] . "&resultCode=" . $data['resultCode'] . "&transId=" . $data['transId'];
$signature = hash_hmac("sha256", $rawHash, $secretKey);

if ($signature !== $data['signature']) {
    echo json_encode(['result' => 'invalid_signature']);
    exit;
}

// Process payment result
$orderId = $data['orderId'];
$resultCode = $data['resultCode'];
$amount = $data['amount'];
$transId = $data['transId'];

// Check for duplicate processing
$check_duplicate_sql = "SELECT id FROM payment_logs WHERE trans_id = ?";
$check_duplicate_stmt = $conn->prepare($check_duplicate_sql);
$check_duplicate_stmt->bind_param("s", $transId);
$check_duplicate_stmt->execute();
$duplicate_result = $check_duplicate_stmt->get_result();

if ($duplicate_result->num_rows > 0) {
    error_log("MoMo IPN: Duplicate transaction detected - TransId=$transId");
    echo json_encode(['result' => 'duplicate']);
    exit;
}

try {
    if ($resultCode == 0) {
        // Payment successful
        // Parse orderId to get payment type
        $orderParts = explode('_', $orderId);
        $paymentType = $orderParts[0]; // 'reservation' | 'deposit' | 'bill'
        $roomId = null;
        $userId = null;
        $billId = null;
        if ($paymentType === 'bill') {
            $billId = isset($orderParts[1]) ? (int)$orderParts[1] : null;
            $userId = isset($orderParts[2]) ? (int)$orderParts[2] : null;
        } else {
            $roomId = $orderParts[1];
            $userId = $orderParts[2];
        }
        
        // Start transaction to ensure data consistency
        $conn->begin_transaction();
        
        try {
            if ($paymentType === 'reservation') {
                // Lock the room row to prevent race conditions
                $lock_sql = "SELECT id FROM rooms WHERE id = ? FOR UPDATE";
                $lock_stmt = $conn->prepare($lock_sql);
                $lock_stmt->bind_param("i", $roomId);
                $lock_stmt->execute();
                $lock_result = $lock_stmt->get_result();
                
                if ($lock_result->num_rows === 0) {
                    throw new Exception("Room not found: $roomId");
                }
                
                // Update reservation status with additional validation
                $update_sql = "UPDATE reservations SET status = 'paid' WHERE user_id = ? AND room_id = ? AND status = 'pending'";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("ii", $userId, $roomId);
                $stmt->execute();
                
                // Check if reservation was updated
                if ($stmt->affected_rows > 0) {
                    // Update room status to reserved with additional check
                    $update_room_sql = "UPDATE rooms SET status = 'reserved' WHERE id = ? AND status = 'available'";
                    $room_stmt = $conn->prepare($update_room_sql);
                    $room_stmt->bind_param("i", $roomId);
                    $room_stmt->execute();
                    
                    if ($room_stmt->affected_rows > 0) {
                        // Remove from cart after successful reservation
                        $del_cart_sql = "DELETE FROM carts WHERE user_id = ? AND room_id = ?";
                        $del_cart_stmt = $conn->prepare($del_cart_sql);
                        $del_cart_stmt->bind_param("ii", $userId, $roomId);
                        $del_cart_stmt->execute();
                        error_log("MoMo Reservation Success: OrderId=$orderId, RoomId=$roomId, UserId=$userId");
                    } else {
                        error_log("MoMo Reservation Warning: Room $roomId status was not available, cannot reserve");
                        throw new Exception("Room is no longer available for reservation");
                    }
                } else {
                    error_log("MoMo Reservation Warning: No pending reservation found for OrderId=$orderId");
                    throw new Exception("No pending reservation found");
                }
                
            } elseif ($paymentType === 'deposit') {
                // Lock the room row to prevent race conditions
                $lock_sql = "SELECT id FROM rooms WHERE id = ? FOR UPDATE";
                $lock_stmt = $conn->prepare($lock_sql);
                $lock_stmt->bind_param("i", $roomId);
                $lock_stmt->execute();
                $lock_result = $lock_stmt->get_result();
                
                if ($lock_result->num_rows === 0) {
                    throw new Exception("Room not found: $roomId");
                }
                
                // Update payment status for deposit
                $update_sql = "UPDATE payments SET payment_status = 'completed' WHERE user_id = ? AND room_id = ? AND payment_type = 'deposit' AND payment_status = 'pending'";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("ii", $userId, $roomId);
                $stmt->execute();
                
                // Check if payment was updated
                if ($stmt->affected_rows > 0) {
                    // Update room status to occupied (be tolerant if still available)
                    $update_room_sql = "UPDATE rooms SET status = 'occupied' WHERE id = ?";
                    $room_stmt = $conn->prepare($update_room_sql);
                    $room_stmt->bind_param("i", $roomId);
                    $room_stmt->execute();
                    
                    if ($room_stmt->affected_rows > 0) {
                        // Delete reservation record
                        $delete_res_sql = "DELETE FROM reservations WHERE user_id = ? AND room_id = ?";
                        $delete_stmt = $conn->prepare($delete_res_sql);
                        $delete_stmt->bind_param("ii", $userId, $roomId);
                        $delete_stmt->execute();
                        
                        error_log("MoMo Deposit Success: OrderId=$orderId, RoomId=$roomId, UserId=$userId");
                    } else {
                        error_log("MoMo Deposit Warning: Room $roomId status was not reserved, cannot occupy");
                        throw new Exception("Room is not in reserved status");
                    }
                } else {
                    error_log("MoMo Deposit Warning: No pending payment found for OrderId=$orderId");
                    throw new Exception("No pending payment found");
                }
            } elseif ($paymentType === 'bill') {
                // Mark the bill as paid
                if (!$billId || !$userId) {
                    throw new Exception('Invalid bill payment orderId');
                }
                $upd_bill_sql = "UPDATE bills SET status = 'paid' WHERE id = ? AND user_id = ? AND status = 'unpaid'";
                $upd_bill_stmt = $conn->prepare($upd_bill_sql);
                $upd_bill_stmt->bind_param('ii', $billId, $userId);
                $upd_bill_stmt->execute();

                if ($upd_bill_stmt->affected_rows === 0) {
                    throw new Exception('No unpaid bill found to update');
                }
            }
            
            // Log the payment transaction
            $log_sql = "INSERT INTO payment_logs (order_id, trans_id, amount, payment_type, status, created_at) VALUES (?, ?, ?, ?, 'success', NOW())";
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->bind_param("ssds", $orderId, $transId, $amount, $paymentType);
            $log_stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            // Log successful payment
            error_log("MoMo Payment Success: OrderId=$orderId, Amount=$amount, TransId=$transId");
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            
            // Log failed payment
            $log_sql = "INSERT INTO payment_logs (order_id, trans_id, amount, payment_type, status, error_message, created_at) VALUES (?, ?, ?, ?, 'failed', ?, NOW())";
            $log_stmt = $conn->prepare($log_sql);
            $error_msg = $e->getMessage();
            $log_stmt->bind_param("ssdss", $orderId, $transId, $amount, $paymentType, $error_msg);
            $log_stmt->execute();
            
            error_log("MoMo Payment Error: OrderId=$orderId, Error=" . $e->getMessage());
            throw $e;
        }
        
    } else {
        // Payment failed - update pending deposit payment if applicable and log the failure
        try {
            $orderParts = explode('_', $orderId);
            $paymentType = $orderParts[0] ?? 'unknown';
            $roomId = isset($orderParts[1]) && $paymentType !== 'bill' ? (int)$orderParts[1] : null;
            $userId = isset($orderParts[2]) ? (int)$orderParts[2] : null;

            if ($paymentType === 'deposit' && $roomId && $userId) {
                $upd_pay_sql = "UPDATE payments SET payment_status = 'failed' WHERE user_id = ? AND room_id = ? AND payment_type = 'deposit' AND payment_status = 'pending'";
                $upd_pay_stmt = $conn->prepare($upd_pay_sql);
                $upd_pay_stmt->bind_param('ii', $userId, $roomId);
                $upd_pay_stmt->execute();
            }
            // For 'bill' failures, no DB update is needed; bill remains 'unpaid'
        } catch (Throwable $e) {
            error_log('IPN failed update error: ' . $e->getMessage());
        }

        $log_sql = "INSERT INTO payment_logs (order_id, trans_id, amount, payment_type, status, error_message, created_at) VALUES (?, ?, ?, ?, 'failed', ?, NOW())";
        $log_stmt = $conn->prepare($log_sql);
        $error_msg = "Payment failed with result code: " . $resultCode . " - " . $data['message'];
        $log_stmt->bind_param("ssds s", $orderId, $transId, $amount, $paymentType, $error_msg);
        // Note: bind types need to be contiguous; split to avoid spacing issue
        $log_stmt = $conn->prepare("INSERT INTO payment_logs (order_id, trans_id, amount, payment_type, status, error_message, created_at) VALUES (?, ?, ?, ?, 'failed', ?, NOW())");
        $log_stmt->bind_param("ssdds", $orderId, $transId, $amount, $paymentType, $error_msg);
        $log_stmt->execute();
        
        error_log("MoMo Payment Failed: OrderId=$orderId, ResultCode=$resultCode, Message=" . $data['message']);
    }
    
    // Respond with success so MoMo stops retrying
    echo json_encode(['result' => 'success']);
    
} catch (Exception $e) {
    error_log("MoMo IPN Error: " . $e->getMessage());
    echo json_encode(['result' => 'error', 'message' => $e->getMessage()]);
}

exit;
?>
