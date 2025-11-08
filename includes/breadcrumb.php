<?php
$pageTitle = $pageTitle ?? 'Dashboard';
$breadcrumbItems = $breadcrumbItems ?? ['Dashboard'];
$activeItem = $activeItem ?? $pageTitle;
?>
<!-- [ breadcrumb ] start -->
<div class="page-header">
  <div class="page-block">
    <div class="page-header-title">
      <h5 class="mb-0 font-medium"><?php echo htmlspecialchars($activeItem); ?></h5>
    </div>
    <ul class="breadcrumb">
      <li class="breadcrumb-item"><a href="/CEMO_System/system/dashboard.php">Home</a></li>
      <?php foreach ($breadcrumbItems as $item): ?>
        <li class="breadcrumb-item"><a href="javascript: void(0)"><?php echo htmlspecialchars($item); ?></a></li>
      <?php endforeach; ?>
      <li class="breadcrumb-item" aria-current="page"><?php echo htmlspecialchars($activeItem); ?></li>
    </ul>
  </div>
</div>
<!-- [ breadcrumb ] end -->

