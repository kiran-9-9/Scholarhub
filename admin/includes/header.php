<?php
// This file contains the common header for admin pages
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ScholarHub Admin' : 'ScholarHub Admin'; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php if (isset($additionalStyles)): ?>
    <?php echo $additionalStyles; ?>
    <?php endif; ?>
</head>
<body>
    <div class="admin-container">
        <?php include 'sidebar.php'; ?>
        <div class="admin-content">
            <?php if (isset($contentHeader)): ?>
            <div class="content-header">
                <h1><?php echo $contentHeader; ?></h1>
                <?php if (isset($contentSubHeader)): ?>
                <p><?php echo $contentSubHeader; ?></p>
                <?php endif; ?>
                <?php if (isset($actionButton)): ?>
                <div class="action-buttons">
                    <?php echo $actionButton; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?> 