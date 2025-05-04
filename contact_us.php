<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Initialize variables
$name = '';
$email = '';
$subject = '';
$message = '';
$success = '';
$error = '';


function sanitizeFormData($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}


// If user is logged in, pre-fill their info
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_query = "SELECT full_name, email FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $user_query);
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        $name = htmlspecialchars($user['full_name']);
        $email = htmlspecialchars($user['email']);
    }
    mysqli_free_result($result);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize inputs
    $name = sanitizeFormData($_POST['name']);
    $email = sanitizeFormData($_POST['email']);
    $subject = sanitizeFormData($_POST['subject']);
    $message_content = sanitizeFormData($_POST['message']);
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    // Combine all form data into query_text
    $query_text = "Name: $name\n";
    $query_text .= "Email: $email\n";
    $query_text .= "Subject: $subject\n\n";
    $query_text .= "Message:\n$message_content";

    // Validate inputs
    $errors = [];
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($subject)) $errors[] = "Subject is required";
    if (empty($message_content)) $errors[] = "Message is required";

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }

    if (empty($errors)) {
        // Prepare data for database
        $clean_query_text = sanitizeFormData($query_text);

        // Insert query into database with status=0 (unread)
        $insert_query = "INSERT INTO user_queries (user_id, query_text, status) 
                         VALUES (?, ?, 0)";
        $stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($stmt, 'is', $user_id, $clean_query_text);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Thank you for contacting us! We'll get back to you soon.";

            // Clear form on success
            // $name = $email = $subject = $message_content = '';

            // // Send email notification
            $to = $email;
            $subject_line = "New Contact Form: " . $subject;
            $email_body = $query_text; // Reuse the same formatted text

            // Additional headers
            $headers = [
                'From' => $email,
                'Reply-To' => $email,
                'X-Mailer' => 'PHP/' . phpversion(),
                'Content-Type' => 'text/plain; charset=UTF-8'
            ];

            // Build header string
            $header_string = '';
            foreach ($headers as $key => $value) {
                $header_string .= "$key: $value\r\n";
            }

            // Send mail
            mail($to, $subject_line, $email_body, $header_string);
        } else {
            $error = "Error submitting your query. Please try again later. Error: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = implode("<br>", $errors);
    }
}

include 'header.php';
?>

<body style="background-image: url(images/3.jpg); background-size: cover; background-repeat: no-repeat;">
    <main class="flex-1 p-8">
        <div class="max-w-3xl mx-auto">
            <h1 class="text-2xl font-bold mb-6 text-white text-center">Contact Us</h1>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <div class="bg-white p-6 rounded-lg shadow-lg">
                <form method="POST" action="contact_us.php" class="space-y-4" id="contactForm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-gray-700 mb-1">Full Name *</label>
                            <input type="text" id="name" name="name"
                                class="w-full p-2 border rounded focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                value="<?php echo htmlspecialchars($name); ?>" required>
                            <div id="name-error" class="text-red-500 text-sm mt-1 hidden"></div>
                        </div>
                        <div>
                            <label for="email" class="block text-gray-700 mb-1">Email Address *</label>
                            <input type="email" id="email" name="email"
                                class="w-full p-2 border rounded focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                value="<?php echo htmlspecialchars($email); ?>" required>
                            <div id="email-error" class="text-red-500 text-sm mt-1 hidden"></div>
                        </div>
                    </div>

                    <div>
                        <label for="subject" class="block text-gray-700 mb-1">Subject *</label>
                        <input type="text" id="subject" name="subject"
                            class="w-full p-2 border rounded focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            value="<?php echo htmlspecialchars($subject); ?>" required>
                        <div id="subject-error" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>

                    <div>
                        <label for="message" class="block text-gray-700 mb-1">Your Message *</label>
                        <textarea id="message" name="message" rows="5"
                            class="w-full p-2 border rounded focus:ring-2 focus:ring-green-500 focus:border-green-500" required><?php echo htmlspecialchars($message_content ?? ''); ?></textarea>
                        <div id="message-error" class="text-red-500 text-sm mt-1 hidden"></div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded transition duration-200">
                            Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('contactForm');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            // Real-time validation
            document.getElementById('email').addEventListener('input', function() {
                const errorElement = document.getElementById('email-error');
                if (!emailRegex.test(this.value)) {
                    errorElement.textContent = 'Please enter a valid email address';
                    errorElement.classList.remove('hidden');
                } else {
                    errorElement.classList.add('hidden');
                }
            });

            // Form submission validation
            form.addEventListener('submit', function(e) {
                let isValid = true;
                const fields = ['name', 'email', 'subject', 'message'];

                fields.forEach(field => {
                    const element = document.getElementById(field);
                    const errorElement = document.getElementById(`${field}-error`);

                    if (!element.value.trim()) {
                        errorElement.textContent = 'This field is required';
                        errorElement.classList.remove('hidden');
                        isValid = false;
                    } else {
                        errorElement.classList.add('hidden');
                    }
                });

                // Additional email validation
                const email = document.getElementById('email').value.trim();
                if (email && !emailRegex.test(email)) {
                    document.getElementById('email-error').textContent = 'Please enter a valid email address';
                    document.getElementById('email-error').classList.remove('hidden');
                    isValid = false;
                }

                if (!isValid) {
                    e.preventDefault();
                    // Scroll to first error
                    const firstError = document.querySelector('.text-red-500:not(.hidden)');
                    if (firstError) {
                        firstError.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }
                }
            });
        });
    </script>

    <?php include 'footer.php'; ?>