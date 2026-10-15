<?php
session_start();

require_once __DIR__ . "/../config/database.php";

// Admin authentication
if (
    !isset($_SESSION["user"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: admin-login.php");
    exit;
}

// Create CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";

// Upload directory
$uploadDir = __DIR__ . "/../uploads/properties/";

// Create upload directory if it does not exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Allowed image formats
$allowedTypes = [
    "image/jpeg" => "jpg",
    "image/png"  => "png",
    "image/webp" => "webp"
];

$maxSize = 5 * 1024 * 1024;

// =====================================
// DELETE PROPERTY IMAGE
// =====================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["delete_image_id"])
) {
    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        $message = "Security verification failed. Please try again.";
    } else {
        $imageId = filter_var(
            $_POST["delete_image_id"],
            FILTER_VALIDATE_INT
        );

        if (!$imageId || $imageId < 1) {
            $message = "Invalid image ID.";
        } else {
            // Find the image record
            $stmt = $conn->prepare(
                "SELECT image_name
                 FROM property_images
                 WHERE id = ?"
            );

            $stmt->bind_param("i", $imageId);
            $stmt->execute();

            $image = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$image) {
                $message = "Image not found.";
            } else {
                // Use basename to prevent directory traversal
                $fileName = basename($image["image_name"]);
                $filePath = $uploadDir . $fileName;

                // Delete the database record
                $deleteStmt = $conn->prepare(
                    "DELETE FROM property_images WHERE id = ?"
                );

                $deleteStmt->bind_param("i", $imageId);

                if ($deleteStmt->execute()) {
                    $message = "Property image deleted successfully!";

                    // Delete the actual file if it exists
                    if (is_file($filePath)) {
                        if (!unlink($filePath)) {
                            $message .=
                                " The database record was deleted, but the image file could not be removed.";
                        }
                    }
                } else {
                    $message = "Could not delete the image.";
                }

                $deleteStmt->close();
            }
        }
    }
}

// =====================================
// UPLOAD PROPERTY IMAGES
// =====================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["upload_images"])
) {
    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        $message = "Security verification failed. Please try again.";
    } else {
        $propertyId = filter_var(
            $_POST["property_id"] ?? "",
            FILTER_VALIDATE_INT
        );

        if (
            !$propertyId ||
            !isset($_FILES["images"]) ||
            empty($_FILES["images"]["name"][0])
        ) {
            $message = "Please select a property and choose at least one image.";
        } else {
            // Verify property exists
            $check = $conn->prepare(
                "SELECT id FROM properties WHERE id = ?"
            );

            $check->bind_param("i", $propertyId);
            $check->execute();

            $selectedProperty = $check->get_result()->fetch_assoc();
            $check->close();

            if (!$selectedProperty) {
                $message = "The selected property was not found.";
            } else {
                $uploaded = 0;
                $errors = [];

                $stmt = $conn->prepare(
                    "INSERT INTO property_images
                    (property_id, image_name)
                    VALUES (?, ?)"
                );

                $fileCount = count($_FILES["images"]["name"]);

                for ($i = 0; $i < $fileCount; $i++) {
                    $errorCode = $_FILES["images"]["error"][$i];
                    $tmpName = $_FILES["images"]["tmp_name"][$i];
                    $fileSize = (int) $_FILES["images"]["size"][$i];

                    if ($errorCode !== UPLOAD_ERR_OK) {
                        $errors[] = "One image could not be uploaded.";
                        continue;
                    }

                    if (
                        !is_uploaded_file($tmpName) ||
                        $fileSize <= 0 ||
                        $fileSize > $maxSize
                    ) {
                        $errors[] = "Each image must be smaller than 5 MB.";
                        continue;
                    }

                    // Verify actual image content
                    $imageInfo = @getimagesize($tmpName);

                    if ($imageInfo === false) {
                        $errors[] = "An uploaded file is not a valid image.";
                        continue;
                    }

                    $mime = $imageInfo["mime"] ?? "";

                    if (!isset($allowedTypes[$mime])) {
                        $errors[] = "Only JPG, PNG, and WEBP images are allowed.";
                        continue;
                    }

                    // Generate a random filename
                    $extension = $allowedTypes[$mime];
                    $fileName = bin2hex(random_bytes(16)) . "." . $extension;

                    $destination = $uploadDir . $fileName;

                    if (!move_uploaded_file($tmpName, $destination)) {
                        $errors[] = "Could not save an uploaded image.";
                        continue;
                    }

                    // Save image information to database
                    $stmt->bind_param("is", $propertyId, $fileName);

                    if ($stmt->execute()) {
                        $uploaded++;
                    } else {
                        if (is_file($destination)) {
                            unlink($destination);
                        }

                        $errors[] = "Could not save image information.";
                    }
                }

                $stmt->close();

                if ($uploaded > 0) {
                    $message = $uploaded .
                        " image(s) uploaded successfully!";
                }

                if (!empty($errors)) {
                    if ($message !== "") {
                        $message .= " ";
                    }

                    $message .= implode(" ", array_unique($errors));
                }
            }
        }
    }
}

// =====================================
// GET PROPERTY LIST
// =====================================

$properties = $conn->query(
    "SELECT id, name, location
     FROM properties
     ORDER BY id DESC"
);

if (!$properties) {
    die("Could not load properties. Check your database.");
}

// =====================================
// GET UPLOADED IMAGES
// =====================================

$images = $conn->query(
    "SELECT
        pi.id,
        pi.image_name,
        p.name AS property_name,
        p.location
     FROM property_images pi
     INNER JOIN properties p ON p.id = pi.property_id
     ORDER BY pi.id DESC"
);

if (!$images) {
    die("Could not load property images. Check your database table.");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Property Images | SmartRent</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #172b4d;
        }

        .container {
            max-width: 1100px;
            margin: auto;
        }

        h1 {
            color: #17365d;
            margin-bottom: 10px;
        }

        h2 {
            margin-top: 0;
            margin-bottom: 20px;
        }

        .subtitle {
            color: #64748b;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .card {
            background: white;
            padding: 25px;
            margin-bottom: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.06);
        }

        label {
            display: block;
            margin: 18px 0 8px;
            font-weight: bold;
        }

        select,
        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            background: white;
        }

        button,
        .back-link {
            display: inline-block;
            padding: 12px 18px;
            margin-top: 15px;
            border: none;
            border-radius: 7px;
            background: #1677f2;
            color: white;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }

        button:hover,
        .back-link:hover {
            background: #075ec8;
        }

        .message {
            padding: 13px;
            margin-bottom: 18px;
            border-radius: 7px;
            background: #e0f2fe;
            color: #075985;
            line-height: 1.6;
        }

        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
        }

        .image-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            overflow: hidden;
        }

        .image-card img {
            width: 100%;
            height: 190px;
            object-fit: cover;
            display: block;
        }

        .image-info {
            padding: 15px;
        }

        .image-info p {
            margin: 8px 0;
            color: #64748b;
            font-size: 14px;
            line-height: 1.5;
        }

        .delete-form {
            padding: 0 15px 15px;
        }

        .delete-btn {
            width: 100%;
            background: #ef4444;
            color: white;
            font-weight: bold;
        }

        .delete-btn:hover {
            background: #dc2626;
        }

        .empty-message {
            color: #64748b;
            line-height: 1.6;
        }

        .help-text {
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
        }

        @media (max-width: 600px) {
            body {
                padding: 15px;
            }

            .card {
                padding: 18px;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <h1>🏠 Property Image Management</h1>

    <p class="subtitle">
        Upload new property photos and delete photos you no longer need.
    </p>

    <a class="back-link" href="index.php">
        ← Back to Dashboard
    </a>

    <!-- Upload Form -->
    <section class="card">

        <h2>📸 Upload Property Photos</h2>

        <?php if ($message !== ""): ?>
            <div class="message">
                <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION["csrf_token"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>"
            >

            <label for="property_id">Select Property</label>

            <select name="property_id" id="property_id" required>

                <option value="">-- Choose a property --</option>

                <?php while ($p = $properties->fetch_assoc()): ?>

                    <option value="<?= (int) $p["id"] ?>">

                        <?= htmlspecialchars(
                            $p["name"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                        -

                        <?= htmlspecialchars(
                            $p["location"],
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>

                    </option>

                <?php endwhile; ?>

            </select>

            <label for="images">Choose Property Images</label>

            <input
                type="file"
                name="images[]"
                id="images"
                accept=".jpg,.jpeg,.png,.webp"
                multiple
                required
            >

            <p class="help-text">
                Accepted formats: JPG, PNG, WEBP.
                Maximum size: 5 MB per image.
                You can select multiple photos.
            </p>

            <button type="submit" name="upload_images">
                ⬆ Upload Images
            </button>

        </form>

    </section>

    <!-- Uploaded Image Gallery -->
    <section class="card">

        <h2>🖼️ Uploaded Property Images</h2>

        <div class="gallery">

            <?php if ($images->num_rows > 0): ?>

                <?php while ($img = $images->fetch_assoc()): ?>

                    <div class="image-card">

                        <img
                            src="../uploads/properties/<?= rawurlencode(
                                $img["image_name"]
                            ) ?>"
                            alt="<?= htmlspecialchars(
                                $img["property_name"],
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                            loading="lazy"
                        >

                        <div class="image-info">

                            <strong>
                                <?= htmlspecialchars(
                                    $img["property_name"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </strong>

                            <p>
                                📍
                                <?= htmlspecialchars(
                                    $img["location"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </p>

                        </div>

                        <!-- Delete Image Form -->
                        <form
                            method="POST"
                            class="delete-form"
                            onsubmit="return confirm('Are you sure you want to permanently delete this property image?');"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= htmlspecialchars(
                                    $_SESSION["csrf_token"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                            >

                            <input
                                type="hidden"
                                name="delete_image_id"
                                value="<?= (int) $img["id"] ?>"
                            >

                            <button
                                type="submit"
                                class="delete-btn"
                            >
                                🗑️ Delete Image
                            </button>

                        </form>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <p class="empty-message">
                    No property images uploaded yet.
                    Upload your first property photo above.
                </p>

            <?php endif; ?>

        </div>

    </section>

</div>

</body>
</html>