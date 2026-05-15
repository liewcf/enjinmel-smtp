# EnjinMel SMTP v0.2.4 Release Notes

Security maintenance release for EnjinMel SMTP.

## Security Fixes

- Preserves any existing non-null `pre_wp_mail` return value, including `WP_Error`, before sending through EnjinMel. This keeps earlier mail-blocking and security filters authoritative.
- Neutralizes spreadsheet formula prefixes in exported email log CSV values.
- Renders dynamic Send Test Email failure messages as text instead of HTML.

## Development Tooling

- Updates the locked PHPUnit dev dependency to `12.5.25` to address CVE-2026-24765.

## Tests

- Adds regression coverage for preserving prior `WP_Error` mail blockers.
- Adds regression coverage for CSV formula neutralization.

## Packaging

- Keeps release packaging based on `git archive --worktree-attributes` and `.gitattributes` export-ignore rules so distributable zips exclude development assets.
