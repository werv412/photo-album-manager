<?php
require 'db.php';

// Fetch album title
function getAlbumTitle($albumId) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT g_title FROM galeria_item WHERE g_id = ?");
    $stmt->execute([$albumId]);
    
    return $stmt->fetchColumn() ?: "Album";
}

function getParents($id) {
    global $pdo;

    // Fetch the parent sequence for the given item ID
    $stmt = $pdo->prepare("
        SELECT g_parentSequence 
        FROM galeria_itemattributesmap 
        WHERE g_itemId = ?
    ");
    $stmt->execute([$id]);
    $parentSequence = $stmt->fetchColumn();

    if (!$parentSequence) {
        return null;  // If no parent sequence found, return null
    }

    // Split the parent sequence into individual IDs
    $parentIds = explode('/', trim($parentSequence, '/'));

    // Initialize an array to store the parent details
    $parents = [];

    // Loop through each parent ID to get its title
    foreach ($parentIds as $parentId) {
        $stmt = $pdo->prepare("
            SELECT g_id, g_title 
            FROM galeria_item 
            WHERE g_id = ?
        ");
        $stmt->execute([$parentId]);
        $parent = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($parent) {
            $parents[] = (object)[
                'g_id' => $parent['g_id'],
                'g_title' => $parent['g_title']
            ];
        }
    }

    // Return the array of parent objects
    return $parents;
}



function getEntityInfo($albumId) {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(FROM_UNIXTIME(ge.g_creationTimestamp, '%m/%d/%y'), NULL) AS createdAt,
            COALESCE(gia.g_viewCount, 0) AS g_viewCount,
            COALESCE(gc.g_parentId, NULL) AS parentId
        FROM galeria_entity ge
        LEFT JOIN galeria_itemattributesmap gia ON ge.g_id = gia.g_itemId
        LEFT JOIN galeria_childentity gc ON ge.g_id = gc.g_id
        WHERE ge.g_id = ?
    ");
    $stmt->execute([$albumId]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'createdAt' => $result['createdAt'] ?? null, 
        'g_viewCount' => $result['g_viewCount'] ?? 0,
        'parentId' => $result['parentId'] ?? null
    ];
}



// Fetch album contents (sub-albums or photos)
function getAlbumContents($parentId) {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT i.g_id, i.g_title, e.g_entityType, f.g_pathComponent
        FROM galeria_item i
        JOIN galeria_entity e ON i.g_id = e.g_id
        LEFT JOIN galeria_filesystementity f ON i.g_id = f.g_id
        JOIN galeria_childentity c ON i.g_id = c.g_id
        JOIN galeria_itemattributesmap iam ON i.g_id = iam.g_itemId
        WHERE c.g_parentId = ?
        ORDER BY iam.g_orderWeight;
    ");
    $stmt->execute([$parentId]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch the album cover using the same logic as images
function getAlbumCover($albumId) {
    global $pdo;

    while (true) {
        // Step 1: Get children (g_id) from galeria_childentity where g_parentId matches albumId
        $stmt = $pdo->prepare("SELECT g_id FROM galeria_childentity WHERE g_parentId = ?");
        $stmt->execute([$albumId]);
        $children = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!$children) {
            return null; // No children found for the given albumId
        }

        // Step 2: Search in galeria_itemattributesmap where g_itemId is in children and g_parentSequence contains albumId
        $placeholders = implode(',', array_fill(0, count($children), '?'));
        $stmt = $pdo->prepare("SELECT * FROM galeria_itemattributesmap 
                               WHERE g_itemId IN ($placeholders) 
                               AND g_parentSequence LIKE ? 
                               ORDER BY g_orderWeight ASC");
        $stmt->execute(array_merge($children, ["%$albumId%"]));
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return null; // No matching records found
        }

        $g_itemId = $result['g_itemId'];

        // Step 2.5: Check if g_itemId is an album (GalleryAlbumItem) or a photo (GalleryPhotoItem)
        $stmt = $pdo->prepare("SELECT g_entityType FROM galeria_entity WHERE g_id = ?");
        $stmt->execute([$g_itemId]);
        $entityType = $stmt->fetchColumn();

        if ($entityType === 'GalleryPhotoItem') {
            break; // Found a valid photo, proceed to Step 3
        }

        // If it's an album (GalleryAlbumItem), repeat the process with the new albumId
        $albumId = $g_itemId;
    }

    // Step 3: Construct the full path using g_parentSequence

    $coverImage = getPreImage($g_itemId);
    
    return $coverImage;
}




// Fetch image details using the same logic for paths
function getImage($imageId) {
    global $pdo;

    // Fetch the parent sequence
    $stmt = $pdo->prepare("
        SELECT g_parentSequence 
        FROM galeria_itemattributesmap 
        WHERE g_itemId = ?
    ");
    $stmt->execute([$imageId]);
    $parentSequence = $stmt->fetchColumn();

    if (!$parentSequence) {
        return null;
    }

    // Split sequence into album IDs
    $parentIds = explode('/', trim($parentSequence, '/'));

    // Fetch path components
    $pathParts = [];
    foreach ($parentIds as $parentId) {
        $stmt = $pdo->prepare("SELECT g_pathComponent FROM galeria_filesystementity WHERE g_id = ?");
        $stmt->execute([$parentId]);
        $pathComponent = $stmt->fetchColumn();
        if ($pathComponent) {
            $pathParts[] = $pathComponent;
        }
    }

    // Fetch image details from galeria_filesystementity and galeria_item
    $stmt = $pdo->prepare("
        SELECT f.g_pathComponent, i.g_title, i.g_description 
        FROM galeria_filesystementity f
        JOIN galeria_item i ON f.g_id = i.g_id
        WHERE f.g_id = ?
    ");
    $stmt->execute([$imageId]);
    $imageData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($imageData) {
        // Fetch image dimensions from galeria_photoitem
        $stmt = $pdo->prepare("
            SELECT g_width, g_height 
            FROM galeria_photoitem 
            WHERE g_id = ?
        ");
        $stmt->execute([$imageId]);
        $dimensions = $stmt->fetch(PDO::FETCH_ASSOC);

        // Add width and height to image data
        if ($dimensions) {
            $imageData['g_width'] = $dimensions['g_width'];
            $imageData['g_height'] = $dimensions['g_height'];
        } else {
            $imageData['g_width'] = null;
            $imageData['g_height'] = null;
        }

        // Construct full image path
        $imageData['fullPath'] = 'images/' . implode('/', $pathParts) . '/' . $imageData['g_pathComponent'];

        return $imageData;
    }

    return null;
}

function getPreImage($imageId) {
    global $pdo;

    // Fetch the parent sequence
    $stmt = $pdo->prepare("
        SELECT g_parentSequence 
        FROM galeria_itemattributesmap 
        WHERE g_itemId = ?
    ");
    $stmt->execute([$imageId]);
    $parentSequence = $stmt->fetchColumn();

    if (!$parentSequence) {
        return null;
    }

    // Split sequence into album IDs
    $parentIds = explode('/', trim($parentSequence, '/'));

    // Fetch path components
    $pathParts = [];
    foreach ($parentIds as $parentId) {
        $stmt = $pdo->prepare("SELECT g_pathComponent FROM galeria_filesystementity WHERE g_id = ?");
        $stmt->execute([$parentId]);
        $pathComponent = $stmt->fetchColumn();
        if ($pathComponent) {
            $pathParts[] = $pathComponent;
        }
    }

    // Fetch image details from galeria_filesystementity
    $stmt = $pdo->prepare("
        SELECT f.g_pathComponent 
        FROM galeria_filesystementity f
        WHERE f.g_id = ?
    ");
    $stmt->execute([$imageId]);
    $imageData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$imageData) {
        return null;  // If no image data found, return null
    }

    // Construct full image path
    $imageData['fullPath'] = 'images/' . implode('/', $pathParts) . '/' . $imageData['g_pathComponent'];

    // Fetch pre-resolution dimensions (width and height)
    $stmt = $pdo->prepare("
    SELECT g_id 
    FROM galeria_derivative 
    WHERE g_derivativeSourceId = ?
");
$stmt->execute([$imageId]);
$derivative = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$derivative) {
    return null;  // If no derivative source found, return null
}

// Use the g_id from galeria_derivative to search in galeria_derivativeimage
$stmt = $pdo->prepare("
    SELECT g_width, g_height 
    FROM galeria_derivativeimage 
    WHERE g_id = ?
");
$stmt->execute([$derivative['g_id']]);
$dimensions = $stmt->fetch(PDO::FETCH_ASSOC);


    // Add pre-resolution dimensions to the image data array
    if ($dimensions) {
        $imageData['pre_width'] = $dimensions['g_width'];
        $imageData['pre_height'] = $dimensions['g_height'];
    } else {
        $imageData['pre_width'] = null;
        $imageData['pre_height'] = null;
    }

    return $imageData;
}


?>
