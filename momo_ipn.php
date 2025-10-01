<?php
// MoMo IPN (Instant Payment Notification) endpoint for Motel Management System
// This file receives payment notifications from MoMo

session_start();
require_once 'includes/db';
require_once 'momo_config.php';

header('Content-Type: application/json');

$input = file_get_contents('php://input');
if (!$input) {
    @file_put_contents(__DIR__ . '/momo_debug.log', "[IPN] no_input at " . date('c') . "\n", FILE_APPEND);
    echo json_encode(['result' => 'no_input']);
    exit;
}

$data = json_decode($input, true);
if (!is_array($data)) {
    @file_put_contents(__DIR__ . '/momo_debug.log', "[IPN] invalid_json at " . date('c') . ": " . substr($input,0,500) . "\n", FILE_APPEND);
    echo json_encode(['result' => 'invalid_json']);
    exit;
}

// Verify signature (in production, you should verify the signature)
$secretKey = MOMO_SECRET_KEY;
$rawHash = "accessKey=" . $data['accessKey'] . "&amount=" . $data['amount'] . "&extraData=" . $data['extraData'] . "&message=" . $data['message'] . "&orderId=" . $data['orderId'] . "&orderInfo=" . $data['orderInfo'] . "&orderType=" . $data['orderType'] . "&partnerCode=" . $data['partnerCode'] . "&payType=" . $data['payType'] . "&requestId=" . $data['requestId'] . "&responseTime=" . $data['responseTime'] . "&resultCode=" . $data['resultCode'] . "&transId=" . $data['transId'];
$signature = hash_hmac("sha256", $rawHash, $secretKey);

if ($signature !== $data['signature']) {
    @file_put_contents(__DIR__ . '/momo_debug.log', "[IPN] invalid_signature orderId=" . ($data['orderId']??'') . " at " . date('c') . "\n", FILE_APPEND);
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
        // Parse orderId to get payment type and ids
        $orderParts = explode('_', $orderId);
        $paymentType = $orderParts[0];
        $isSplitMulti = false; // reservation_multi formatted as reservation_multi_* or reservation_multi split as reservation,multi
        if ($paymentType === 'reservation' && isset($orderParts[1]) && $orderParts[1] === 'multi') {
            $paymentType = 'reservation_multi';
            $isSplitMulti = true;
        }
        $roomId = null;
        $userId = null;
        $billId = null;
        if ($paymentType === 'bill') {
            $billId = isset($orderParts[1]) ? (int)$orderParts[1] : null;
            $userId = isset($orderParts[2]) ? (int)$orderParts[2] : null;
        } elseif ($paymentType === 'reservation_multi') {
            // reservation_multi formats:
            // - reservation_multi_{userId}_{idsSegment}_{ts} (new)
            // - reservation_multi_{userId}_{ts} (old)
            // - reservation_multi split variant: reservation, multi, {userId}, {idsSegment}, {ts}
            if ($isSplitMulti) {
                $userId = isset($orderParts[2]) ? (int)$orderParts[2] : null;
            } else {
                $userId = isset($orderParts[1]) ? (int)$orderParts[1] : null;
            }
        } else {
            // reservation_{roomId}_{userId}_{ts} or deposit_{roomId}_{userId}_{ts}
            $roomId = isset($orderParts[1]) ? (int)$orderParts[1] : null;
            $userId = isset($orderParts[2]) ? (int)$orderParts[2] : null;
        }
        
        // Start transaction to ensure data consistency
        $conn->begin_transaction();
        
        try {
            // Debug log: basic context (file + error_log)
            @file_put_contents(__DIR__ . '/momo_debug.log', "[IPN] begin orderId=$orderId type=$paymentType amount=$amount at " . date('c') . "\n", FILE_APPEND);
            error_log('[IPN] orderId=' . $orderId . ' type=' . $paymentType . ' amount=' . $amount);
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
            } elseif ($paymentType === 'reservation_multi') {
                // Multi-room reservation: decode extraData or parse by userId from orderId
                $roomIds = [];
                $userId = (int)$userId;
                if (!empty($data['extraData'])) {
                    $decoded = json_decode(base64_decode($data['extraData']), true);
                    if (isset($decoded['userId']) && (int)$decoded['userId'] > 0) {
                        $userId = (int)$decoded['userId'];
                    }
                    if (isset($decoded['roomIds']) && is_array($decoded['roomIds'])) {
                        $roomIds = array_map('intval', $decoded['roomIds']);
                    }
                }
                if (empty($roomIds)) {
                    // Fallback: find user's pending reservations and use them
                    $q = $conn->prepare("SELECT room_id FROM reservations WHERE user_id = ? AND status = 'pending'");
                    $q->bind_param('i', $userId);
                    $q->execute();
                    $rs = $q->get_result();
                    while ($r = $rs->fetch_assoc()) { $roomIds[] = (int)$r['room_id']; }
                }
                if (empty($roomIds)) {
                    // Fallback parse room ids in orderId: reservation_multi_{userId}_{id-id-...}_{ts}
                    if ($isSplitMulti) {
                        if (count($orderParts) >= 5) {
                            $idsSegment = $orderParts[3];
                            foreach (explode('-', $idsSegment) as $ridStr) {
                                $rid = (int)$ridStr;
                                if ($rid > 0) { $roomIds[] = $rid; }
                            }
                        }
                    } else {
                        if (count($orderParts) >= 4) {
                            $idsSegment = $orderParts[2];
                            foreach (explode('-', $idsSegment) as $ridStr) {
                                $rid = (int)$ridStr;
                                if ($rid > 0) { $roomIds[] = $rid; }
                            }
                        }
                    }
                }
                if (empty($roomIds)) {
                    // Additional fallback: rooms currently in user's cart
                    $q2 = $conn->prepare("SELECT room_id FROM carts WHERE user_id = ?");
                    $q2->bind_param('i', $userId);
                    $q2->execute();
                    $rs2 = $q2->get_result();
                    while ($r2 = $rs2->fetch_assoc()) { $roomIds[] = (int)$r2['room_id']; }
                }
                if (!empty($roomIds)) {
                    // Reserve each room and clear from cart
                    error_log('[IPN] reservation_multi userId=' . $userId . ' rooms=' . implode(',', $roomIds));
                    foreach ($roomIds as $rid) {
                        $upd_res_sql = "UPDATE reservations SET status = 'paid' WHERE user_id = ? AND room_id = ? AND status = 'pending'";
                        if ($upd_res_stmt = $conn->prepare($upd_res_sql)) {
                            $upd_res_stmt->bind_param('ii', $userId, $rid);
                            $upd_res_stmt->execute();
                            @file_put_contents(__DIR__ . '/momo_debug.log', "[IPN] res_paid rows=" . $upd_res_stmt->affected_rows . " userId=$userId roomId=$rid\n", FILE_APPEND);
                            error_log('[IPN] reservation updated rows=' . $upd_res_stmt->affected_rows . ' roomId=' . $rid);
                        }

                        $upd_room_sql = "UPDATE rooms SET status = 'reserved' WHERE id = ? AND status = 'available'";
                        if ($upd_room_stmt = $conn->prepare($upd_room_sql)) {
                            $upd_room_stmt->bind_param('i', $rid);
                            $upd_room_stmt->execute();
                            @file_put_contents(__DIR__ . '/momo_debug.log', "[IPN] room_reserved rows=" . $upd_room_stmt->affected_rows . " roomId=$rid\n", FILE_APPEND);
                            error_log('[IPN] room updated rows=' . $upd_room_stmt->affected_rows . ' roomId=' . $rid);
                        }

                        $del_cart_sql = "DELETE FROM carts WHERE user_id = ? AND room_id = ?";
                        if ($del_cart_stmt = $conn->prepare($del_cart_sql)) {
                            $del_cart_stmt->bind_param('ii', $userId, $rid);
                            $del_cart_stmt->execute();
                            @file_put_contents(__DIR__ . '/momo_debug.log', "[IPN] cart_deleted rows=" . $del_cart_stmt->affected_rows . " userId=$userId roomId=$rid\n", FILE_APPEND);
                            error_log('[IPN] cart deleted rows=' . $del_cart_stmt->affected_rows . ' roomId=' . $rid);
                        }
                    }
                } else {
                    // Last-resort: update all pending reservations for this user
                    if ($stmtAll = $conn->prepare("UPDATE reservations SET status = 'paid' WHERE user_id = ? AND status = 'pending'")) {
                        $stmtAll->bind_param('i', $userId);
                        $stmtAll->execute();
                        @file_put_contents(__DIR__ . '/momo_debug.log', "[IPN] res_paid_bulk rows=" . $stmtAll->affected_rows . " userId=$userId\n", FILE_APPEND);
                        error_log('[IPN] reservation fallback bulk updated rows=' . $stmtAll->affected_rows . ' userId=' . $userId);
                    }
                    // And clear all items in cart for this user that now have a paid reservation
                    $conn->query("DELETE c FROM carts c JOIN reservations r ON r.user_id = c.user_id AND r.room_id = c.room_id AND r.status = 'paid' WHERE c.user_id = " . (int)$userId);
                    @file_put_contents(__DIR__ . '/momo_debug.log', "[IPN] cart_deleted_bulk userId=$userId\n", FILE_APPEND);
                    error_log('[IPN] cart fallback bulk deleted for userId=' . $userId);
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
            @file_put_contents(__DIR__ . '/momo_debug.log', "[IPN] commit success orderId=$orderId transId=$transId\n", FILE_APPEND);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            @file_put_contents(__DIR__ . '/momo_debug.log', "[IPN] exception: " . $e->getMessage() . "\n", FILE_APPEND);
            
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
        @file_put_contents(__DIR__ . '/momo_debug.log', "[IPN] failed orderId=$orderId code=$resultCode msg=" . ($data['message']??'') . "\n", FILE_APPEND);
    }
    
    // Respond with success so MoMo stops retrying
    @file_put_contents(__DIR__ . '/momo_debug.log', "[IPN] respond success orderId=$orderId at " . date('c') . "\n", FILE_APPEND);
    echo json_encode(['result' => 'success']);
    
} catch (Exception $e) {
    error_log("MoMo IPN Error: " . $e->getMessage());
    echo json_encode(['result' => 'error', 'message' => $e->getMessage()]);
}

exit;
?>
