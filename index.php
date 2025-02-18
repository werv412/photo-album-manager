<?php
require 'functions.php';

$albumId = $_GET['id'] ?? 7;
$contents = getAlbumContents($albumId);
$albumTitle = getAlbumTitle($albumId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($albumTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: sans-serif;
            background-color: #f8f9fa;
            color: #343a40;
        }

        .container-fluid {
            padding-top: 2rem;
            padding-bottom: 2rem;
        }

        h1 {
            font-weight: 600;
            color: #212529;
            margin-bottom: 1.5rem;
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
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-img-top {
            overflow: hidden;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .card-img-top img {
            max-width: 100%;
            margin: 1rem;
            max-height: 250px; /* Adjust as needed or remove for completely dynamic height */
            object-fit: contain;
            transition: opacity 0.3s;
        }

        .card-img-top::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.2);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .card:hover .card-img-top::before {
            opacity: 1;
        }
        .card:hover .card-img-top img {
            opacity: 0.8;
        }

        .card-body {
            padding: 1.25rem;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 500;
            color: #212529;
            margin-bottom: 0.5rem;
        }

        .card-text {
            font-size: 0.9rem;
            color: #6c757d;
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
<body class="container-fluid">

    <h1 class="text-center"><?= htmlspecialchars($albumTitle) ?></h1>

    <div class="container">
        <?php $parents = getParents($albumId); ?>
        <?php if ($parents): ?>
            <div class="gbBreadCrumb">
                <div class="block-core-BreadCrumb">
                    <?php foreach ($parents as $index => $parent): ?>
                        <a href="index.php?id=<?= $parent->g_id ?>">
                            <?= htmlspecialchars($parent->g_title) ?>
                        </a> / 
                    <?php endforeach; ?>
                </div>
            </div>       
        <?php endif; ?>
        <br/>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
            <?php foreach ($contents as $item): ?>
                <div class="col">
                    <div class="card h-100">
                        <div class="card-img-top">
                            <?php if ($item['g_entityType'] == 'GalleryPhotoItem'): ?>
                                <?php 
                                $image = getPreImage($item['g_id']); 
                                $imagePath = $image ? htmlspecialchars($image['fullPath']) : 'images/default.jpg';
                                ?>
                                <a href="view.php?id=<?= $item['g_id'] ?>">
                                    <img src="<?= $imagePath ?>"  
                                         alt="<?= htmlspecialchars($item['g_title']) ?>"
                                         height="<?= htmlspecialchars($image['pre_height']) ?>"
                                         width="<?= htmlspecialchars($image['pre_width']) ?>"
                                         onerror="this.onerror=null; this.src='images/default.jpg';"
                                         loading="lazy">
                                </a>
                            <?php elseif ($item['g_entityType'] == 'GalleryAlbumItem'): ?>
                                <?php 
                                $cover = getAlbumCover($item['g_id']); 
                                $coverPath = $cover ? htmlspecialchars($cover['fullPath']) : 'images/default.jpg';
                                ?>
                                <a href="index.php?id=<?= $item['g_id'] ?>">
                                    <img src="<?= $coverPath ?>" 
                                         alt="<?= htmlspecialchars($item['g_title']) ?>"
                                         height="<?= htmlspecialchars($cover['pre_height']) ?>"
                                         width="<?= htmlspecialchars($cover['pre_width']) ?>"
                                         onerror="this.onerror=null; this.src='images/default.jpg';"
                                         loading="lazy">
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php $details = getEntityInfo($item['g_id']); ?>
                            <?php if ($item['g_entityType'] == 'GalleryPhotoItem'): ?>
                                <h5 class="card-title"><?= htmlspecialchars($item['g_title']) ?></h5>
                                <div class="card-text">👁 Visits: <?= htmlspecialchars($details['g_viewCount']) ?></div>
                                <div class="card-text">📅 Date: <?= htmlspecialchars($details['createdAt']) ?></div>
                            <?php elseif ($item['g_entityType'] == 'GalleryAlbumItem'): ?>
                                <h5 class="card-title">📂 Album: <?= htmlspecialchars($item['g_title']) ?></h5>
                                <div class="card-text">👁 Visits: <?= htmlspecialchars($details['g_viewCount']) ?></div>
                                <div class="card-text">📅 Date: <?= htmlspecialchars($details['createdAt']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>