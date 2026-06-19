<?php
require_once 'config/database.php';
require_once 'config/functions.php';

if (!isLoggedIn()) {
    header('Location: auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_item_id = (int)$_POST['cart_item_id'];
    
    $delete_query = "DELETE FROM cart_items WHERE cart_item_id = ?";
    $delete_stmt = $mysqli->prepare($delete_query);
    $delete_stmt->bind_param("i", $cart_item_id);
    
    if ($delete_stmt->execute()) {
        setSuccessMessage('已從購物車移除');
    } else {
        setErrorMessage('移除失敗');
    }
}

header('Location: cart.php');
exit();
?>