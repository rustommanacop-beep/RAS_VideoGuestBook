
<?php
session_start();
include("database/database.php");

$message = "";

// ======================================
// USER REGISTRATION
// ======================================

if (isset($_POST['register'])) {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (
        $name === "" ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($password) < 8 ||
        $password !== $confirm
    ) {

        $message = "Please check your registration details.";

    } else {

        // Check if email already exists
        $stmt = $conn->prepare(
            "SELECT user_id FROM users WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {

            $message = "Email already registered.";

        } else {

            $stmt->close();

            // School demonstration only:
            // Save fictional test password as readable text
            $savedPassword = $password;

            $stmt = $conn->prepare(
                "INSERT INTO users
                 (name, email, password, role)
                 VALUES (?, ?, ?, 'user')"
            );

            $stmt->bind_param(
                "sss",
                $name,
                $email,
                $savedPassword
            );

            if ($stmt->execute()) {

                $message = "Registration successful. Please log in.";

            } else {

                $message = "Registration failed. Please try again.";

            }
        }

        $stmt->close();
    }
}


// ======================================
// USER LOGIN
// ======================================

if (isset($_POST['login'])) {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === "" || $password === "") {

        $message = "Please enter your email and password.";

    } else {

        $stmt = $conn->prepare(
            "SELECT * FROM users WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // School demonstration:
        // Check readable test password
        if (
            $user &&
            hash_equals(
                $user['password'],
                $password
            )
        ) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            $stmt->close();

            if ($user['role'] === 'admin') {

                header("Location: admin.php");

            } else {

                header("Location: my_bookings.php");

            }

            exit;

        } else {

            $message = "Invalid email or password.";

        }

        $stmt->close();
    }
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

    <title>Login / Register - RAS Video Guest Book</title>

    <link rel="icon" href="pictures/ras.png">

    <link rel="stylesheet" href="css/style.css">

    <style>
        .account-message {
            text-align: center;
            margin: 20px auto;
            font-size: 15px;
            font-weight: 500;
        }

        .password-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 12px 0 20px;
            font-size: 14px;
            cursor: pointer;
        }

        .password-toggle input {
            width: auto;
            margin: 0;
            accent-color: #c59445;
        }

        .form-box input:not([type="checkbox"]) {
            box-sizing: border-box;
            width: 100%;
        }
    </style>

</head>

<body>

<!-- ======================================
     WEBSITE HEADER
     ====================================== -->

<div class="top-header">

    <a href="index.php" class="top-logo">

        <img
            src="pictures/ras.png"
            alt="RAS Logo"
        >

    </a>

    <h2>RAS VIDEO GUEST BOOK</h2>

</div>


<!-- ======================================
     NAVIGATION BAR
     ====================================== -->

<nav class="navbar">

    <div class="nav-links">

        <a href="index.php">HOME</a>

        <a href="aboutus.php">ABOUT US</a>

        <a href="events.php">SERVICES</a>

        <a href="events.php" class="gold-text">
            BOOK NOW!
        </a>

        <?php if (isset($_SESSION['user_id'])) { ?>

            <a href="my_bookings.php">MY BOOKINGS</a>

            <a href="profile.php">PROFILE</a>

            <?php if (
                ($_SESSION['role'] ?? '') === 'admin'
            ) { ?>

                <a href="admin.php">ADMIN</a>

            <?php } ?>

            <a href="logout.php">LOGOUT</a>

        <?php } else { ?>

            <a href="account.php">
                LOGIN / REGISTER
            </a>

        <?php } ?>

    </div>

</nav>


<!-- ======================================
     LOGIN AND REGISTRATION SECTION
     ====================================== -->

<section class="section">

    <p class="small-title">YOUR ACCOUNT</p>

    <h2>Login / Register</h2>


    <!-- DISPLAY MESSAGE -->

    <?php if ($message !== "") { ?>

        <p class="account-message">
            <?php
            echo htmlspecialchars(
                $message,
                ENT_QUOTES,
                'UTF-8'
            );
            ?>
        </p>

    <?php } ?>


    <div class="form-grid">


        <!-- ==================================
             LOGIN FORM
             ================================== -->

        <form
            method="POST"
            action="account.php"
            class="form-box"
            autocomplete="off"
        >

            <h3>Login</h3>

            <label for="login_email">
                Email
            </label>

            <input
                type="email"
                id="login_email"
                name="email"
                autocomplete="off"
                required
            >


            <label for="login_password">
                Password
            </label>

            <input
                type="password"
                id="login_password"
                name="password"
                autocomplete="off"
                required
            >


            <label class="password-toggle">

                <input
                    type="checkbox"
                    onchange="toggleLoginPassword()"
                >

                Show Password

            </label>


            <button
                type="submit"
                name="login"
                class="button"
            >
                LOGIN
            </button>

        </form>


        <!-- ==================================
             REGISTRATION FORM
             ================================== -->

        <form
            method="POST"
            action="account.php"
            class="form-box"
            autocomplete="off"
        >

            <h3>Create Account</h3>


            <label for="register_name">
                Full Name
            </label>

            <input
                type="text"
                id="register_name"
                name="name"
                autocomplete="off"
                required
            >


            <label for="register_email">
                Email
            </label>

            <input
                type="email"
                id="register_email"
                name="email"
                autocomplete="off"
                required
            >


            <label for="register_password">
                Password
            </label>

            <input
                type="password"
                id="register_password"
                name="password"
                autocomplete="new-password"
                minlength="8"
                required
            >


            <label for="register_confirm">
                Confirm Password
            </label>

            <input
                type="password"
                id="register_confirm"
                name="confirm"
                autocomplete="new-password"
                minlength="8"
                required
            >


            <label class="password-toggle">

                <input
                    type="checkbox"
                    onchange="toggleRegisterPassword()"
                >

                Show Password

            </label>


            <button
                type="submit"
                name="register"
                class="button"
            >
                REGISTER
            </button>

        </form>

    </div>


    <!-- GUEST BOOKING LINK -->

    <p>
        You can also
        <a href="events.php">
            book a service as a guest
        </a>.
    </p>

</section>


<!-- ======================================
     FOOTER
     ====================================== -->

<footer class="footer">

    <h3>RAS Video Guest Book</h3>

    <p>Creating memories that last forever.</p>

    <p>
        &copy; <?php echo date('Y'); ?>
        RAS Video Guest Book
    </p>

</footer>


<!-- ======================================
     SHOW / HIDE PASSWORD
     ====================================== -->

<script>

function toggleLoginPassword() {

    const password = document.getElementById(
        "login_password"
    );

    password.type =
        password.type === "password"
        ? "text"
        : "password";
}


function toggleRegisterPassword() {

    const password = document.getElementById(
        "register_password"
    );

    const confirm = document.getElementById(
        "register_confirm"
    );

    const show = password.type === "password";

    password.type = show ? "text" : "password";

    confirm.type = show ? "text" : "password";
}

</script>

</body>
</html>
