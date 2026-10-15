<?php
session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user"])) {
    header("Location: ../login.php");
    exit;
}

// Current logged-in user
$userId = (int)($_SESSION["user_id"] ?? 0);

if ($userId <= 0) {
    exit("User ID is missing. Please log out and log in again.");
}

$message = "";
$messageType = "error";

// Fetch only this tenant's bookings
$stmt = $conn->prepare("
    SELECT id, property_name, location, price, status
    FROM bookings
    WHERE user_id = ?
    ORDER BY id DESC
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();

$bookings = [];

while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

$stmt->close();

// Handle payment submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $bookingId = (int)($_POST["booking_id"] ?? 0);

    $amount = filter_var(
        $_POST["amount"] ?? "",
        FILTER_VALIDATE_FLOAT
    );

    $paymentMethod = $_POST["payment_method"] ?? "";

    $reference = trim(
        $_POST["transaction_reference"] ?? ""
    );

    $allowedMethods = [
        "Telebirr",
        "Chapa",
        "Bank"
    ];

    if (
        $bookingId <= 0 ||
        $amount === false ||
        $amount <= 0 ||
        !in_array($paymentMethod, $allowedMethods, true)
    ) {
        $message = "Please enter valid payment information.";

    } else {

        // Verify that the booking belongs to this tenant
        $check = $conn->prepare("
            SELECT id
            FROM bookings
            WHERE id = ? AND user_id = ?
            LIMIT 1
        ");

        $check->bind_param(
            "ii",
            $bookingId,
            $userId
        );

        $check->execute();

        $bookingResult = $check->get_result();

        if ($bookingResult->num_rows !== 1) {

            $message = "The selected booking was not found.";

        } else {

            // Prevent duplicate submissions for the same booking
            $duplicate = $conn->prepare("
                SELECT id
                FROM payments
                WHERE booking_id = ?
                  AND user_id = ?
                  AND status IN ('Pending', 'Approved')
                LIMIT 1
            ");

            $duplicate->bind_param(
                "ii",
                $bookingId,
                $userId
            );

            $duplicate->execute();

            $duplicateResult = $duplicate->get_result();

            if ($duplicateResult->num_rows > 0) {

                $message = "A pending or approved payment already exists for this booking.";

            } else {

                $insert = $conn->prepare("
                    INSERT INTO payments
                    (
                        user_id,
                        booking_id,
                        amount,
                        payment_method,
                        transaction_reference,
                        status
                    )
                    VALUES (?, ?, ?, ?, ?, 'Pending')
                ");

                $insert->bind_param(
                    "iidss",
                    $userId,
                    $bookingId,
                    $amount,
                    $paymentMethod,
                    $reference
                );

                if ($insert->execute()) {

                    header(
                        "Location: payment.php?success=1"
                    );

                    exit;

                } else {

                    $message = "Payment submission failed. Please try again.";
                }

                $insert->close();
            }

            $duplicate->close();
        }

        $check->close();
    }
}

if (isset($_GET["success"])) {
    $message = "Payment submitted successfully! Waiting for admin approval.";
    $messageType = "success";
}

function e($value)
{
    return htmlspecialchars(
        (string)($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>SmartRent | Payments</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
        }

        header {
            background: #0f172a;
            color: white;
            padding: 20px 7%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        header h2 {
            margin: 0;
            color: #38bdf8;
        }

        header a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
        }

        .container {
            width: 90%;
            max-width: 900px;
            margin: 35px auto;
        }

        .card {
            background: white;
            padding: 28px;
            border-radius: 14px;
            box-shadow: 0 5px 20px #0000000d;
        }

        h1 {
            margin-top: 0;
        }

        .subtitle {
            color: #64748b;
            margin-bottom: 25px;
        }

        label {
            display: block;
            font-weight: bold;
            margin: 18px 0 8px;
        }

        input,
        select {
            display: block;
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 15px;
            background: white;
        }

        input:focus,
        select:focus {
            outline: 2px solid #38bdf8;
            border-color: #38bdf8;
        }

        button {
            width: 100%;
            margin-top: 25px;
            padding: 14px;
            background: #0284c7;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #0369a1;
        }

        .message {
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .note {
            margin-top: 20px;
            padding: 15px;
            background: #eff6ff;
            border-left: 4px solid #0284c7;
            border-radius: 5px;
            color: #1e40af;
            line-height: 1.6;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #0369a1;
            text-decoration: none;
        }

        @media (max-width: 600px) {
            header {
                padding: 18px 5%;
            }

            .container {
                width: 94%;
                margin: 20px auto;
            }

            .card {
                padding: 20px;
            }
        }
    </style>
</head>

<body>

<header>
    <h2>SmartRent</h2>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="bookings.php">My Bookings</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>

<div class="container">

    <a class="back" href="dashboard.php">
        ← Back to Dashboard
    </a>

    <div class="card">

        <h1>💳 Make a Payment</h1>

        <p class="subtitle">
            Submit your payment information for admin verification.
        </p>

        <?php if ($message !== ""): ?>

            <div class="message <?= e($messageType) ?>">
                <?= e($message) ?>
            </div>

        <?php endif; ?>

        <?php if (count($bookings) > 0): ?>

            <form method="POST" action="payment.php">

                <label for="booking_id">
                    Select Booking
                </label>

                <select
                    name="booking_id"
                    id="booking_id"
                    required
                >

                    <option value="">
                        -- Select your booking --
                    </option>

                    <?php foreach ($bookings as $booking): ?>

                        <option
                            value="<?= (int)$booking["id"] ?>"
                            data-price="<?= e($booking["price"]) ?>"
                        >
                            <?= e($booking["property_name"]) ?>
                            -
                            <?= e($booking["location"]) ?>
                            -
                            <?= e($booking["status"]) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <label for="amount">
                    Payment Amount (ETB)
                </label>

                <input
                    type="number"
                    name="amount"
                    id="amount"
                    min="0.01"
                    step="0.01"
                    placeholder="Enter payment amount"
                    required
                >

                <label for="payment_method">
                    Payment Method
                </label>

                <select
                    name="payment_method"
                    id="payment_method"
                    required
                >
                    <option value="">
                        -- Select payment method --
                    </option>

                    <option value="Telebirr">
                        Telebirr
                    </option>

                    <option value="Chapa">
                        Chapa
                    </option>

                    <option value="Bank">
                        Bank Transfer
                    </option>
                </select>

                <label for="transaction_reference">
                    Transaction Reference
                </label>

                <input
                    type="text"
                    name="transaction_reference"
                    id="transaction_reference"
                    maxlength="150"
                    placeholder="Enter transaction reference"
                >

                <button type="submit">
                    Submit Payment
                </button>

            </form>

            <div class="note">
                <strong>Important:</strong>
                Your payment will remain Pending until the admin
                verifies it. This form records payment information;
                it does not transfer money automatically.
            </div>

        <?php else: ?>

            <div class="message error">
                You have no bookings available for payment.
                Please create a booking first.
            </div>

            <a href="properties.php">
                View Available Properties
            </a>

        <?php endif; ?>

    </div>

</div>

<script>
    const bookingSelect = document.getElementById("booking_id");
    const amountInput = document.getElementById("amount");

    if (bookingSelect && amountInput) {
        bookingSelect.addEventListener("change", function () {
            const selected = this.options[this.selectedIndex];

            if (selected && selected.dataset.price) {
                amountInput.value = selected.dataset.price;
            } else {
                amountInput.value = "";
            }
        });
    }
</script>

</body>
</html>