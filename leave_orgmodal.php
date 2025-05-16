<!-- Leave Organization Modal -->
<style>
    #leaveOrgModal {
        display: none; 
        position: fixed; 
        z-index: 1000; 
        left: 0;
        top: 0;
        width: 100%; 
        height: 100%; 
        background-color: rgba(0,0,0,0.7);
        overflow: auto;
        align-items: center;
        justify-content: center;
    }
    
    .leave-org-modal-content {
        background: #fff; /* white background */
        border: 2px solid #0099ff; /* blue border */
        color: #333; /* text color */
        box-shadow: 0 0 20px rgba(0, 153, 255, 0.2); /* subtle blue shadow */
        padding: 30px 20px;
        border-radius: 16px;
        max-width: 400px;
        margin: 15% auto; /* 15% from the top and centered */
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
        padding: 10px 15px;
        text-decoration: none;
        font-size: 16px;
        border-radius: 5px;
        cursor: pointer;
        width: auto;
        min-width: 160px;
        height: 44px;
        box-sizing: border-box;
        margin: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        line-height: 1;
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
    }
    .leave-org-modal-content button[type="submit"]:hover {
        background-color: #007acc;
    }
    .leave-org-modal-content button[type="button"]:hover {
        background-color: #0099ff;
        color: white;
    }
    
    /* Focus and active states for better accessibility */
    .leave-org-modal-content button[type="submit"]:focus,
    .leave-org-modal-content button[type="submit"]:active {
        background-color: #006bb3;
        outline: none;
    }
    
    .leave-org-modal-content button[type="button"]:focus,
    .leave-org-modal-content button[type="button"]:active {
        background-color: #0088e6;
        color: white;
        outline: none;
    }
    .leave-org-modal-content .warning-icon {
        font-size: 40px;
        color: #ff4d4d; /* red icon */
        margin-bottom: 10px;
        display: block;
    }
    .button-container {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: center;
        gap: 12px;
        margin-top: 0px;
        width: 100%;
    }
    
    /* Responsive styles for small screens */
    @media (max-width: 480px) {
        .button-container {
            flex-direction: column;
            gap: 8px;
        }
        
        .leave-org-modal-content button[type="submit"],
        .leave-org-modal-content button[type="button"] {
            width: 100%;
            margin: 3px 0;
        }
    }
</style>

<div id="leaveOrgModal">
    <div class="leave-org-modal-content">
        <i class="fas fa-exclamation-triangle warning-icon"></i>
        <h3>Are you sure you want to leave your organization?</h3>
        <p>This action will remove you from the organization and cannot be undone.</p>
        
        <?php
        // Check if user is the owner of the organization
        $check_owner_sql = "SELECT created_by FROM organizations WHERE organization_id = ?";
        $check_owner_stmt = $conn->prepare($check_owner_sql);
        $check_owner_stmt->bind_param("i", $user['organization_id']);
        $check_owner_stmt->execute();
        $check_owner_result = $check_owner_stmt->get_result();
        $owner_data = $check_owner_result->fetch_assoc();
        
        $is_owner = ($owner_data['created_by'] == $user_id);
        
        // Check member count
        $check_members_sql = "SELECT COUNT(*) as member_count FROM users WHERE organization_id = ?";
        $check_members_stmt = $conn->prepare($check_members_sql);
        $check_members_stmt->bind_param("i", $user['organization_id']);
        $check_members_stmt->execute();
        $count_result = $check_members_stmt->get_result();
        $member_count = $count_result->fetch_assoc()['member_count'];
        
        if ($is_owner && $member_count <= 1) {
            echo '<p style="color: #ff4d4d; font-weight: bold;">Warning: You are the only member of this organization. If you leave, the organization will be permanently deleted.</p>';
        } elseif ($is_owner) {
            echo '<p style="color: #ff4d4d;">Note: As the organization owner, you should consider transferring ownership before leaving.</p>';
        }
        ?>
        
        <form action="leave_organization.php" method="POST">
            <input type="text" name="confirm_text" placeholder="Type 'LEAVE' to confirm" required>
            <input type="hidden" name="confirm_leave" value="yes">
            <div class="button-container">
                <button type="submit">Leave Organization</button>
                <button type="button" onclick="closeLeaveOrgModal()">Cancel</button>
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