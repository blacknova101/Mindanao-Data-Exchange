# Mindanao Data Exchange - Organization Membership System

This document provides an overview of the organization membership request system implemented for the Mindanao Data Exchange platform.

## System Overview

The organization membership system allows users to request to join organizations. Organization owners (creators) can then approve or reject these requests. The system includes automatic expiration of pending requests and an auto-accept feature for organizations that want to accept all requests automatically.

## Key Features

1. **Request-based Membership**: Users must request to join an organization and be approved by the organization owner.
2. **Request Expiration**: Requests expire after 14 days if not processed.
3. **Auto-Accept Option**: Organizations can enable automatic approval of all membership requests.
4. **Owner Responses**: Organization owners can include a custom message when approving or rejecting requests.
5. **Cancellation**: Users can cancel their pending requests.
6. **Notifications**: The system sends notifications for all major events (request submitted, approved, rejected, expired, cancelled).

## Database Tables

The following database tables are used for the organization membership system:

1. **organization_membership_requests**: Stores all membership requests with their status and messages.
2. **user_notifications**: Stores notifications sent to users about request status changes.
3. **organizations**: Contains the auto_accept setting for each organization and created_by field for owner reference.
4. **users**: Associates users with their organization.

## File Structure

- **process_join_org.php**: Handles the creation of membership requests and auto-acceptance.
- **manage_org_requests.php**: Owner interface for managing membership requests.
- **expire_requests.php**: Script to mark expired requests and notify users and organization owners.
- **cancel_request.php**: Handles the cancellation of pending requests.
- **setup_expiration_task.bat**: Batch file to set up a scheduled task for expiration checking.

## Workflow

1. A user visits join_organization.php and selects an organization to join.
2. The user submits a request with a message via process_join_org.php.
3. If the organization has auto-accept enabled, the user is immediately added to the organization.
4. Otherwise, the request is stored as "Pending" and the organization owner is notified.
5. The owner can view, approve, or reject the request via manage_org_requests.php.
6. If approved, the user is added to the organization.
7. If rejected, the user can submit a new request in the future.
8. If the request expires (14 days), it's marked as "Expired" and both the user and organization owner are notified.

## Organization Owner Controls

Organization owners (the users who created the organization) have access to the following features:

1. **Manage Membership Requests**: View all requests and their details.
2. **Approve/Reject Requests**: Process membership requests with optional feedback messages.
3. **Auto-Accept Setting**: Toggle automatic acceptance of membership requests.

## Scheduled Tasks

The system includes a scheduled task (set up via setup_expiration_task.bat) that runs expire_requests.php daily to check for and process expired requests.

## User Interface

1. **User Settings Page**: Shows pending request details for users and owner controls for organization creators.
2. **Join Organization Page**: Displays organization list and request form, or pending request status.
3. **Manage Requests Page**: Administrative interface for organization owners to process requests.

## Security Considerations

- Only organization owners (creators) can manage membership requests for their organization.
- Organization admins who are not the creator do not have access to manage membership requests.
- Users can only have one pending request at a time.
- Request cancellation is only possible for the user who created the request.
- The expiration script has protection against direct browser access.

## Customization Options

1. **Expiration Period**: Default is 14 days, can be modified in process_join_org.php.
2. **Auto-Accept**: Can be enabled/disabled per organization via the owner interface.
3. **Response Messages**: Owners can include custom messages when approving or rejecting. 