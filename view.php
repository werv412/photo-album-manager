<?php
require 'functions.php';
$imageId = $_GET['id'] ?? 0;
$image = getImage($imageId);

if (!$image) {
    die("Image not found.");
}

$width = $image['g_width'] ?? 800;
$height = $image['g_height'] ?? 600;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($image['g_title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: sans-serif;
            background-color: #f8f9fa;
            color: #343a40;
        }

        .container {
            padding-top: 2rem;
            padding-bottom: 2rem;
        }

        h1 {
            font-weight: 600;
            color: #212529;
            margin-bottom: 1.5rem;
            text-align: center; /* Center the title */
        }

        .gbBreadCrumb {
            margin-bottom: 1rem;
        }

        .block-core-BreadCrumb a {
            color: #007bff;
            text-decoration: none;
        }
        .block-core-BreadCrumb a:hover {
            color: #0056b3;
        }
        .block-core-BreadCrumb span {
            color: #6c757d;
        }

        .card {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); /* More pronounced shadow */
        }

        .card-body {
            padding: 1.5rem;
        }

        .card-text {
            font-size: 1rem;
            color: #495057; /* Slightly darker text */
            margin-bottom: 1rem; /* Add margin below the description */
        }

        .image-container {
            overflow: hidden;
            padding: 1rem; /* Reduced padding around the image */
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f0f0f0;  /* Light gray background for image container */
            border-top: 1px solid #dee2e6; /* Add a top border to separate content */
        }

        .image-container img {
            max-width: 95%; /* Slightly smaller image for better fit */
            max-height: 90vh; /* Limit max height to viewport height */
            object-fit: contain;
        }

        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }

        .btn-secondary:hover {
            background-color: #5c636a;
            border-color: #565e64;
        }
    </style>
</head>
<body class="container">

    <h1><?= htmlspecialchars($image['g_title']) ?></h1>
    <?php $parents = getParents($imageId); ?>
    <div class="gbBreadCrumb">
        <div class="block-core-BreadCrumb">
            <?php foreach ($parents as $index => $parent): ?>
                <a href="index.php?id=<?= $parent->g_id ?>">
                    <?= htmlspecialchars($parent->g_title) ?>
                </a> / 
            <?php endforeach; ?>
            <span class="BreadCrumb-<?= count($parents) + 1 ?>">
                <?= htmlspecialchars($image['g_title']) ?>
            </span>
        </div>
    </div>
    <br/>

    <div class="card shadow">
        <div class="card-body">
            <p class="card-text"><?= nl2br(htmlspecialchars($image['g_description'])) ?></p>
        </div>
        <div class="image-container">
            <img src="<?= htmlspecialchars($image['fullPath']) ?>" alt="<?= htmlspecialchars($image['g_title']) ?>"
                 onerror="this.onerror=null; this.src='images/default.jpg';" loading="lazy">
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>