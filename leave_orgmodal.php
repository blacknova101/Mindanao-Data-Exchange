<!-- Modal for Leave Organization Confirmation -->
<div id="leaveOrgModal" class="modal leave-org-modal">
    <div class="modal-content leave-org-modal-content">
        <span class="warning-icon">&#9888;</span>
        <h3>Are you sure you want to leave your organization?</h3>
        <p>Type <strong><?php echo htmlspecialchars($organizationName); ?></strong> to confirm:</p>
        <form action="leave_organization.php" method="POST">
            <input type="text" id="orgNameConfirm" name="orgNameConfirm" placeholder="Enter organization name" required>
            <input type="hidden" name="organization_id" value="<?php echo htmlspecialchars($organizationId); ?>">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($userId); ?>">
            <button type="submit">Confirm</button>
        </form>
        <button type="button" onclick="closeLeaveOrgModal()">Cancel</button>
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