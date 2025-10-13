# Feature Specification: EnjinMel SMTP for WordPress

> _Renamed from "EngineMail SMTP" on 2025-10-01. References to the former brand now point to EnjinMel; legacy hooks and data structures remain for compatibility._

**Feature Branch**: `001-wordpress-enjinmel-smtp`  
**Created**: 2025-09-09  
**Status**: Draft  
**Input**: User description: "The project specification is for a WordPress plugin that uses EnjinMel SMTP to send all WordPress transactional emails, effectively replacing the default PHP mail function to enhance email deliverability and reliability. The core objective is to create a straightforward and robust integration for WordPress site owners who use EnjinMel. The plugin's minimum viable product will consist of a settings page within the WordPress admin area where users can configure their EnjinMel credentials, including the SMTP host, port, encryption type (like SSL or TLS), and authentication details such as username and password, ensuring the password is encrypted upon storage. This configuration will be used to intercept the standard `wp_mail()` function via the `phpmailer_init` action hook, rerouting all outgoing emails through the specified EnjinMel server. To help users confirm their setup is working correctly, the settings page will also include a feature to send a test email to any address, providing clear feedback on whether the email was sent successfully or failed, along with basic error messages for common issues like incorrect credentials or connection problems. To finalize the plan, several key decisions are required. We need to decide if the plugin should allow users to set a default "From" name and "From" email address and if there should be an option to force these settings across all emails sent from the site, overriding configurations from other plugins. Another critical consideration is the implementation of email logging; options range from having no log, logging only failed emails with their error codes, or maintaining a full log of every email sent, which would also necessitate an option to disable logging to conserve database resources. We also need to consider if advanced features like a dashboard widget for email statistics or support for WordPress Multisite (with either network-wide or per-site settings) should be included in the initial release or a later version. Lastly, the desired level of detail for error reporting needs to be determined—whether simple, user-friendly messages are sufficient or if a full technical debug log should be available for advanced troubleshooting scenarios."

---

## User Scenarios & Testing

### Primary User Story
As a WordPress site owner, I want to easily configure my site to send all emails through my EnjinMel account (formerly EngineMail) so that I can have reliable email delivery for my transactional emails.

### Acceptance Scenarios
1. **Given** I am a WordPress admin, **When** I navigate to the plugin's settings page, **Then** I should see fields for SMTP host, port, encryption, username, and password.
2. **Given** I have entered my EnjinMel SMTP credentials and saved them, **When** my WordPress site sends an email (e.g., a password reset), **Then** the email should be sent through the EnjinMel SMTP server.
3. **Given** I am on the plugin's settings page, **When** I enter an email address and click "Send Test Email", **Then** I should receive a confirmation message indicating whether the email was sent successfully or not.

### Edge Cases
- What happens when incorrect SMTP credentials are provided?
- How does the system handle a failure to connect to the SMTP server?

---

## Requirements

### Functional Requirements
- **FR-001**: System MUST provide a settings page in the WordPress admin area for configuring SMTP credentials.
- **FR-002**: System MUST encrypt and store the SMTP password securely.
- **FR-003**: System MUST use the configured SMTP settings to send all emails from WordPress.
- **FR-004**: System MUST provide a feature to send a test email to a specified address.
- **FR-005**: System MUST display a clear success or failure message after a test email is sent.
- **FR-006**: System MUST handle connection errors and provide basic, user-friendly error messages.
- **FR-007**: System MUST allow users to set a default "From" name and "From" email address.
- **FR-008**: System MUST provide an option to force the default "From" settings for all outgoing emails.
- **FR-009**: System MUST maintain a full log of all emails sent.
- **FR-010**: System MUST provide an option to disable email logging.
- **FR-011**: System MUST provide a full technical debug log for error reporting.

---

## Out of Scope
- Dashboard widget for email statistics.
- WordPress Multisite support.

---

## Review & Acceptance Checklist

### Content Quality
- [ ] No implementation details (languages, frameworks, APIs)
- [x] Focused on user value and business needs
- [x] Written for non-technical stakeholders
- [x] All mandatory sections completed

### Requirement Completeness
- [x] No [NEEDS CLARIFICATION] markers remain
- [x] Requirements are testable and unambiguous  
- [x] Success criteria are measurable
- [x] Scope is clearly bounded
- [ ] Dependencies and assumptions identified
