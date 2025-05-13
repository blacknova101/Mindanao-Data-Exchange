<?php
session_start();
include('db_connection.php');

// Assuming user ID is stored in session
$user_id = $_SESSION['user_id'];  // Adjust according to your session structure

// Check if user already has an organization
$check_user_org_query = "SELECT organization_id FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $check_user_org_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $organization_id);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if ($organization_id) {
    // User already has an organization
    $error = "You already belong to an organization and cannot create a new one.";
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $contact_email = trim($_POST['contact_email']);
    $website_url = trim($_POST['website_url']);
    $org_type = isset($_POST['org_type']) ? trim($_POST['org_type']) : ''; // Fallback value
    $org_type_specify = trim($_POST['org_type_specify'] ?? '');

    // Determine final organization type
    if ($org_type === 'Other' && empty($org_type_specify)) {
        $error = "Please specify the organization type.";
    } else {
        $final_type = ($org_type === 'Other') ? $org_type_specify : $org_type;
    }

    // Basic validation
    if (empty($name) || empty($contact_email)) {
        $error = "Name and contact email are required.";
    } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!isset($error)) {
        // Check if organization already exists
        $check_query = "SELECT COUNT(*) as count FROM organizations WHERE name = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "s", $name);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $count);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if ($count > 0) {
            $error = "Organization already exists.";
        } else {
            // Insert the new organization
            $insert_query = "INSERT INTO organizations (name, contact_email, website_url, type, created_at) VALUES (?, ?, ?, ?, NOW())";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "ssss", $name, $contact_email, $website_url, $final_type);
        
            if (mysqli_stmt_execute($stmt)) {
                // Update user's organization_id and user_type
                $organization_id = mysqli_insert_id($conn);
                $update_user_query = "UPDATE users SET organization_id = ?, user_type = 'with_organization' WHERE user_id = ?";
                $stmt = mysqli_prepare($conn, $update_user_query);
                mysqli_stmt_bind_param($stmt, "ii", $organization_id, $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
        
                // Update session variables so UI updates immediately
                $_SESSION['organization_id'] = $organization_id;
                $_SESSION['user_type'] = 'with_organization';
        
                $success = "Organization created successfully.";
                // Optionally redirect to settings or another page:
                // header("Location: user_settings.php");
                // exit();
            } else {
                $error = "Error creating organization: " . mysqli_error($conn);
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Organization</title>
    <style>
        body { font-family: Arial; padding: 2rem; background-color: #f8f8f8; }
        form { max-width: 500px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input, select, button { width: 100%; padding: 10px; margin: 10px 0; }
        .message { margin-top: 1rem; color: red; }
        .success { color: green; }
        label { margin-top: 10px; display: block; font-weight: bold; }
    </style>
</head>
<body>

<h2 style="text-align: center;">Create an Organization</h2>

<?php if (!empty($error)): ?>
    <div class="message"><?php echo $error; ?></div>
<?php elseif (!empty($success)): ?>
    <div class="message success"><?php echo $success; ?></div>
<?php endif; ?>

<form method="POST" action="">
    <input type="text" name="name" placeholder="Organization Name" required>
    <input type="email" name="contact_email" placeholder="Contact Email" required>
    <input type="url" name="website_url" placeholder="Website URL (optional)">
    
    <label for="org_type">Organization Type</label>
    <select name="org_type" id="org_type" required onchange="toggleSpecifyField()">
        <option value="">-- Select Type --</option>
        <option value="Academic">Academic</option>
        <option value="Government">Government</option>
        <option value="Non-Profit">Non-Profit</option>
        <option value="Commercial">Commercial</option>
        <option value="Other">Other</option>
    </select>

    <div id="specifyContainer" style="display:none;">
        <input type="text" name="org_type_specify" placeholder="Please specify">
    </div>

    <button type="submit">Create</button>
</form>

<script>
function toggleSpecifyField() {
    const select = document.getElementById('org_type');
    const specify = document.getElementById('specifyContainer');
    specify.style.display = (select.value === 'Other') ? 'block' : 'none';
}
</script>

</body>
</html>
