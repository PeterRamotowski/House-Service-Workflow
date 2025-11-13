<?php

namespace App\ServiceManagement\Application;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\Workflow\Transition;

/**
 * Service to check workflow-related permissions based on metadata.
 * 
 * Provides utilities to:
 * - Check if a user can access specific workflow places
 * - Get available transitions for a user based on their roles
 * - Validate role-based access to workflow transitions
 */
class WorkflowAccessChecker
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker
    ) {
    }

    /**
     * Check if the current user can access a specific place based on metadata roles.
     */
    public function canAccessPlace(WorkflowInterface $workflow, string $placeName): bool
    {
        $placeMetadata = $workflow->getMetadataStore()->getPlaceMetadata($placeName);
        $allowedRoles = $placeMetadata['allowed_roles'] ?? [];

        if (empty($allowedRoles)) {
            return true;
        }

        foreach ($allowedRoles as $role) {
            if ($this->authorizationChecker->isGranted($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the current user can execute a specific transition based on metadata roles.
     */
    public function canExecuteTransition(WorkflowInterface $workflow, Transition $transition): bool
    {
        $transitionMetadata = $workflow->getMetadataStore()->getTransitionMetadata($transition);
        $allowedRoles = $transitionMetadata['allowed_roles'] ?? [];

        if (empty($allowedRoles)) {
            return true;
        }

        foreach ($allowedRoles as $role) {
            if ($this->authorizationChecker->isGranted($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all transitions the current user is allowed to execute for a given workflow.
     */
    public function getAvailableTransitions(WorkflowInterface $workflow, object $subject): array
    {
        $availableTransitions = [];

        foreach ($workflow->getDefinition()->getTransitions() as $transition) {
            // Check if the transition is possible given the current state
            if (!$workflow->can($subject, $transition->getName())) {
                continue;
            }

            // Check if user has permission to execute this transition
            if ($this->canExecuteTransition($workflow, $transition)) {
                $availableTransitions[] = $transition->getName();
            }
        }

        return array_unique($availableTransitions);
    }
}
