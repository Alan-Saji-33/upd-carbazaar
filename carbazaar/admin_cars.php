<?php
// Start output buffering to prevent headers issues
ob_start();

// Ensure this file is included in admin_dashboard.php
if (!defined('IN_ADMIN_DASHBOARD')) {
    define('IN_ADMIN_DASHBOARD', true);
}

$search_query = isset($_GET['search']) ? filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING) : '';
$where_clause = $search_query ? "WHERE (brand LIKE ? OR model LIKE ?)" : "";
$params = $search_query ? ["%$search_query%", "%$search_query%"] : [];
$param_types = $search_query ? "ss" : "";

$stmt = $conn->prepare("SELECT id, brand, model, price, is_sold, main_image FROM cars $where_clause ORDER BY created_at DESC");
if ($stmt === false) {
    $_SESSION['error'] = "Prepare failed: " . htmlspecialchars($conn->error);
    header("Location: admin_dashboard.php?section=cars" . ($search_query ? "&search=" . urlencode($search_query) : ""));
    exit();
}
if ($search_query) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$cars_result = $stmt->get_result();

// Handle mark as sold
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_sold'])) {
    try {
        $car_id = filter_input(INPUT_POST, 'car_id', FILTER_SANITIZE_NUMBER_INT);
        $stmt = $conn->prepare("UPDATE cars SET is_sold = TRUE WHERE id = ?");
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $car_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Car marked as sold!";
        } else {
            throw new Exception("Failed to mark car as sold: " . $stmt->error);
        }
        $stmt->close();
        header("Location: admin_dashboard.php?section=cars" . ($search_query ? "&search=" . urlencode($search_query) : ""));
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Handle unmark as sold
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['unmark_sold'])) {
    try {
        $car_id = filter_input(INPUT_POST, 'car_id', FILTER_SANITIZE_NUMBER_INT);
        $stmt = $conn->prepare("UPDATE cars SET is_sold = FALSE WHERE id = ?");
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $car_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Car unmarked as sold!";
        } else {
            throw new Exception("Failed to unmark car as sold: " . $stmt->error);
        }
        $stmt->close();
        header("Location: admin_dashboard.php?section=cars" . ($search_query ? "&search=" . urlencode($search_query) : ""));
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}
?>

<div class="table-container">
    <h3>Manage Cars</h3>
    <?php if ($cars_result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Car</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($car = $cars_result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <img src="<?php echo htmlspecialchars($car['main_image'] ?: 'Uploads/cars/default.jpg'); ?>" alt="<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>" class="car-image">
                                <?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>
                            </div>
                        </td>
                        <td style="text-align: right;">₹<?php echo number_format($car['price'], 0, '', ','); ?></td>
                        <td style="text-align: center;"><?php echo $car['is_sold'] ? 'Sold' : 'Available'; ?></td>
                        <td class="action-buttons">
                            <a href="view_car.php?id=<?php echo $car['id']; ?>" class="btn btn-outline"><i class="fas fa-eye"></i> View</a>
                            <a href="edit_car.php?id=<?php echo $car['id']; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Edit</a>
                            <a href="delete_car.php?id=<?php echo $car['id']; ?>" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</a>
                            <?php if (!$car['is_sold']): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                    <button type="submit" name="mark_sold" class="btn btn-warning"><i class="fas fa-check-circle"></i> Mark Sold</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="car_id" value="<?php echo $car['id']; ?>">
                                    <button type="submit" name="unmark_sold" class="btn btn-warning"><i class="fas fa-undo"></i> Unmark Sold</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No cars found.</p>
    <?php endif; ?>
</div>

<?php
// End output buffering
ob_end_flush();
?>