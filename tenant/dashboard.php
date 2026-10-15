```php
<?php
session_start();

if (
    !isset($_SESSION["user"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "tenant"
) {
    header("Location: ../login.php");
    exit;
}

$name = $_SESSION["user"];
$email = $_SESSION["email"] ?? "";
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>SmartRent Tenant Dashboard</title>

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

.logout {
    background: #ef4444;
    color: white;
    text-decoration: none;
    padding: 10px 18px;
    border-radius: 6px;
}

.logout:hover {
    background: #dc2626;
}

.container {
    max-width: 1200px;
    margin: auto;
    padding: 30px;
}

.welcome {
    background: white;
    padding: 25px;
    border-radius: 10px;
    margin-bottom: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.welcome h1 {
    margin-bottom: 10px;
}

.welcome p {
    color: #64748b;
    line-height: 1.6;
}

.cards {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
}

.card {
    background: white;
    padding: 30px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 1px solid #e2e8f0;
    transition: transform 0.2s;
}

.card:hover {
    transform: translateY(-4px);
}

.icon {
    font-size: 45px;
    margin-bottom: 15px;
}

.card h2 {
    margin-bottom: 10px;
    font-size: 21px;
}

.card p {
    color: #64748b;
    margin-bottom: 20px;
    line-height: 1.6;
}

.card a {
    display: inline-block;
    width: 100%;
    padding: 12px;
    background: #2563eb;
    color: white;
    text-decoration: none;
    border-radius: 6px;
}

.card a:hover {
    background: #1d4ed8;
}

.properties-card {
    border-top: 4px solid #2563eb;
}

.bookings-card {
    border-top: 4px solid #ea580c;
}

.payment-card {
    border-top: 4px solid #16a34a;
}

.history-card {
    border-top: 4px solid #0891b2;
}

.profile-card {
    border-top: 4px solid #9333ea;
}

.footer {
    text-align: center;
    padding: 25px;
    color: #94a3b8;
    font-size: 14px;
}

@media (max-width: 850px) {
    .cards {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 600px) {
    .cards {
        grid-template-columns: 1fr;
    }

    .header {
        padding: 18px;
    }

    .container {
        padding: 20px 15px;
    }

    .welcome h1 {
        font-size: 24px;
    }
}
</style>

</head>

<body>

<header class="header">
    <div class="logo">🏠 SmartRent</div>
    <a href="../logout.php" class="logout">Logout</a>
</header>

<main class="container">

    <section class="welcome">
        <h1>
            Welcome, <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?> 👋
        </h1>

        <p>
            Welcome to your SmartRent Tenant Dashboard.
            Manage your bookings and payments from here.
        </p>
    </section>

    <section class="cards">

        <div class="card properties-card">
            <div class="icon">🏠</div>
            <h2>Properties</h2>
            <p>Browse available rental properties.</p>
            <a href="properties.php">View Properties</a>
        </div>

        <div class="card bookings-card">
            <div class="icon">📋</div>
            <h2>My Bookings</h2>
            <p>View your booking requests and their status.</p>
            <a href="bookings.php">My Bookings</a>
        </div>

        <div class="card payment-card">
            <div class="icon">💳</div>
            <h2>Make Payment</h2>
            <p>Submit payment information for your booking.</p>
            <a href="payment.php">Make Payment</a>
        </div>

        <div class="card history-card">
            <div class="icon">🧾</div>
            <h2>Payment History</h2>
            <p>Review previous payments and their approval status.</p>
            <a href="payment-history.php">Payment History</a>
        </div>

        <div class="card profile-card">
            <div class="icon">👤</div>
            <h2>My Profile</h2>
            <p>View and manage your profile information.</p>
            <a href="profile.php">View Profile</a>
        </div>

    </section>

</main>

<footer class="footer">
    SmartRent Property Management System
</footer>

</body>
</html>
```
