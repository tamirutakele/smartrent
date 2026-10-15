<?php

session_start();

require_once "../config/database.php";

// Check admin access
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

// ============================================
// APPROVE OR REJECT BOOKING
// ============================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $bookingId = filter_input(
        INPUT_POST,
        "booking_id",
        FILTER_VALIDATE_INT
    );

    $newStatus = $_POST["status"] ?? "";

    // Validate input
    if (
        !$bookingId ||
        !in_array($newStatus, ["Approved", "Rejected"], true)
    ) {

        $message = "Invalid booking ID or status.";
        $messageType = "error";

    } else {

        // Check whether booking exists
        $checkStmt = $conn->prepare(
            "SELECT id, status
             FROM bookings
             WHERE id = ?"
        );

        if (!$checkStmt) {

            $message = "Database error: " . $conn->error;
            $messageType = "error";

        } else {

            $checkStmt->bind_param("i", $bookingId);
            $checkStmt->execute();

            $checkResult = $checkStmt->get_result();
            $bookingExists = $checkResult->fetch_assoc();

            $checkStmt->close();

            if (!$bookingExists) {

                $message = "Booking #" . $bookingId . " was not found.";
                $messageType = "error";

            } else {

                // Update booking status
                $updateStmt = $conn->prepare(
                    "UPDATE bookings
                     SET status = ?
                     WHERE id = ?"
                );

                if (!$updateStmt) {

                    $message = "Unable to prepare update: " . $conn->error;
                    $messageType = "error";

                } else {

                    $updateStmt->bind_param(
                        "si",
                        $newStatus,
                        $bookingId
                    );

                    if ($updateStmt->execute()) {

                        $updateStmt->close();

                        // Verify saved status from database
                        $verifyStmt = $conn->prepare(
                            "SELECT status
                             FROM bookings
                             WHERE id = ?"
                        );

                        if (!$verifyStmt) {

                            $message = "Unable to verify booking status.";
                            $messageType = "error";

                        } else {

                            $verifyStmt->bind_param("i", $bookingId);
                            $verifyStmt->execute();

                            $verifyResult = $verifyStmt->get_result();
                            $savedBooking = $verifyResult->fetch_assoc();

                            $verifyStmt->close();

                            if (
                                $savedBooking &&
                                $savedBooking["status"] === $newStatus
                            ) {

                                $message = "Booking #" .
                                    $bookingId .
                                    " successfully updated to " .
                                    $newStatus . ".";

                                $messageType = "success";

                            } else {

                                $message = "The booking status could not be verified.";
                                $messageType = "error";

                            }
                        }

                    } else {

                        $message = "Failed to update booking: " .
                            $updateStmt->error;

                        $messageType = "error";

                        $updateStmt->close();
                    }
                }
            }
        }
    }
}

// ============================================
// FETCH ALL BOOKINGS
// ============================================

$sql = "
    SELECT
        b.id,
        b.user_id,
        b.property_name,
        b.location,
        b.rooms,
        b.price,
        b.move_in_date,
        b.status,
        u.name AS tenant_name,
        u.email AS tenant_email
    FROM bookings AS b
    LEFT JOIN userss AS u
        ON b.user_id = u.id
    ORDER BY b.id DESC
";

$result = $conn->query($sql);

if (!$result) {
    die(
        "Unable to load bookings. Database error: " .
        htmlspecialchars($conn->error)
    );
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Bookings | SmartRent Admin</title>

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

        header h1 {
            font-size: 27px;
        }

        header a {
            color: white;
            text-decoration: none;
            background: #334155;
            padding: 10px 15px;
            border-radius: 6px;
        }

        header a:hover {
            background: #475569;
        }

        .container {
            max-width: 1450px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .intro {
            margin-bottom: 20px;
        }

        .intro p {
            margin-top: 8px;
            color: #64748b;
        }

        .message {
            padding: 14px 18px;
            border-radius: 7px;
            margin: 20px 0;
            font-weight: bold;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .table-wrap {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            overflow-x: auto;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
        }

        th,
        td {
            padding: 14px 12px;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: #f8fafc;
            color: #0f172a;
            white-space: nowrap;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        .status {
            display: inline-block;
            padding: 7px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
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

        .actions {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        button {
            border: none;
            padding: 9px 12px;
            border-radius: 5px;
            color: white;
            cursor: pointer;
            font-weight: bold;
            font-size: 12px;
        }

        .approve {
            background: #16a34a;
        }

        .approve:hover {
            background: #15803d;
        }

        .reject {
            background: #dc2626;
        }

        .reject:hover {
            background: #b91c1c;
        }

        .no-action {
            color: #64748b;
            font-size: 12px;
        }

        .empty {
            text-align: center;
            padding: 35px;
            color: #64748b;
        }

        .summary {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
        }

        .summary-card {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .summary-card span {
            font-weight: bold;
        }

        @media (max-width: 600px) {

            header {
                padding: 18px;
            }

            header h1 {
                font-size: 22px;
            }

            .container {
                padding: 0 12px;
            }

            .table-wrap {
                padding: 10px;
            }
        }

    </style>

</head>

<body>

<header>

    <h1>📋 Manage Bookings</h1>

    <a href="index.php">← Admin Dashboard</a>

</header>

<div class="container">

    <div class="intro">

        <h2>All Rental Bookings</h2>

        <p>
            Review tenant requests and approve or reject bookings.
        </p>

    </div>

    <?php if ($message !== ""): ?>

        <div class="message <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>

    <?php endif; ?>

    <div class="table-wrap">

        <table>

            <thead>

                <tr>
                    <th>ID</th>
                    <th>Tenant</th>
                    <th>Email</th>
                    <th>Property</th>
                    <th>Location</th>
                    <th>Rooms</th>
                    <th>Price / Month</th>
                    <th>Move-in Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>

            </thead>

            <tbody>

            <?php if ($result->num_rows > 0): ?>

                <?php while ($booking = $result->fetch_assoc()): ?>

                    <?php

                    $status = $booking["status"] ?? "Pending";

                    $statusClass = strtolower(trim($status));

                    if (
                        !in_array(
                            $statusClass,
                            ["pending", "approved", "rejected"],
                            true
                        )
                    ) {
                        $statusClass = "pending";
                    }

                    ?>

                    <tr>

                        <td>
                            <?= (int)$booking["id"] ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $booking["tenant_name"] ?? "Unknown"
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $booking["tenant_email"] ?? "N/A"
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $booking["property_name"] ?? ""
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $booking["location"] ?? ""
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                (string)($booking["rooms"] ?? "")
                            ) ?>
                        </td>

                        <td>
                            <?= number_format(
                                (float)($booking["price"] ?? 0)
                            ) ?> ETB
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $booking["move_in_date"] ?? ""
                            ) ?>
                        </td>

                        <td>

                            <span class="status <?= $statusClass ?>">

                                <?php if ($statusClass === "approved"): ?>

                                    ✅ Approved

                                <?php elseif ($statusClass === "rejected"): ?>

                                    ❌ Rejected

                                <?php else: ?>

                                    ⏳ Pending

                                <?php endif; ?>

                            </span>

                        </td>

                        <td>

                            <?php if ($statusClass === "pending"): ?>

                                <div class="actions">

                                    <form
                                        method="POST"
                                        action=""
                                        onsubmit="return confirm('Are you sure you want to approve this booking?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="booking_id"
                                            value="<?= (int)$booking["id"] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="status"
                                            value="Approved"
                                        >

                                        <button
                                            type="submit"
                                            class="approve"
                                        >
                                            ✓ Approve
                                        </button>

                                    </form>

                                    <form
                                        method="POST"
                                        action=""
                                        onsubmit="return confirm('Are you sure you want to reject this booking?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="booking_id"
                                            value="<?= (int)$booking["id"] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="status"
                                            value="Rejected"
                                        >

                                        <button
                                            type="submit"
                                            class="reject"
                                        >
                                            ✕ Reject
                                        </button>

                                    </form>

                                </div>

                            <?php else: ?>

                                <span class="no-action">
                                    No action needed
                                </span>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>

                    <td colspan="10" class="empty">
                        No bookings found yet.
                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>

<?php

$result->free();
$conn->close();

?>