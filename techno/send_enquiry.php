<?php
/**
 * DB Lead Handler for Technodome
 * This script saves enquiry data into the database instead of sending emails.
 */

require_once 'db_config.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Collect and sanitize form data
    $name = isset($_POST["name"]) ? strip_tags(trim($_POST["name"])) : 'N/A';
    $mobile = isset($_POST["mobile"]) ? strip_tags(trim($_POST["mobile"])) : 'N/A';
    $location = isset($_POST["location"]) ? strip_tags(trim($_POST["location"])) : 'N/A';
    $course = isset($_POST["course"]) ? strip_tags(trim($_POST["course"])) : 'N/A';
    $computerKnowledge = isset($_POST["computerKnowledge"]) ? strip_tags(trim($_POST["computerKnowledge"])) : 'N/A';
    $learningMode = isset($_POST["learningMode"]) ? strip_tags(trim($_POST["learningMode"])) : 'N/A';

    try {
        // 2. Prepare database insertion
        $stmt = $pdo->prepare("
            INSERT INTO leads (name, mobile, location, course, computer_knowledge, learning_mode) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $success = $stmt->execute([
            $name, 
            $mobile, 
            $location, 
            $course, 
            $computerKnowledge, 
            $learningMode
        ]);

        if ($success) {
            echo json_encode(["status" => "success", "message" => "Thank you! Your enquiry has been submitted successfully."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Something went wrong. Please try again."]);
        }

    } catch (PDOException $e) {
        // Handle database errors
        error_log("DB Lead Submission Error: " . $e->getMessage());
        echo json_encode(["status" => "error", "message" => "Sorry, we encountered a technical issue. Please try later."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}
?>

