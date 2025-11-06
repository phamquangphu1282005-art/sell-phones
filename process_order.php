<?php
include 'connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['address']);
    $product_ids = $_POST['product_id'];   // mảng
    $quantities = $_POST['quantity'];      // mảng

    try {
        // Bắt đầu transaction
        $conn->beginTransaction();

        foreach ($product_ids as $index => $product_id) {
            $qty = $quantities[$index];

            // Lấy stock hiện tại
            $stmt = $conn->prepare("SELECT stock FROM sanpham WHERE id = ?");
            $stmt->execute([$product_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                throw new Exception("Sản phẩm ID $product_id không tồn tại!");
            }

            if ($row['stock'] < $qty) {
                throw new Exception("Sản phẩm ID $product_id không đủ hàng!");
            }

            // Trừ stock
            $stmt_update = $conn->prepare("UPDATE sanpham SET stock = stock - ? WHERE id = ?");
            $stmt_update->execute([$qty, $product_id]);

            // Thêm đơn hàng
            $stmt_insert = $conn->prepare("INSERT INTO orders (product_id, quantity, address, order_date) VALUES (?, ?, ?, NOW())");
            $stmt_insert->execute([$product_id, $qty, $address]);
        }

        // Commit transaction
        $conn->commit();
        echo "Đặt hàng thành công!";

    } catch (Exception $e) {
        $conn->rollBack();
        echo "Lỗi: " . $e->getMessage();
    }
}
?>
