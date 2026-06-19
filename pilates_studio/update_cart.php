<?php
require_once 'config/database.php';
require_once 'config/functions.php';

if (!isLoggedIn()) {
    header('Location: auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_item_id = (int)$_POST['cart_item_id'];
    $quantity = (int)$_POST['quantity'];
    
    if ($quantity < 1) {
        setErrorMessage('數量不正確');
        header('Location: cart.php');
        exit();
    }
    
    $update_query = "UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?";
    $update_stmt = $mysqli->prepare($update_query);
    $update_stmt->bind_param("ii", $quantity, $cart_item_id);
    
    if ($update_stmt->execute()) {
        setSuccessMessage('購物車已更新');
    } else {
        setErrorMessage('更新失敗');
    }
}

header('Location: cart.php');
exit();
?>