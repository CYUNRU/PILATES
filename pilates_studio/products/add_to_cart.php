<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// 檢查用戶是否登入
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $user_id = $_SESSION['user_id'];
    
    // 驗證數量
    if ($quantity < 1) {
        setErrorMessage('數量不正確');
        header('Location: shop.php');
        exit();
    }
    
    // 檢查產品是否存在和庫存
    $product_query = "SELECT * FROM products WHERE product_id = ?";
    $product_stmt = $mysqli->prepare($product_query);
    $product_stmt->bind_param("i", $product_id);
    $product_stmt->execute();
    $product_result = $product_stmt->get_result();
    
    if ($product_result->num_rows === 0 || $product_result->fetch_assoc()['stock'] < $quantity) {
        setErrorMessage('產品不存在或庫存不足');
        header('Location: shop.php');
        exit();
    }
    
    // 獲取或創建購物車
    $cart_query = "SELECT cart_id FROM shopping_carts WHERE user_id = ?";
    $cart_stmt = $mysqli->prepare($cart_query);
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    
    if ($cart_result->num_rows > 0) {
        $cart = $cart_result->fetch_assoc();
        $cart_id = $cart['cart_id'];
    } else {
        $create_cart = "INSERT INTO shopping_carts (user_id) VALUES (?)";
        $create_cart_stmt = $mysqli->prepare($create_cart);
        $create_cart_stmt->bind_param("i", $user_id);
        $create_cart_stmt->execute();
        $cart_id = $create_cart_stmt->insert_id;
    }
    
    // 檢查購物車中是否已有該產品
    $check_item = "SELECT cart_item_id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?";
    $check_stmt = $mysqli->prepare($check_item);
    $check_stmt->bind_param("ii", $cart_id, $product_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // 更新數量
        $item = $check_result->fetch_assoc();
        $new_quantity = $item['quantity'] + $quantity;
        $update_query = "UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?";
        $update_stmt = $mysqli->prepare($update_query);
        $update_stmt->bind_param("ii", $new_quantity, $item['cart_item_id']);
        $update_stmt->execute();
    } else {
        // 添加新項目
        $add_query = "INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)";
        $add_stmt = $mysqli->prepare($add_query);
        $add_stmt->bind_param("iii", $cart_id, $product_id, $quantity);
        $add_stmt->execute();
    }
    
    setSuccessMessage('已加入購物車');
}

header('Location: shop.php');
exit();
?>