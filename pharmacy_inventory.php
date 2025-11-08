<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? 'patient') !== 'pharmacy_owner') {
    header('Location: /medi/auth/login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$stmt = $db->prepare('SELECT id FROM pharmacies WHERE owner_user_id = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$pharmacy = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pharmacy) {
    header('Location: /medi/pharmacy_dashboard.php');
    exit;
}

$pharmacyId = (int)$pharmacy['id'];
$success = null;

// Handle add/update inventory
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $medicineId = (int)($_POST['medicine_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    $price = $_POST['price'] ? (float)$_POST['price'] : null;
    
    $stmt = $db->prepare('INSERT INTO pharmacy_inventory (pharmacy_id, medicine_id, quantity, price) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE quantity = ?, price = ?');
    $stmt->bind_param('iiiddd', $pharmacyId, $medicineId, $quantity, $price, $quantity, $price);
    $stmt->execute();
    $stmt->close();
    $success = 'Inventory updated successfully.';
}

// Get all medicines
$stmt = $db->prepare('SELECT * FROM medicines ORDER BY name ASC');
$stmt->execute();
$medicines = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get current inventory
$stmt = $db->prepare('SELECT pi.*, m.name as medicine_name, m.generic_name FROM pharmacy_inventory pi JOIN medicines m ON pi.medicine_id = m.id WHERE pi.pharmacy_id = ? ORDER BY m.name ASC');
$stmt->bind_param('i', $pharmacyId);
$stmt->execute();
$inventory = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get edit item if requested
$editItem = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    foreach ($inventory as $item) {
        if ($item['id'] == $editId) {
            $editItem = $item;
            break;
        }
    }
}
?>
<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
  <!-- [Head] start -->
  <head>
    <title>Inventory Management | MediFinder</title>
    <!-- [Meta] -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Manage pharmacy inventory" />
    <meta name="keywords" content="inventory management, pharmacy stock" />
    <meta name="author" content="MediFinder" />

    <!-- [Favicon] icon -->
    <link rel="icon" href="/medi/assets/img/medifinder-logo.svg" type="image/x-icon" />

    <?php include 'includes/head-css.php'; ?>
  </head>
  <!-- [Head] end -->
  <!-- [Body] Start -->

  <body>
    <?php include 'includes/loader.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/header.php'; ?>

    <!-- [ Main Content ] start -->
    <div class="pc-container">
      <div class="pc-content">
        <?php
        $pageTitle = 'Inventory Management';
        $breadcrumbItems = ['Pharmacy', 'Inventory'];
        $activeItem = 'Management';
        include 'includes/breadcrumb.php';
        ?>

        <!-- [ Main Content ] start -->
        <div class="grid grid-cols-12 gap-x-6">
          <?php if ($success): ?>
            <div class="col-span-12">
              <div class="alert alert-success">
                <i class="feather icon-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- Add/Update Form -->
          <div class="col-span-12 mb-4">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0"><?php echo $editItem ? 'Update' : 'Add/Update'; ?> Medicine Stock</h5>
              </div>
              <div class="card-body">
                <form method="post">
                  <div class="grid grid-cols-12 gap-x-3">
                    <div class="col-span-12 md:col-span-5">
                      <label class="form-label">Medicine</label>
                      <select class="form-select" name="medicine_id" required <?php echo $editItem ? 'disabled' : ''; ?>>
                        <option value="">Select medicine...</option>
                        <?php foreach ($medicines as $med): ?>
                          <option value="<?php echo $med['id']; ?>" <?php echo ($editItem && $editItem['medicine_id'] == $med['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($med['name']); ?><?php echo $med['generic_name'] ? ' (' . htmlspecialchars($med['generic_name']) . ')' : ''; ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                      <?php if ($editItem): ?>
                        <input type="hidden" name="medicine_id" value="<?php echo $editItem['medicine_id']; ?>">
                      <?php endif; ?>
                    </div>
                    <div class="col-span-12 md:col-span-3">
                      <label class="form-label">Quantity</label>
                      <input type="number" class="form-control" name="quantity" min="0" required value="<?php echo $editItem ? $editItem['quantity'] : ''; ?>">
                    </div>
                    <div class="col-span-12 md:col-span-3">
                      <label class="form-label">Price (₱)</label>
                      <input type="number" class="form-control" name="price" step="0.01" min="0" value="<?php echo $editItem ? $editItem['price'] : ''; ?>">
                    </div>
                    <div class="col-span-12 md:col-span-1">
                      <label class="form-label">&nbsp;</label>
                      <button type="submit" class="btn btn-primary w-100">
                        <i class="feather icon-save mr-1"></i>Save
                      </button>
                    </div>
                  </div>
                  <?php if ($editItem): ?>
                    <div class="mt-3">
                      <a href="/medi/pharmacy_inventory.php" class="btn btn-sm btn-outline-secondary">
                        <i class="feather icon-x mr-1"></i>Cancel
                      </a>
                    </div>
                  <?php endif; ?>
                </form>
              </div>
            </div>
          </div>

          <!-- Current Inventory -->
          <div class="col-span-12">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Current Inventory</h5>
              </div>
              <div class="card-body">
                <?php if (empty($inventory)): ?>
                  <div class="text-center py-4 text-muted">
                    <i class="feather icon-package text-[48px] mb-3 opacity-25"></i>
                    <p class="mb-0">No inventory items yet. Add medicines above.</p>
                  </div>
                <?php else: ?>
                  <div class="table-responsive">
                    <table class="table table-hover">
                      <thead>
                        <tr>
                          <th>Medicine</th>
                          <th>Generic Name</th>
                          <th>Quantity</th>
                          <th>Price</th>
                          <th>Status</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($inventory as $item): ?>
                          <tr class="<?php echo $item['quantity'] <= 10 ? 'table-warning' : ''; ?>">
                            <td><strong><?php echo htmlspecialchars($item['medicine_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($item['generic_name'] ?: '—'); ?></td>
                            <td><?php echo $item['quantity']; ?> units</td>
                            <td>₱<?php echo number_format($item['price'] ?: 0, 2); ?></td>
                            <td>
                              <?php if ($item['quantity'] > 10): ?>
                                <span class="badge bg-success">In Stock</span>
                              <?php elseif ($item['quantity'] > 0): ?>
                                <span class="badge bg-warning">Low Stock</span>
                              <?php else: ?>
                                <span class="badge bg-danger">Out of Stock</span>
                              <?php endif; ?>
                            </td>
                            <td>
                              <a href="/medi/pharmacy_inventory.php?edit=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="feather icon-edit mr-1"></i>Edit
                              </a>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <!-- [ Main Content ] end -->
      </div>
    </div>
    <!-- [ Main Content ] end -->
    
    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/footer-js.php'; ?>
  </body>
  <!-- [Body] end -->
</html>
