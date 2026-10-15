<?php

session_start();

require_once "../config/database.php";

// Check tenant login
if (!isset($_SESSION["user"])) {
    header("Location: ../login.php");
    exit;
}

// Get logged-in user's ID
$userId = $_SESSION["user_id"] ?? 0;

if (!$userId) {
    die("User ID is missing. Please log out and log in again.");
}

// Get this tenant's bookings only
$sql = "SELECT *
        FROM bookings
        WHERE user_id = ?
        ORDER BY id DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database query failed.");
}

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Bookings | SmartRent</title>

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
        }

        header {
            background: #1e293b;
            color: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        header a {
            background: #334155;
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 6px;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .intro {
            margin-bottom: 22px;
        }

        .intro p {
            color: #64748b;
            margin-top: 8px;
        }

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            overflow-x: auto;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        table {
            width: 100%;
            min-width: 850px;
            border-collapse: collapse;
        }

        th, td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #f8fafc;
        }

        .status {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .pending {
            background: #fef3c7;
            color: #92400e;
        }

        .approved {
            background: #dcfce7;
            color: #166534;
        }

        .rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .empty {
            text-align: center;
            padding: 35px;
            color: #64748b;
        }

        .browse {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 18px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 7px;
        }

        .browse:hover {
            background: #1d4ed8;
        }

        @media (max-width: 600px) {
            .container {
                padding: 0 12px;
            }

            .table-container {
                padding: 10px;
            }

            header {
                padding: 18px;
            }
        }

    </style>

</head>

<body>

<header>

    <h1>📋 My Bookings</h1>

    <a href="dashboard.php">← Dashboard</a>

</header>

<div class="container">

    <div class="intro">

        <h2>My Rental Requests</h2>

        <p>
            Check your rental booking details and approval status.
        </p>

    </div>

    <div class="table-container">

        <table>

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Property</th>
                    <th>Location</th>
                    <th>Rooms</th>
                    <th>Monthly Price</th>
                    <th>Move-in Date</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($result->num_rows > 0): ?>

                <?php while ($booking = $result->fetch_assoc()): ?>

                    <?php
                    $status = $booking["status"] ?? "Pending";

                    $statusClass = strtolower($status);

                    if (!in_array(
                        $statusClass,
                        ["pending", "approved", "rejected"],
                        true
                    )) {
                        $statusClass = "pending";
                    }
                    ?>

                    <tr>

                        <td>
                            <?= (int)$booking["id"] ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($booking["property_name"] ?? "") ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($booking["location"] ?? "") ?>
                        </td>

                        <td>
                            <?= htmlspecialchars((string)($booking["rooms"] ?? "")) ?>
                        </td>

                        <td>
                            <?= number_format((float)($booking["price"] ?? 0)) ?> ETB
                        </td>

                        <td>
                            <?= htmlspecialchars($booking["move_in_date"] ?? "") ?>
                        </td>

                        <td>
                            <span class="status <?= $statusClass ?>">
                                <?php
                                if ($statusClass === "approved") {
                                    echo "✅ Approved";
                                } elseif ($statusClass === "rejected") {
                                    echo "❌ Rejected";
                                } else {
                                    echo "⏳ Pending";
                                }
                                ?>
                            </span>
                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="7" class="empty">
                        You have not made any bookings yet.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

    <a href="properties.php" class="browse">
        🏠 Browse Properties
    </a>

</div>

</body>

</html>

<?php
$stmt->close();
$conn->close();
?>