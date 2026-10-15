<?php

session_start();

if (!isset($_SESSION["user"])) {
    header("Location: ../login.php");
    exit;
}

$name = $_SESSION["user"];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SmartRent - Booking Successful</title>

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

        .container {
            max-width: 650px;
            margin: 80px auto;
            padding: 20px;
        }

        .success-card {
            background: white;

            padding: 45px 35px;

            border-radius: 15px;

            text-align: center;

            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        }

        .icon {
            width: 90px;
            height: 90px;

            margin: 0 auto 25px;

            background: #dcfce7;

            color: #16a34a;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 50px;
        }

        h1 {
            color: #16a34a;

            margin-bottom: 15px;
        }

        .message {
            color: #64748b;

            font-size: 17px;

            line-height: 1.6;

            margin-bottom: 30px;
        }

        .user {
            font-weight: bold;

            color: #1e293b;
        }

        .buttons {
            display: flex;

            gap: 15px;

            justify-content: center;

            flex-wrap: wrap;
        }

        .button {
            text-decoration: none;

            color: white;

            background: #2563eb;

            padding: 13px 22px;

            border-radius: 7px;

            font-weight: bold;
        }

        .button:hover {
            background: #1d4ed8;
        }

        .secondary {
            background: #64748b;
        }

        .secondary:hover {
            background: #475569;
        }

    </style>

</head>

<body>


    <div class="header">

        <div class="logo">
            SmartRent
        </div>

    </div>


    <div class="container">

        <div class="success-card">


            <div class="icon">
                ✓
            </div>


            <h1>
                Booking Successful!
            </h1>


            <div class="message">

                Congratulations
                <span class="user">
                    <?php echo htmlspecialchars($name); ?>
                </span>!

                <br><br>

                Your property booking request has been submitted successfully.

                <br>

                Our team will review your booking and confirm it soon.

            </div>


            <div class="buttons">

                <a href="bookings.php" class="button">
                    📋 My Bookings
                </a>

                <a href="dashboard.php" class="button secondary">
                    🏠 Dashboard
                </a>

            </div>


        </div>

    </div>


</body>

</html>