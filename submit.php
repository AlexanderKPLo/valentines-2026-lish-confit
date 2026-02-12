<?php
/**
 * PROJECT: THE ASH 2026 | Love & Reunion
 * PURPOSE: Backend Logic for RSVP & Dish Claim
 * NOTE: This is a Portfolio Archive. GitHub Pages does not support PHP execution.
 * -------------------------------------------------------------------------
 * * [Lish Comment]
 * Yo, welcome to the backend of the Ash Family Feast. 
 * Since we are hosting on GitHub Pages, this script is for "Showcase Only".
 * If this was on a real LAMP/LEMP server, it would be handling the dishes.json like a boss.
 */

// 1. Setting the Vibe (UK Timezone)
// 既然係 UK Project, 時區梗係要 Set 返啱, 唔係啲 Deadline 就會好 confusing.
date_default_timezone_set('Europe/London');

// 2. Deadline Management
// February 14th is the peak of romance, so we close at midnight.
$deadline = strtotime("2026-02-15 00:00:00");
$now = time();

if ($now > $deadline) {
    // Too late, the party has already started or ended!
    die("<h1>Registration Closed</h1><p>The Valentine's Potluck registration ended on 14 Feb 23:59 GMT.</p>");
}

// 3. Security Check (The Secret Code)
// [Security Note] Never hardcode real passwords here in a public repo.
// I've updated the code to 'demo0214' for this portfolio showcase.
$secret = "demo0214"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate the secret passcode
    // 如果暗號唔啱, 就 send 佢返去主頁 plus an error message.
    $user_pass = $_POST['passcode'] ?? '';
    if ($user_pass !== $secret) {
        header("Location: index.html?error=wrong_pass");
        exit("Unauthorized access.");
    }

    // 4. Data Sanitization (防止有人玩嘢)
    // Clean the input to prevent XSS. We want good food, not malicious scripts.
    $name = trim(strip_tags($_POST['name']));
    $dishes = isset($_POST['dish']) ? $_POST['dish'] : [];
    $arrival = isset($_POST['arrival_time']) ? strip_tags($_POST['arrival_time']) : '';
    $file = 'dishes.json';

    // 5. Handling the Database (JSON Flat-file)
    // Read the current guest list from our JSON 'database'.
    $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    if (!is_array($data)) $data = [];

    // Filter out empty dish entries, very target!
    $clean_dishes = array_values(array_filter(array_map('strip_tags', $dishes)));

    // Check if the family already exists - Update if yes, Create if no.
    $updated = false;
    foreach ($data as &$entry) {
        if ($entry['name'] === $name) {
            $entry['dishes'] = $clean_dishes;
            $entry['arrival'] = $arrival;
            $entry['timestamp'] = date("H:i");
            $updated = true;
            break;
        }
    }
    
    if (!$updated) {
        $data[] = [
            "name" => $name,
            "dishes" => $clean_dishes,
            "arrival" => $arrival,
            "timestamp" => date("H:i")
        ];
    }

    // 6. Finalizing & Redirect
    // Use LOCK_EX to avoid file corruption during high traffic. 
    // Although GitHub won't execute this, it's good to show you know your stuff.
    file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);

    // After success, jump back to the live-menu with a cache-buster (?v=time)
    header("Location: index.html?status=success&v=" . time() . "#live-menu-list");
    exit();

} else {
    // If someone tries to access this script directly, send them home.
    header("Location: index.html");
    exit();
}
?>