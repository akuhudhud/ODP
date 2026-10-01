# Authentication Foundation

## Status

Proposed

## Purpose

This document defines the initial authentication architecture for ODP.

The purpose is to establish a stable identity foundation before implementing authentication workflows.

Business-specific authentication requirements are intentionally excluded at this stage.

---

## Core Principle

ODP uses a centralized account identity.

One account represents one identity across the ODP platform.

The same account may later access different ODP applications or capabilities without requiring separate accounts.

---

## Account Identity

An ODP account may support:

- Phone number
- Email address

The architecture does not currently designate phone number or email address as the single mandatory primary identifier.

The final authentication method will be defined when functional requirements are implemented.

---

## Application Access

The authentication system is shared across ODP applications.

Initial applications:

- User App
- Runner App
- Admin System

Application-specific permissions and capabilities will be defined separately.

---

## Future Capabilities

An account may later have different capabilities.

Examples:

- User
- Runner
- Admin

These capabilities must not require duplicate accounts for the same identity.

The exact authorization model will be defined separately from authentication.

---

## API Authentication

API authentication will be provided under API v1.

Planned base path:

`/api/v1/auth`

Potential endpoints:

- Register
- Login
- Logout
- Current account

These endpoints are not implemented at this stage.

---

## Authentication Technology

Laravel Sanctum is the planned foundation for API token authentication.

Implementation will only begin after the authentication requirements and database structure have been finalized.

---

## Scope Control

The following are intentionally excluded from this architecture stage:

- Registration workflow
- Login workflow
- Password reset
- OTP
- Phone verification
- Email verification
- Session management
- Role implementation
- Permission implementation
- Runner onboarding
- Admin authorization
- Business-specific authentication rules

---

## Architecture Principle

Authentication establishes identity.

Authorization determines what an authenticated account is allowed to do.

These concerns should remain separate.
