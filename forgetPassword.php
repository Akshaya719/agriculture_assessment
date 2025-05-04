<?php
session_start();
require_once 'includes/config.php';
require_once 'formValidation.php';


$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize_input(filter_var($_POST["email"], FILTER_SANITIZE_EMAIL));

    $query = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        // Generate a new random password
        $newPasswordPlain = bin2hex(random_bytes(4)); // 8-character password
        $newPasswordHashed = password_hash($newPasswordPlain, PASSWORD_DEFAULT);

        // Update the user's password in the Database
        $updateQuery = "UPDATE users SET password = '$newPasswordHashed' WHERE email = '$email'";
        $updateResult = mysqli_query($conn, $updateQuery);

        if ($updateResult) {
            // Send the new password via email
            $subject = "Password Reset - AgriLand";
            $body = "Hi,\n\nUser Name: $email\nYour new password is: $newPasswordPlain\n\nPlease login and change your password immediately for security.\n\nThank you.";
            $headers = "From: bodduakshaya22@gmail.com";

            if (mail($email, $subject, $body, $headers)) {
                $message = "A new password has been sent to <strong>$email</strong>.";
            } else {
                $message = "Password updated, but failed to send email.";
            }
        } else {
            $message = "Error updating password.";
        }
    } else {
        $message = "No account found with this email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forget Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body style="background-image: url(images/3.jpg); background-size: cover; background-repeat: no-repeat;">
    <?php require 'header.php'; ?>

    <main id="content" role="main" class="w-full max-w-md mx-auto p-6 mt-[100px]">
        <div class="mt-7 bg-white rounded-xl shadow-lg dark:bg-gray-800 dark:border-gray-700 border-2 border-green-300">
            <div class="p-4 sm:p-7">
                <div class="text-center">
                    <h1 class="block text-2xl font-bold text-green-700 dark:text-white">Forgot password?</h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Remember your password?
                        <a class="text-green-600 decoration-2 hover:underline font-medium" href="login.php">
                            Login here
                        </a>
                    </p>
                </div>

                <div class="mt-5">
                    <form method="POST" action="forgetPassword.php">
                        <div class="grid gap-y-4">
                            <div>
                                <label for="email" class="block text-sm font-bold ml-1 mb-2 dark:text-white">Email address</label>
                                <div class="relative">
                                    <input type="email" id="email" name="email" class="py-3 px-4 block w-full border-2 border-gray-200 rounded-md text-sm focus:border-green-500 focus:ring-green-500 shadow-sm" required aria-describedby="email-error">
                                </div>
                                <p class="hidden text-xs text-red-600 mt-2" id="email-error">Please include a valid email address so we can get back to you</p>
                            </div>
                            <button type="submit" class="py-3 px-4 inline-flex justify-center items-center gap-2 rounded-md border border-transparent font-semibold bg-green-500 text-white hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all text-sm dark:focus:ring-offset-gray-800">
                                Send password
                            </button>
                        </div>
                    </form>

                    <?php if (!empty($message)) : ?>
                        <div class="text-center mt-4 text-sm font-medium text-[black]">
                            <?= $message ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <p class="mt-3 flex justify-center items-center text-center divide-x divide-gray-300 dark:divide-gray-700">
            <a class="pl-3 inline-flex items-center gap-x-2 text-sm text-gray-600 decoration-2 hover:underline hover:text-green-600 dark:text-gray-500 dark:hover:text-gray-200" href="#">
                Contact us!
            </a>
        </p>
    </main>
</body>

</html>
