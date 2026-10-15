<?php

session_start();

require_once "../config/database.php";

// Admin authentication
if (
    !isset($_SESSION["user"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "admin"
) {
    header("Location: admin-login.php");
    exit;
}

$message = "";
$messageType = "";

$name = "";
$location = "";
$rooms = "";
$price = "";
$type = "";
$description = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $roomsInput = trim($_POST["rooms"] ?? "");
    $priceInput = trim($_POST["price"] ?? "");
    $type = trim($_POST["type"] ?? "");
    $description = trim($_POST["description"] ?? "");

    // Keep submitted values if validation fails
    $rooms = $roomsInput;
    $price = $priceInput;

    // Validate fields
    if (
        $name === "" ||
        $location === "" ||
        $roomsInput === "" ||
        $priceInput === "" ||
        $type === ""
    ) {
        $message = "Please complete all required fields.";
        $messageType = "error";

    } elseif (
        filter_var($roomsInput, FILTER_VALIDATE_INT) === false ||
        (int)$roomsInput < 1
    ) {
        $message = "Please enter a valid number of bedrooms.";
        $messageType = "error";

    } elseif (
        !is_numeric($priceInput) ||
        (float)$priceInput <= 0
    ) {
        $message = "Please enter a valid monthly price.";
        $messageType = "error";

    } else {

        $rooms = (int)$roomsInput;
        $price = (float)$priceInput;

        // Insert property into the database
        $sql = "INSERT INTO properties
                (name, location, rooms, price, type, description)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if ($stmt) {

            $stmt->bind_param(
                "ssidss",
                $name,
                $location,
                $rooms,
                $price,
                $type,
                $description
            );

            if ($stmt->execute()) {

                $message = "Property added successfully!";
                $messageType = "success";

                // Clear form after successful submission
                $name = "";
                $location = "";
                $rooms = "";
                $price = "";
                $type = "";
                $description = "";

            } else {

                $message = "Could not add property. " . $stmt->error;
                $messageType = "error";

            }

            $stmt->close();

        } else {

            $message = "Database query error: " . $conn->error;
            $messageType = "error";

        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SmartRent | Add New Property</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #0f172a;
            color: #f8fafc;
            min-height: 100vh;
        }

        header {
            background: #020617;
            padding: 20px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            border-bottom: 1px solid #334155;
        }

        header h1 {
            color: #fbbf24;
            font-size: 24px;
        }

        .back-link {
            background: #1e293b;
            color: #fff;
            text-decoration: none;
            padding: 11px 17px;
            border-radius: 8px;
        }

        .back-link:hover {
            background: #fbbf24;
            color: #0f172a;
        }

        .container {
            width: 100%;
            max-width: 850px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .form-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 18px;
            padding: 32px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        .form-card h2 {
            font-size: 27px;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #94a3b8;
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 25px;
        }

        .message {
            padding: 14px;
            border-radius: 9px;
            margin-bottom: 22px;
            font-size: 14px;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .success {
            background: #064e3b;
            color: #a7f3d0;
            border: 1px solid #059669;
        }

        .error {
            background: #7f1d1d;
            color: #fecaca;
            border: 1px solid #dc2626;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 9px;
            font-size: 13px;
            font-weight: bold;
            color: #cbd5e1;
        }

        input,
        select,
        textarea {
            display: block;
            width: 100%;
            padding: 13px 14px;
            background: #0f172a;
            border: 1px solid #475569;
            border-radius: 9px;
            color: #f8fafc;
            font-family: Arial, sans-serif;
            font-size: 14px;
            outline: none;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #fbbf24;
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        .submit-btn {
            display: block;
            width: 100%;
            border: none;
            padding: 15px;
            background: #fbbf24;
            color: #0f172a;
            border-radius: 10px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
        }

        .submit-btn:hover {
            background: #f59e0b;
            transform: translateY(-2px);
        }

        footer {
            text-align: center;
            padding: 25px;
            color: #94a3b8;
            font-size: 13px;
        }

        @media (max-width: 600px) {

            .form-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .form-card {
                padding: 22px;
            }

            .form-card h2 {
                font-size: 22px;
            }

            header h1 {
                font-size: 20px;
            }

        }

    </style>

</head>

<body>

<header>

    <h1>🏠 SmartRent Admin</h1>

    <a href="index.php" class="back-link">
        ← Admin Dashboard
    </a>

</header>

<main class="container">

    <section class="form-card">

        <h2>➕ Add New Property</h2>

        <p class="subtitle">
            Enter the property details below to publish your rental property.
        </p>

        <?php if ($message !== ""): ?>

            <div
                class="message <?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>"
                role="alert"
            >
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-grid">

                <div class="form-group">

                    <label for="name">Property Name *</label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        maxlength="150"
                        value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="location">Location *</label>

                    <input
                        type="text"
                        id="location"
                        name="location"
                        maxlength="150"
                        value="<?= htmlspecialchars($location, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="rooms">Number of Bedrooms *</label>

                    <input
                        type="number"
                        id="rooms"
                        name="rooms"
                        min="1"
                        max="100"
                        step="1"
                        value="<?= htmlspecialchars((string)$rooms, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="price">Monthly Price (ETB) *</label>

                    <input
                        type="number"
                        id="price"
                        name="price"
                        min="0.01"
                        step="0.01"
                        value="<?= htmlspecialchars((string)$price, ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >

                </div>

            </div>

            <div class="form-group">

                <label for="type">Property Type *</label>

                <select id="type" name="type" required>

                    <option value="">Select property type</option>

                    <option value="Apartment"
                        <?= $type === "Apartment" ? "selected" : "" ?>>
                        Apartment
                    </option>

                    <option value="House"
                        <?= $type === "House" ? "selected" : "" ?>>
                        House
                    </option>

                    <option value="Villa"
                        <?= $type === "Villa" ? "selected" : "" ?>>
                        Villa
                    </option>

                    <option value="Penthouse"
                        <?= $type === "Penthouse" ? "selected" : "" ?>>
                        Penthouse
                    </option>

                    <option value="Studio"
                        <?= $type === "Studio" ? "selected" : "" ?>>
                        Studio
                    </option>

                </select>

            </div>

            <div class="form-group">

                <label for="description">Property Description</label>

                <textarea
                    id="description"
                    name="description"
                ><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></textarea>

            </div>

            <button type="submit" class="submit-btn">
                ➕ Add Property
            </button>

        </form>

    </section>

</main>

<footer>
    &copy; <?= date("Y") ?> SmartRent Property Management System
</footer>

</body>

</html>