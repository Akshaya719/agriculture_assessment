<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] != 1) {
    header("Location: login.php");
    exit();
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate input
    $region_name = mysqli_real_escape_string($conn, $_POST['region_name']);
    $state = mysqli_real_escape_string($conn, $_POST['state']);
    $district = mysqli_real_escape_string($conn, $_POST['district']);
    $agro_climatic_zone = mysqli_real_escape_string($conn, $_POST['agro_climatic_zone']);

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Insert into regions table
        $region_query = "INSERT INTO regions (region_name, state, district, agro_climatic_zone) 
                         VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $region_query);
        mysqli_stmt_bind_param($stmt, "ssss", $region_name, $state, $district, $agro_climatic_zone);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error inserting region: " . mysqli_error($conn));
        }
        $region_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        // Insert land holdings
        if (!empty($_POST['holding_size']) && !empty($_POST['count'])) {
            $holding_sizes = $_POST['holding_size'];
            $counts = $_POST['count'];

            for ($i = 0; $i < count($holding_sizes); $i++) {
                $holding_size = mysqli_real_escape_string($conn, $holding_sizes[$i]);
                $count = (int)$counts[$i];

                $holding_query = "INSERT INTO land_holdings (region_id, holding_size, count) 
                                 VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conn, $holding_query);
                mysqli_stmt_bind_param($stmt, "isi", $region_id, $holding_size, $count);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error inserting land holding: " . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt);
            }
        }

        // Insert irrigation sources and region irrigation
        if (!empty($_POST['source_name']) && !empty($_POST['area_irrigated'])) {
            $source_names = $_POST['source_name'];
            $areas_irrigated = $_POST['area_irrigated'];

            for ($i = 0; $i < count($source_names); $i++) {
                $source_name = mysqli_real_escape_string($conn, $source_names[$i]);
                $area_irrigated = (float)$areas_irrigated[$i];

                // Check if irrigation source exists, or insert new one
                $source_query = "SELECT source_id FROM irrigation_sources WHERE source_name = ?";
                $stmt = mysqli_prepare($conn, $source_query);
                mysqli_stmt_bind_param($stmt, "s", $source_name);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                if (mysqli_num_rows($result) > 0) {
                    $source_row = mysqli_fetch_assoc($result);
                    $source_id = $source_row['source_id'];
                } else {
                    $insert_source_query = "INSERT INTO irrigation_sources (source_name) VALUES (?)";
                    $stmt = mysqli_prepare($conn, $insert_source_query);
                    mysqli_stmt_bind_param($stmt, "s", $source_name);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Error inserting irrigation source: " . mysqli_error($conn));
                    }
                    $source_id = mysqli_insert_id($conn);
                    mysqli_stmt_close($stmt);
                }

                // Insert into region_irrigation
                $irrigation_query = "INSERT INTO region_irrigation (region_id, source_id, area_irrigated) 
                                    VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conn, $irrigation_query);
                mysqli_stmt_bind_param($stmt, "iid", $region_id, $source_id, $area_irrigated);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error inserting region irrigation: " . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt);
            }
        }

        // Commit transaction
        mysqli_commit($conn);
        header("Location: regions.php?success=Region added successfully");
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $error = $e->getMessage();
        header("Location: regions.php?error=" . urlencode($error));
        exit();
    }
} else {
    // If not a POST request, redirect to regions.php with an error
    header("Location: regions.php?error=" . urlencode("Invalid request method"));
    exit();
}

mysqli_close($conn);
?>