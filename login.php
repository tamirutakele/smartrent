<?php
session_start();

require_once "config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $sql = "SELECT id, name, email, password, phone, role
            FROM userss
            WHERE email = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Database error: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user["password"])) {

            session_regenerate_id(true);

            $_SESSION["user"] = $user["name"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];
            $_SESSION["user_id"] = $user["id"];

            if ($user["role"] === "admin") {
                header("Location: admin/index.php");
                exit;
            }

            header("Location: tenant/dashboard.php");
            exit;

        } else {
            $message = "Incorrect password!";
        }

    } else {
        $message = "Email not found!";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

```
<title>SmartRent Login</title>

<style>
    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        font-family: Arial, sans-serif;
        background: #f1f5f9;
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 90px 15px 20px;
    }

    /* Top navigation */
    .top-buttons {
        position: fixed;
        top: 20px;
        right: 25px;
        display: flex;
        align-items: center;
        gap: 25px;
        z-index: 1000;
    }

    .home-button,
    .admin-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 9px 16px;
        color: white;
        text-decoration: none;
        border-radius: 20px;
        font-size: 14px;
        font-weight: bold;
        white-space: nowrap;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
        transition: 0.3s;
    }

    .home-button {
        background: #475569;
    }

    .home-button:hover {
        background: #334155;
        transform: translateY(-2px);
    }

    .admin-button {
        background: #16a34a;
    }

    .admin-button:hover {
        background: #15803d;
        transform: translateY(-2px);
    }

    /* Login form */
    .login-box {
        width: 100%;
        max-width: 400px;
        background: white;
        padding: 35px;
        border-radius: 14px;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.12);
    }

    h1 {
        text-align: center;
        color: #1e293b;
        margin: 0 0 25px;
    }

    input {
        display: block;
        width: 100%;
        padding: 13px;
        margin-bottom: 16px;
        border: 1px solid #cbd5e1;
        border-radius: 7px;
        font-size: 15px;
    }

    input:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    button {
        width: 100%;
        padding: 13px;
        background: #2563eb;
        color: white;
        border: none;
        border-radius: 7px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.3s;
    }

    button:hover {
        background: #1d4ed8;
    }

    .message {
        background: #fee2e2;
        color: #dc2626;
        padding: 12px;
        margin-bottom: 16px;
        border-radius: 7px;
        text-align: center;
    }

    .register-link {
        text-align: center;
        margin-top: 22px;
        color: #64748b;
        line-height: 1.8;
    }

    .register-link a {
        color: #2563eb;
        text-decoration: none;
        font-weight: bold;
    }

    .register-link a:hover {
        text-decoration: underline;
    }

    /* Small screens */
    @media (max-width: 480px) {
        .top-buttons {
            top: 15px;
            right: 12px;
            gap: 12px;
        }

        .home-button,
        .admin-button {
            padding: 8px 11px;
            font-size: 12px;
        }

        .login-box {
            padding: 25px 20px;
        }
    }
</style>
```

</head>

<body>

```
<div class="top-buttons">

    <a href="/SmartRent/index.html" class="home-button">
        🏠 Home
    </a>

    <a href="/SmartRent/admin/admin-login.php" class="admin-button">
        👤 Admin Login
    </a>

</div>

<div class="login-box">

    <h1>SmartRent Login</h1>

    <?php if ($message !== ""): ?>
        <div class="message">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="/SmartRent/login.php" autocomplete="off">

        <input
            type="email"
            name="email"
            placeholder="Enter your email"
            autocomplete="off"
            required
        >

        <input
            type="password"
            name="password"
            placeholder="Enter your password"
            autocomplete="new-password"
            required
        >

        <button type="submit">Login</button>

    </form>

    <div class="register-link">
        Don't have an account?<br>
        <a href="/SmartRent/register.php">Create Account</a>
    </div>

</div>
```

</body>
</html>
