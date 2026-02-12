<?php
/**
 * Project: THE ASH 2026 | Valentine's Day Reunion
 * Description: Backend handler for RSVP submissions and dish registration.
 * Environment: Archive Version (Static Hosting Simulation)
 * * Note: This script is intended for demonstration in a technical portfolio.
 * GitHub Pages provides static hosting and does not execute PHP server-side logic.
 */

/* 1. Configuration & Global Settings */
// Set timezone to Europe/London to ensure consistency with UK event scheduling.
date_default_timezone_set('Europe/London');

// Define the RSVP deadline: 14th Feb 2026 at 23:59:59 GMT.
$deadline = strtotime("2026-02-15 00:00:00");
$now = time();

/**
 * Handle Deadline Enforcement
 * Prevents further registrations once the event window has closed.
 */
if ($now > $deadline) {
    http_response_code(403); // Forbidden
    die("<h1>Registration Closed</h1><p>The deadline for this event was 14 Feb 23:59 GMT. Thank you for your interest.</p>");
}

/* 2. Authentication & Security */
/**
 * Passcode Validation
 * In a production environment, this would be authenticated against a hashed password in a database.
 * For this demonstration, we use a constant: 'demo0214'.
 */
$secret_passcode = "demo0214"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Extract and validate the passcode from the POST request.
    $user_pass = $_POST['passcode'] ?? '';
    if ($user_pass !== $secret_passcode) {
        // Redirect back with an error flag if authentication fails.
        header("Location: index.html?error=wrong_pass");
        exit("Unauthorized: Invalid Passcode.");
    }

    /* 3. Data Sanitization & Normalization */
    /**
     * Sanitize user input to mitigate Cross-Site Scripting (XSS) risks.
     * We use strip_tags and trim to ensure clean data entry.
     */
    $name = trim(strip_tags($_POST['name']));
    $dishes = isset($_POST['dish']) ? $_POST['dish'] : [];
    $arrival = isset($_POST['arrival_time']) ? strip_tags($_POST['arrival_time']) : '';
    $data_file = 'dishes.json';

    /* 4. Persistence Logic (Flat-file JSON Storage) */
    /**
     * Load existing data from the local JSON file.
     * If the file does not exist, initialize an empty array.
     */
    $current_data = file_exists($data_file) ? json_decode(file_get_contents($data_file), true) : [];
    if (!is_array($current_data)) $current_data = [];

    // Filter out empty dish entries and ensure proper indexing.
    $sanitized_dishes = array_values(array_filter(array_map('strip_tags', $dishes)));

    /**
     * Upsert Logic (Update or Insert)
     * Check if a record already exists for the given name.
     */
    $record_updated = false;
    foreach ($current_data as &$entry) {
        if ($entry['name'] === $name) {
            $entry['dishes'] = $sanitized_dishes;
            $entry['arrival'] = $arrival;
            $entry['timestamp'] = date("H:i");
            $record_updated = true;
            break;
        }
    }
    
    // Create a new record if no existing match was found.
    if (!$record_updated) {
        $current_data[] = [
            "name" => $name,
            "dishes" => $sanitized_dishes,
            "arrival" => $arrival,
            "timestamp" => date("H:i")
        ];
    }

    /**
     * File Write Operation
     * Utilises LOCK_EX (Exclusive Lock) to prevent race conditions or file corruption 
     * during concurrent write attempts.
     */
    file_put_contents($data_file, json_encode($current_data, JSON_UNESCAPED_UNICODE), LOCK_EX);

    /* 5. Response & Redirection */
    // Redirect back to the UI with a success flag and cache-buster.
    header("Location: index.html?status=success&v=" . time() . "#live-menu-list");
    exit();

} else {
    // Redirect direct GET requests back to the landing page.
    header("Location: index.html");
    exit();
}
?>