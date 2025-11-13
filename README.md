# House Service Workflow

A demonstration of how Symfony's Workflow component lets you define business processes in YAML configuration files instead of scattering logic throughout your codebase.

## Why define your workflow in YAML?

**Business process as configuration**

Instead of hardcoding state transitions and rules in PHP classes, this project keeps the entire service request workflow in [`workflow.yaml`](app/config/packages/workflow.yaml). This approach offers several practical benefits:

- **Visibility**: The entire business process is visible in one file - no hunting through controllers and services to understand how requests move through the system
- **Easy modifications**: Change the workflow by editing YAML, not refactoring code. Add new states, modify transitions, or adjust permissions without touching PHP classes
- **Team collaboration**: Business stakeholders can review and understand the workflow configuration without reading application code
- **Documentation**: The YAML file serves as living documentation that stays in sync with actual behavior
- **Version control**: Track how your business process evolves over time through git history of a single configuration file

## The service request workflow

This application manages holiday house cleaning services through a multi-step process with different user roles:

**User roles:**
- **Manager** - Creates and oversees service requests, assigns cleaners
- **Cleaner** - Performs the work, can self-assign to available tasks
- **Owner** - Views their properties and service status
- **Admin** - Full system access

**Process flow:**

The workflow moves service requests through these stages:

1. Manager creates request in `draft` state
2. Submits for `pending_approval`
3. After approval, moves to `approved`
4. Gets `scheduled` for a specific date
5. Manager assigns or cleaner self-assigns - `assigned`
6. Cleaner starts work - `in_progress`
7. Work submitted for `review`
8. Manager completes or requests changes
9. Finished requests marked `completed`
10. Old records can be `archived`

Requests can be cancelled at most stages and some can be reopened if needed.

## What's in the YAML configuration?

The [`workflow.yaml`](app/config/packages/workflow.yaml) file defines:

- **10 workflow states** (places) with role-based viewing permissions
- **15 transitions** specifying who can move requests between states
- **Audit trail** automatically tracking all state changes
- **Role restrictions** controlling which users can trigger each transition

Example from the configuration:

```yaml
transitions:
    approve:
        from: pending_approval
        to: approved
        metadata:
            allowed_roles: [ROLE_MANAGER, ROLE_ADMIN]
```

This single declaration enforces that only managers and admins can approve requests, moving them from pending to approved status.

## Learning more

This is an educational project demonstrating workflow patterns. For building your own workflows, see:
- [Symfony Workflow component](https://symfony.com/doc/current/workflow.html)
- [State Machines](https://symfony.com/doc/current/workflow/state-machines.html)
