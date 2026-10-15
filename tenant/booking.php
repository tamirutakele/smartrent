<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["user"])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_SESSION["user_id"]) || !is_numeric($_SESSION["user_id"])) {
    exit("User ID is missing. Please log out and log in again.");
}

$user_id = (int) $_SESSION["user_id"];
$name = $_SESSION["user"];
$email = $_SESSION["email"] ?? "";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(404);
    exit("Property not found.");
}

/* Get property details */
$stmt = $conn->prepare(
    "SELECT id, name, location, rooms, price, type, description
     FROM properties
     WHERE id = ?
     LIMIT 1"
);

if (!$stmt) {
    exit("Database error.");
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$property = $result->fetch_assoc();
$stmt->close();

if (!$property) {
    http_response_code(404);
    exit("Property not found in the database.");
}

/*
 * Check whether this property has already been approved
 * for another booking.
 */
$stmt = $conn->prepare(
    "SELECT id
     FROM bookings
     WHERE property_id = ?
       AND status = 'Approved'
     LIMIT 1"
);

if (!$stmt) {
    exit("Database error while checking booking status.");
}

$stmt->bind_param("i", $id);
$stmt->execute();
$approvedBooking = $stmt->get_result()->fetch_assoc();
$stmt->close();

$isBooked = (bool) $approvedBooking;
$message = "";
$messageType = "error";

/* Process booking */
if ($_SERVER["REQUEST_METHOD"] === "POST" && !$isBooked) {

    $move_in_date = trim($_POST["move_in_date"] ?? "");

    $dateObject = DateTime::createFromFormat("!Y-m-d", $move_in_date);

    $validDate = $dateObject &&
        $dateObject->format("Y-m-d") === $move_in_date &&
        $move_in_date >= date("Y-m-d");

    if (!$validDate) {
        $message = "Please select a valid move-in date that is today or later.";
    } else {

        /*
         * Check again immediately before inserting.
         * The property ID is saved with the booking.
         */
        $conn->begin_transaction();

        try {
            $lockStmt = $conn->prepare(
                "SELECT id FROM properties WHERE id = ? FOR UPDATE"
            );

            if (!$lockStmt) {
                throw new Exception("Could not verify property availability.");
            }

            $lockStmt->bind_param("i", $id);
            $lockStmt->execute();
            $lockedProperty = $lockStmt->get_result()->fetch_assoc();
            $lockStmt->close();

            if (!$lockedProperty) {
                throw new Exception("Property not found.");
            }

            $checkStmt = $conn->prepare(
                "SELECT id FROM bookings
                 WHERE property_id = ?
                   AND status = 'Approved'
                 LIMIT 1"
            );

            if (!$checkStmt) {
                throw new Exception("Could not check booking status.");
            }

            $checkStmt->bind_param("i", $id);
            $checkStmt->execute();
            $alreadyApproved = $checkStmt->get_result()->fetch_assoc();
            $checkStmt->close();

            if ($alreadyApproved) {
                $conn->rollback();
                $isBooked = true;
                $message = "Sorry, this property has already been booked.";
            } else {
                $propertyName = $property["name"];
                $location = $property["location"];
                $rooms = (string) $property["rooms"];
                $price = (string) $property["price"];

                $insertStmt = $conn->prepare(
                    "INSERT INTO bookings
                     (user_id, property_id, property_name, location,
                      rooms, price, move_in_date, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')"
                );

                if (!$insertStmt) {
                    throw new Exception("Could not prepare booking.");
                }

                $insertStmt->bind_param(
                    "iisssss",
                    $user_id,
                    $id,
                    $propertyName,
                    $location,
                    $rooms,
                    $price,
                    $move_in_date
                );

                if (!$insertStmt->execute()) {
                    $insertStmt->close();
                    throw new Exception("Booking failed. Please try again.");
                }

                $bookingId = $conn->insert_id;
                $insertStmt->close();
                $conn->commit();

                header("Location: booking-success.php?id=" . $bookingId);
                exit;
            }
        } catch (Throwable $e) {
            $conn->rollback();
            $message = $e->getMessage();
        }
    }
}

/* Refresh availability after processing */
$stmt = $conn->prepare(
    "SELECT id FROM bookings
     WHERE property_id = ?
       AND status = 'Approved'
     LIMIT 1"
);

if ($stmt) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $isBooked = (bool) $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$today = date("Y-m-d");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartRent - Book Property</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            line-height: 1.6;
        }

        .header {
            background: #1e293b;
            color: white;
            padding: 18px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .logo {
            font-size: 25px;
            font-weight: bold;
        }

        .back {
            background: #2563eb;
            color: white;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 6px;
        }

        .container {
            max-width: 700px;
            margin: auto;
            padding: 40px 20px;
        }

        .card {
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0,0,0,.08);
        }

        h1 {
            text-align: center;
            margin-bottom: 25px;
        }

        .property {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 22px;
            border-radius: 10px;
            margin-bottom: 28px;
        }

        .property h2 {
            margin-bottom: 12px;
        }

        .property p {
            margin: 8px 0;
            color: #475569;
        }

        .price {
            color: #2563eb;
            font-size: 22px;
            font-weight: bold;
            margin-top: 15px;
        }

        .welcome {
            color: #64748b;
            text-align: center;
            margin-bottom: 25px;
        }

        .notice {
            padding: 14px;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .booked {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 18px;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
        }

        input {
            display: block;
            width: 100%;
            padding: 13px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 16px;
        }

        input[readonly] {
            background: #f8fafc;
            color: #475569;
        }

        button {
            width: 100%;
            border: none;
            background: #16a34a;
            color: white;
            padding: 15px;
            border-radius: 7px;
            font-size: 17px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #15803d;
        }

        @media (max-width: 600px) {
            .header {
                padding: 15px;
            }

            .card {
                padding: 22px;
            }
        }
    </style>
</head>

<body>

<header class="header">
    <div class="logo">SmartRent</div>

    <a href="property.php?id=<?php echo (int)$property["id"]; ?>"
       class="back">← Property</a>
</header>

<main class="container">
    <div class="card">

        <h1>🏠 Book Property</h1>

        <p class="welcome">
            Review the property details and select your move-in date.
        </p>

        <?php if ($message !== ""): ?>
            <div class="notice error">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="property">
            <h2><?php echo htmlspecialchars($property["name"]); ?></h2>

            <p>📍 Location:
                <?php echo htmlspecialchars($property["location"]); ?>
            </p>

            <p>🛏 Bedrooms:
                <?php echo htmlspecialchars((string)$property["rooms"]); ?>
            </p>

            <p>🏢 Type:
                <?php echo htmlspecialchars($property["type"] ?? "Property"); ?>
            </p>

            <div class="price">
                💰 <?php echo number_format((float)$property["price"], 2); ?>
                ETB / Month
            </div>
        </div>

        <?php if ($isBooked): ?>

            <div class="booked">
                🚫 Already Booked — This property has already been approved
                for another tenant.
            </div>

            <a href="properties.php"
               class="back"
               style="display:block;text-align:center;">
                ← Back to Properties
            </a>

        <?php else: ?>

            <form method="POST"
                  action="booking.php?id=<?php echo (int)$property["id"]; ?>">

                <label for="full_name">Full Name</label>
                <input
                    type="text"
                    id="full_name"
                    value="<?php echo htmlspecialchars($name); ?>"
                    readonly
                >

                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    value="<?php echo htmlspecialchars($email); ?>"
                    readonly
                >

                <label for="move_in_date">Move-in Date</label>
                <input
                    type="date"
                    id="move_in_date"
                    name="move_in_date"
                    min="<?php echo htmlspecialchars($today); ?>"
                    value="<?php echo htmlspecialchars($_POST["move_in_date"] ?? ""); ?>"
                    required
                >

                <button type="submit">Confirm Booking</button>
            </form>

        <?php endif; ?>

    </div>
</main>

</body>
</html>