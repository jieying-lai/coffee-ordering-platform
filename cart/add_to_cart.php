<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $temperature = isset($_POST['temperature']) ? trim($_POST['temperature']) : '';
    $sweetness = isset($_POST['sweetness']) ? trim($_POST['sweetness']) : '';
    $remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';

    $customPrice = isset($_POST['custom_price']) ? (float)$_POST['custom_price'] : null;

    if ($itemId > 0 && $quantity > 0) {
        require_once '../includes/db_connect.php';
        $catStmt = $conn->prepare("SELECT c.category_key, mi.category_id FROM menu_items mi JOIN categories c ON c.category_id = mi.category_id WHERE mi.item_id = ?");
        if ($catStmt) {
            $catStmt->bind_param("i", $itemId);
            $catStmt->execute();
            $catRes = $catStmt->get_result()->fetch_assoc();
            $catStmt->close();
            if ($catRes) {
                $cKey = strtolower($catRes['category_key'] ?? '');
                $cId = (int)($catRes['category_id'] ?? 0);
                if ($cId === 5 || $cId === 6 || in_array($cKey, ['mains', 'desserts', 'food'], true)) {
                    $temperature = '';
                    $sweetness = '';
                }
            }
        }

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Create a unique key based on item ID and custom selections
        $cartKey = md5($itemId . '|' . $temperature . '|' . $sweetness . '|' . $remarks . '|' . $customPrice);

        if (isset($_SESSION['cart'][$cartKey])) {
            $_SESSION['cart'][$cartKey]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$cartKey] = [
                'item_id' => $itemId,
                'quantity' => $quantity,
                'temperature' => $temperature,
                'sweetness' => $sweetness,
                'remarks' => $remarks,
                'custom_price' => $customPrice
            ];
        }
    }
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
              || isset($_POST['ajax']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if ($isAjax) {
        header('Content-Type: application/json');
        $totalItems = 0;
        if (!empty($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $c) {
                $totalItems += (int)($c['quantity'] ?? 1);
            }
        }
        echo json_encode([
            'status' => 'success',
            'message' => 'Item added to cart!',
            'cart_count' => count($_SESSION['cart']),
            'total_quantity' => $totalItems
        ]);
        exit;
    }
}

// Fallback redirect for standard form submissions
header('Location: ../cart/index.php');
exit;