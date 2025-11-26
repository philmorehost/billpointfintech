<?php
require_once 'includes/bootstrap.php';
require_once 'includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        die('Invalid CSRF token');
    }

    if (isset($_POST['action']) && $_POST['action'] === 'create_ticket') {
        $subject = trim($_POST['subject']);
        $message = trim($_POST['message']);
        $user_id = $_SESSION['user_id'];

        if (empty($subject) || empty($message)) {
            $_SESSION['error_message'] = 'Subject and message are required.';
            header('Location: support.php');
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Create the ticket
            $stmt = $pdo->prepare("INSERT INTO tickets (user_id, subject) VALUES (?, ?)");
            $stmt->execute([$user_id, $subject]);
            $ticket_id = $pdo->lastInsertId();

            // Add the initial message
            $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message, is_admin_reply) VALUES (?, ?, ?, 0)");
            $stmt->execute([$ticket_id, $user_id, $message]);

            $pdo->commit();

            $_SESSION['success_message'] = 'Support ticket created successfully.';
            header('Location: view_ticket.php?id=' . $ticket_id);
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = 'An error occurred while creating the ticket. Please try again.';
            header('Location: support.php');
            exit;
        }
    }

    // Redirect back to the support page for any other case
    header('Location: support.php');
    exit;
}
