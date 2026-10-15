<?php
session_start();
require_once "../config/database.php";

// Escape HTML output
function e($value)
{
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}

// Search and filter values
$search = trim($_GET["search"] ?? "");
$location = trim($_GET["location"] ?? "");
$type = trim($_GET["type"] ?? "");
$minPrice = trim($_GET["min_price"] ?? "");
$maxPrice = trim($_GET["max_price"] ?? "");
$rooms = trim($_GET["rooms"] ?? "");

// Load properties
$sql = "SELECT id, name, location, rooms, price, type, description
        FROM properties
        WHERE 1=1";

$params = [];
$paramTypes = "";

if ($search !== "") {
    $sql .= " AND (
        name LIKE ?
        OR location LIKE ?
        OR description LIKE ?
        OR type LIKE ?
    )";

    $keyword = "%" . $search . "%";

    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;

    $paramTypes .= "ssss";
}

if ($location !== "") {
    $sql .= " AND location LIKE ?";
    $params[] = "%" . $location . "%";
    $paramTypes .= "s";
}

if ($type !== "") {
    $sql .= " AND type = ?";
    $params[] = $type;
    $paramTypes .= "s";
}

if ($minPrice !== "" && is_numeric($minPrice) && (float)$minPrice >= 0) {
    $sql .= " AND price >= ?";
    $params[] = (float)$minPrice;
    $paramTypes .= "d";
}

if ($maxPrice !== "" && is_numeric($maxPrice) && (float)$maxPrice >= 0) {
    $sql .= " AND price <= ?";
    $params[] = (float)$maxPrice;
    $paramTypes .= "d";
}

if ($rooms !== "" && ctype_digit($rooms)) {
    $sql .= " AND rooms = ?";
    $params[] = (int)$rooms;
    $paramTypes .= "i";
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Unable to load properties. Please check your database.");
}

if (!empty($params)) {
    $stmt->bind_param($paramTypes, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$properties = [];

while ($row = $result->fetch_assoc()) {
    $properties[] = $row;
}

$stmt->close();

// Load property images
$propertyImages = [];

$imageQuery = "
    SELECT property_id, image_name
    FROM property_images
    ORDER BY id DESC
";

$imageResult = $conn->query($imageQuery);

if ($imageResult) {
    while ($imageRow = $imageResult->fetch_assoc()) {
        $propertyId = (int)$imageRow["property_id"];

        // Keep the newest image for each property
        if (!isset($propertyImages[$propertyId])) {
            $propertyImages[$propertyId] = $imageRow["image_name"];
        }
    }
}

// Load available property types
$propertyTypes = [];

$typeResult = $conn->query(
    "SELECT DISTINCT type
     FROM properties
     WHERE type IS NOT NULL
     AND type <> ''
     ORDER BY type"
);

if ($typeResult) {
    while ($row = $typeResult->fetch_assoc()) {
        $propertyTypes[] = $row["type"];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SmartRent | Available Properties</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
        }

        .header {
            background: #0f172a;
            color: white;
            padding: 18px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .logo {
            font-size: 27px;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }

        .logo span {
            color: #38bdf8;
        }

        .nav-links {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 7px;
        }

        .nav-links a:hover {
            background: #334155;
        }

        .hero {
            background: linear-gradient(135deg, #0f172a, #1d4ed8);
            color: white;
            padding: 45px 5%;
        }

        .hero h1 {
            font-size: clamp(28px, 4vw, 42px);
            margin-bottom: 12px;
        }

        .hero p {
            color: #dbeafe;
            line-height: 1.7;
            max-width: 700px;
        }

        .container {
            max-width: 1250px;
            margin: auto;
            padding: 30px 24px 55px;
        }

        .filter-box {
            background: white;
            padding: 25px;
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(15, 23, 42, .07);
            margin-bottom: 35px;
        }

        .filter-box h2 {
            font-size: 22px;
            margin-bottom: 20px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .field label {
            display: block;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .field input,
        .field select {
            width: 100%;
            padding: 13px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: white;
            color: #1e293b;
            font-size: 15px;
        }

        .filter-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 22px;
        }

        .search-button,
        .reset-button {
            padding: 13px 21px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
        }

        .search-button {
            background: #2563eb;
            color: white;
        }

        .reset-button {
            background: #e2e8f0;
            color: #1e293b;
        }

        .results-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 22px;
        }

        .results-heading h2 {
            font-size: 25px;
        }

        .results-count {
            color: #64748b;
            font-size: 14px;
        }

        .properties {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 24px;
        }

        .property {
            background: white;
            border-radius: 13px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(15, 23, 42, .08);
            transition: .2s;
        }

        .property:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(15, 23, 42, .14);
        }

        .property-image {
            position: relative;
            height: 225px;
            overflow: hidden;
            background: #dbeafe;
        }

        .property-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .image-fallback {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 65px;
            background: #dbeafe;
        }

        .type-badge {
            position: absolute;
            top: 13px;
            left: 13px;
            padding: 7px 11px;
            background: rgba(15, 23, 42, .88);
            color: white;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
        }

        .property-content {
            padding: 21px;
        }

        .property-content h3 {
            font-size: 21px;
            margin-bottom: 13px;
            overflow-wrap: anywhere;
        }

        .property-detail {
            color: #64748b;
            margin-bottom: 10px;
            line-height: 1.6;
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .price {
            color: #2563eb;
            font-size: 21px;
            font-weight: bold;
            margin: 18px 0;
        }

        .price small {
            font-size: 13px;
            color: #64748b;
            font-weight: normal;
        }

        .button {
            display: block;
            text-align: center;
            background: #2563eb;
            color: white;
            text-decoration: none;
            padding: 13px;
            border-radius: 8px;
            font-weight: bold;
        }

        .button:hover,
        .search-button:hover {
            background: #1d4ed8;
        }

        .empty {
            background: white;
            padding: 45px 25px;
            border-radius: 12px;
            text-align: center;
        }

        .empty-icon {
            font-size: 55px;
            margin-bottom: 15px;
        }

        .empty h2 {
            margin-bottom: 12px;
        }

        .empty p {
            color: #64748b;
            line-height: 1.7;
            margin-bottom: 18px;
        }

        .footer {
            text-align: center;
            background: #0f172a;
            color: #cbd5e1;
            padding: 22px;
            font-size: 14px;
        }

        @media (max-width: 950px) {
            .properties {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 600px) {
            .header {
                padding: 16px 20px;
            }

            .filter-box {
                padding: 18px;
            }

            .properties,
            .filter-grid {
                grid-template-columns: 1fr;
            }

            .filter-actions {
                flex-direction: column;
            }

            .search-button,
            .reset-button {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>

<header class="header">
    <a href="../index.html" class="logo">
        Smart<span>Rent</span>
    </a>

    <div class="nav-links">
        <a href="../index.html">Home</a>
        <a href="properties.php">Properties</a>
        <a href="../login.php">Login</a>
        <a href="../register.php">Register</a>
    </div>
</header>

<section class="hero">
    <h1>Find Your Perfect Home 🏡</h1>
    <p>
        Explore rental properties, compare monthly prices,
        and find a home that matches your needs.
    </p>
</section>

<main class="container">

    <section class="filter-box">
        <h2>🔎 Search &amp; Filter Properties</h2>

        <form method="GET" action="properties.php">
            <div class="filter-grid">

                <div class="field">
                    <label for="search">Property Name or Keyword</label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="<?php echo e($search); ?>"
                        placeholder="e.g. Luxury Villa"
                    >
                </div>

                <div class="field">
                    <label for="location">Location</label>
                    <input
                        type="text"
                        id="location"
                        name="location"
                        value="<?php echo e($location); ?>"
                        placeholder="e.g. Bahir Dar"
                    >
                </div>

                <div class="field">
                    <label for="type">Property Type</label>
                    <select id="type" name="type">
                        <option value="">All Types</option>

                        <?php foreach ($propertyTypes as $availableType): ?>
                            <option
                                value="<?php echo e($availableType); ?>"
                                <?php echo $type === $availableType ? "selected" : ""; ?>
                            >
                                <?php echo e($availableType); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field">
                    <label for="min_price">Minimum Monthly Price (ETB)</label>
                    <input
                        type="number"
                        id="min_price"
                        name="min_price"
                        min="0"
                        value="<?php echo e($minPrice); ?>"
                    >
                </div>

                <div class="field">
                    <label for="max_price">Maximum Monthly Price (ETB)</label>
                    <input
                        type="number"
                        id="max_price"
                        name="max_price"
                        min="0"
                        value="<?php echo e($maxPrice); ?>"
                    >
                </div>

                <div class="field">
                    <label for="rooms">Bedrooms</label>
                    <select id="rooms" name="rooms">
                        <option value="">Any Number</option>

                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option
                                value="<?php echo $i; ?>"
                                <?php echo $rooms === (string)$i ? "selected" : ""; ?>
                            >
                                <?php echo $i; ?>
                                Bedroom<?php echo $i > 1 ? "s" : ""; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

            </div>

            <div class="filter-actions">
                <button type="submit" class="search-button">
                    🔍 Search Properties
                </button>

                <a href="properties.php" class="reset-button">
                    Reset Filters
                </a>
            </div>
        </form>
    </section>

    <div class="results-heading">
        <h2>Available Properties</h2>

        <p class="results-count">
            <?php echo count($properties); ?>
            propert<?php echo count($properties) === 1 ? "y" : "ies"; ?>
            found
        </p>
    </div>

    <?php if (!empty($properties)): ?>

        <div class="properties">

            <?php foreach ($properties as $property): ?>

                <?php
                $propertyType = strtolower($property["type"] ?? "");

                if (strpos($propertyType, "villa") !== false) {
                    $fallbackIcon = "🏡";
                } elseif (strpos($propertyType, "apartment") !== false) {
                    $fallbackIcon = "🏢";
                } elseif (strpos($propertyType, "house") !== false) {
                    $fallbackIcon = "🏘️";
                } else {
                    $fallbackIcon = "🏠";
                }

                // Get the uploaded image for this property
                $imageFile = "";
                $propertyId = (int)$property["id"];

                if (!empty($propertyImages[$propertyId])) {
                    $imageFile = "../uploads/properties/" .
                        rawurlencode(basename($propertyImages[$propertyId]));
                }
                ?>

                <article class="property">

                    <div class="property-image">

                        <div
                            class="image-fallback"
                            style="<?php echo $imageFile !== '' ? 'display:none;' : ''; ?>"
                        >
                            <?php echo $fallbackIcon; ?>
                        </div>

                        <?php if ($imageFile !== ""): ?>
                            <img
                                src="<?php echo e($imageFile); ?>"
                                alt="<?php echo e($property["name"]); ?>"
                                onerror="this.style.display='none'; this.previousElementSibling.style.display='flex';"
                            >
                        <?php endif; ?>

                        <span class="type-badge">
                            <?php echo e($property["type"] ?: "Rental Property"); ?>
                        </span>

                    </div>

                    <div class="property-content">

                        <h3><?php echo e($property["name"]); ?></h3>

                        <p class="property-detail">
                            📍 <?php echo e($property["location"]); ?>
                        </p>

                        <p class="property-detail">
                            🛏 <?php echo (int)$property["rooms"]; ?> Bedrooms
                        </p>

                        <?php if (!empty($property["description"])): ?>
                            <p class="property-detail">
                                <?php
                                $description = (string)$property["description"];

                                if (strlen($description) > 120) {
                                    $description = substr($description, 0, 120) . "...";
                                }

                                echo e($description);
                                ?>
                            </p>
                        <?php endif; ?>

                        <div class="price">
                            <?php echo number_format((float)$property["price"], 2); ?>
                            ETB <small>/ month</small>
                        </div>

                        <a
                            href="property.php?id=<?php echo (int)$property["id"]; ?>"
                            class="button"
                        >
                            View Property Details →
                        </a>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="empty">
            <div class="empty-icon">🏠</div>

            <h2>No Properties Found</h2>

            <p>
                No properties match your search.
                Try different keywords or reset the filters.
            </p>

            <a href="properties.php" class="button">
                View All Properties
            </a>
        </div>

    <?php endif; ?>

</main>

<footer class="footer">
    &copy; <?php echo date("Y"); ?> SmartRent.
    Find a home that feels right.
</footer>

</body>
</html>