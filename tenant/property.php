<?php
session_start();
require_once "../config/database.php";

// Check property ID
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Invalid property ID.");
}

$id = (int) $_GET["id"];

// Get property details
$sql = "SELECT * FROM properties WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$property = $result->fetch_assoc();

$stmt->close();

if (!$property) {
    die("Property not found.");
}

// Property information
$name = $property["name"] ?? "Unnamed Property";
$location = $property["location"] ?? "Location not available";
$rooms = $property["rooms"] ?? "N/A";
$type = $property["type"] ?? "N/A";
$price = $property["price"] ?? 0;
$description = $property["description"] ?? "No description available";

// Get images for this property
$images = [];

$imageSql = "SELECT image_name
             FROM property_images
             WHERE property_id = ?
             ORDER BY id DESC";

$imageStmt = $conn->prepare($imageSql);

if (!$imageStmt) {
    die("Image database error: " . htmlspecialchars($conn->error));
}

$imageStmt->bind_param("i", $id);
$imageStmt->execute();

$imageResult = $imageStmt->get_result();

while ($imageRow = $imageResult->fetch_assoc()) {
    $images[] = $imageRow["image_name"];
}

$imageStmt->close();

// Escape output safely
function escapeHtml($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo escapeHtml($name); ?> - SmartRent</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 25px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        h1 {
            margin-top: 0;
            color: #1d4ed8;
        }

        .property-gallery {
            display: grid;
            grid-template-columns: repeat(
                auto-fit,
                minmax(220px, 1fr)
            );
            gap: 15px;
            margin: 20px 0 25px;
        }

        .property-photo {
            display: block;
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 10px;
            background: #e2e8f0;
        }

        .no-image {
            min-height: 200px;
            padding: 20px;
            background: #dbeafe;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 20px;
            color: #475569;
            border-radius: 10px;
            grid-column: 1 / -1;
        }

        .details {
            margin-top: 20px;
            line-height: 1.8;
        }

        .details p {
            margin: 8px 0;
        }

        .price {
            color: #15803d;
            font-size: 23px;
            font-weight: bold;
        }

        .map-container {
            margin-top: 25px;
        }

        .map-container iframe {
            width: 100%;
            height: 300px;
            border: 0;
            border-radius: 10px;
        }

        .buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 25px;
        }

        .btn {
            display: inline-block;
            padding: 12px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            text-align: center;
        }

        .book-btn {
            background: #2563eb;
            color: white;
        }

        .back-btn {
            background: #e2e8f0;
            color: #1e293b;
        }

        .btn:hover {
            opacity: 0.85;
        }

        @media (max-width: 600px) {
            body {
                padding: 12px;
            }

            .container {
                padding: 16px;
            }

            .property-photo {
                height: 220px;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <h1><?php echo escapeHtml($name); ?></h1>

    <!-- Property images uploaded by Admin -->
    <div class="property-gallery">

        <?php if (!empty($images)): ?>

            <?php foreach ($images as $image): ?>

                <?php
                $imageFile = basename($image);
                $imagePath = "../uploads/properties/" . $imageFile;
                ?>

                <img
                    src="<?php echo escapeHtml($imagePath); ?>"
                    alt="<?php echo escapeHtml($name); ?>"
                    class="property-photo"
                    onerror="this.style.display='none';"
                >

            <?php endforeach; ?>

        <?php else: ?>

            <div class="no-image">
                🏠 No photos available for this property.
            </div>

        <?php endif; ?>

    </div>

    <!-- Property details -->
    <div class="details">

        <p>
            <strong>Location:</strong>
            <?php echo escapeHtml($location); ?>
        </p>

        <p>
            <strong>Rooms:</strong>
            <?php echo escapeHtml($rooms); ?>
        </p>

        <p>
            <strong>Property Type:</strong>
            <?php echo escapeHtml($type); ?>
        </p>

        <p class="price">
            Price: <?php echo number_format((float)$price, 2); ?>
        </p>

        <p>
            <strong>Description:</strong><br>
            <?php echo nl2br(escapeHtml($description)); ?>
        </p>

    </div>

    <!-- Google Maps -->
    <div class="map-container">

        <h2>Location Map</h2>

        <iframe
            src="https://maps.google.com/maps?q=<?php echo rawurlencode($location); ?>&output=embed"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            allowfullscreen>
        </iframe>

    </div>

    <!-- Buttons -->
    <div class="buttons">

        <a
            href="booking.php?id=<?php echo $id; ?>"
            class="btn book-btn">
            Book This Property
        </a>

        <a
            href="properties.php"
            class="btn back-btn">
            Back to Properties
        </a>

    </div>

</div>

</body>
</html>