<?php

session_start();

if (!isset($_SESSION["user"])) {
    header("Location: ../login.php");
    exit;
}

$name = $_SESSION["user"];
$email = $_SESSION["email"];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SmartRent - My Profile</title>

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

        .profile-card {
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .profile-icon {
            width: 90px;
            height: 90px;

            background: #2563eb;
            color: white;

            border-radius: 50%;

            display: flex;
            justify-content: center;
            align-items: center;

            font-size: 42px;

            margin: 0 auto 20px;
        }

        .profile-card h1 {
            text-align: center;
            margin-bottom: 30px;
        }

        .info {
            margin-bottom: 20px;
        }

        .info label {
            display: block;
            font-weight: bold;
            margin-bottom: 7px;
            color: #475569;
        }

        .info-box {
            background: #f8fafc;
            padding: 14px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        .role {
            display: inline-block;

            background: #dcfce7;
            color: #166534;

            padding: 7px 14px;

            border-radius: 20px;

            font-weight: bold;
        }

        .dashboard {
            display: block;
            text-align: center;

            margin-top: 25px;

            background: #2563eb;
            color: white;

            text-decoration: none;

            padding: 13px;

            border-radius: 6px;
        }

        .dashboard:hover {
            background: #1d4ed8;
        }

    </style>

</head>

<body>


    <div class="header">

        <div class="logo">
            SmartRent
        </div>

        <a href="dashboard.php" class="back">
            ← Dashboard
        </a>

    </div>


    <div class="container">

        <div class="profile-card">

            <div class="profile-icon">
                👤
            </div>

            <h1>
                My Profile
            </h1>


            <div class="info">

                <label>
                    Full Name
                </label>

                <div class="info-box">
                    <?php echo htmlspecialchars($name); ?>
                </div>

            </div>


            <div class="info">

                <label>
                    Email Address
                </label>

                <div class="info-box">
                    <?php echo htmlspecialchars($email); ?>
                </div>

            </div>


            <div class="info">

                <label>
                    Account Role
                </label>

                <div class="info-box">

                    <span class="role">
                        tenat
                    </span>

                </div>

            </div>


            <a href="dashboard.php" class="dashboard">
                ← Back to Dashboard
            </a>

        </div>

    </div>


</body>

</html>