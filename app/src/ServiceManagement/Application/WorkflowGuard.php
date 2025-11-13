<?php

namespace App\ServiceManagement\Application;

use App\ServiceManagement\Domain\ServiceRequest;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Guards workflow transitions based on user roles defined in workflow metadata.
 * 
 * This guard ensures that:
 * - Cleaners can only self-assign, start work, and submit for review (cannot review their own work or assign others)
 * - Owners cannot assign cleaners or approve requests (only view completed work)
 * - Managers/Admins can perform administrative transitions (approve, assign, review, complete)
 */
class WorkflowGuard implements EventSubscriberInterface
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private Security $security,
        private readonly WorkflowInterface $serviceRequestWorkflow,
        private TranslatorInterface $translator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.service_request.guard' => ['guardTransition'],
        ];
    }

    public function guardTransition(GuardEvent $event): void
    {
        /** @var ServiceRequest $serviceRequest */
        $serviceRequest = $event->getSubject();
        $transition = $event->getTransition();
        $transitionMetadata = $this->serviceRequestWorkflow->getMetadataStore()->getTransitionMetadata($transition);
        $allowedRoles = $transitionMetadata['allowed_roles'] ?? [];

        if (empty($allowedRoles)) {
            return;
        }

        $hasAllowedRole = false;
        foreach ($allowedRoles as $role) {
            if ($this->authorizationChecker->isGranted($role)) {
                $hasAllowedRole = true;
                break;
            }
        }

        if (!$hasAllowedRole) {
            $event->setBlocked(true, $this->translator->trans(
                'workflow.error.no_permission',
                ['%roles%' => implode(', ', $allowedRoles)]
            ));
            return;
        }

        $transitionName = $transition->getName();

        // For self_assign: ensure cleaner is not already assigned to another request at the same time
        if ($transitionName === 'self_assign' && $this->authorizationChecker->isGranted('ROLE_CLEANER')) {
            // TODO: check if cleaner has conflicting assignments
        }

        // For start_work: ensure the cleaner is assigned to this request
        if ($transitionName === 'start_work' && $this->authorizationChecker->isGranted('ROLE_CLEANER')) {
            $user = $this->security->getUser();
            if ($serviceRequest->getAssignedCleaner() !== $user) {
                $event->setBlocked(true, $this->translator->trans('workflow.error.start_work_assigned_only'));
                return;
            }
        }

        // For submit_for_review: ensure the cleaner is assigned to this request
        if ($transitionName === 'submit_for_review' && $this->authorizationChecker->isGranted('ROLE_CLEANER')) {
            $user = $this->security->getUser();
            if ($serviceRequest->getAssignedCleaner() !== $user) {
                $event->setBlocked(true, $this->translator->trans('workflow.error.submit_review_assigned_only'));
                return;
            }
        }

        // For assign transition: prevent cleaners from assigning to others
        if ($transitionName === 'assign') {
            if ($this->authorizationChecker->isGranted('ROLE_CLEANER') &&
                !$this->authorizationChecker->isGranted('ROLE_MANAGER')) {
                $event->setBlocked(true, $this->translator->trans('workflow.error.cleaner_cannot_assign'));
                return;
            }
        }
    }
}
