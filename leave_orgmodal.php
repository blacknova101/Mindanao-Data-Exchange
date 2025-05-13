<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        .leave-org-modal-content {
            background: #fff !important; /* white background */
            border: 2px solid #0099ff; /* blue border */
            color: #ff4d4d; /* red text for caution */
            box-shadow: 0 0 20px #0099ff22; /* subtle blue shadow */
            padding: 30px 20px;
            border-radius: 16px;
            max-width: 400px;
            margin: auto;
            text-align: center;
            position: relative;
        }
        .leave-org-modal-content h3 {
            color: #ff4d4d; /* red for caution */
            margin-bottom: 10px;
        }
        .leave-org-modal-content p {
            color: #0099ff; /* blue for instructions */
            margin-bottom: 20px;
        }
        .leave-org-modal-content input[type="text"] {
            border: 1.5px solid #0099ff; /* blue border */
            border-radius: 5px;
            padding: 8px;
            width: 80%;
            margin-bottom: 15px;
            color: #222;
            background: #fff;
        }
        .leave-org-modal-content form {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
        }
        .leave-org-modal-content button[type="submit"],
        .leave-org-modal-content button[type="button"] {
            padding: 12px 24px;
            text-decoration: none;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            width: 120px;
            height: 48px;
            box-sizing: border-box;
            margin: 0;
        }
        .leave-org-modal-content button[type="submit"] {
            background: #0099ff;
            color: white;
            border: none;
            transition: background-color 0.3s ease;
        }
        .leave-org-modal-content button[type="button"] {
            background: white;
            color: #0099ff;
            border: 1.5px solid #0099ff;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        .leave-org-modal-content button[type="submit"]:hover {
            background-color: #007acc;
        }
        .leave-org-modal-content button[type="button"]:hover {
            background-color: #0099ff;
            color: white;
        }
        .leave-org-modal-content .warning-icon {
            font-size: 40px;
            color: #ff4d4d; /* red icon */
            margin-bottom: 10px;
            display: block;
        }
        #cancel-btn {
            margin-top: 5px;
        }
    </style>
</head>
<body>
<div id="leaveOrgModal" class="modal leave-org-modal">
    <div class="modal-content leave-org-modal-content">
        <span class="warning-icon">&#9888;</span>
        <h3>Are you sure you want to leave your organization?</h3>
        <p>Type <strong><?php echo htmlspecialchars($organizationName); ?></strong> to confirm:</p>
        <form action="leave_organization.php" method="POST">
            <input type="text" id="orgNameConfirm" name="orgNameConfirm" placeholder="Enter organization name" required>
            <input type="hidden" name="organization_id" value="<?php echo htmlspecialchars($organizationId); ?>">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($userId); ?>">
            <div class="button-group">
                <button id="confirm-btn" type="submit">Confirm</button>
                <button id="cancel-btn" type="button" onclick="closeLeaveOrgModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>
<script>
    // Function to show leave organization confirmation modal
    function showLeaveOrgModal() {
        document.getElementById("leaveOrgModal").style.display = "flex";
    }

    // Function to close the leave organization modal
    function closeLeaveOrgModal() {
        document.getElementById("leaveOrgModal").style.display = "none";
    }


    function showModal() {
        document.getElementById("categoryModal").style.display = "flex";
    }
    function hideModal() {
        document.getElementById("categoryModal").style.display = "none";
    }
        document.addEventListener("DOMContentLoaded", function () {
        document.getElementById("categoryModal").style.display = "none";
    });
</script>
</body>
</html>