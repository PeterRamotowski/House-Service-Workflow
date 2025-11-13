<?php

namespace App\ServiceManagement\Application;

use App\ServiceManagement\Domain\ServiceRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Event\EnteredEvent;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\Event\TransitionEvent;

class WorkflowSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private LoggerInterface $logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.service_request.transition' => 'onTransition',
            'workflow.service_request.completed' => 'onCompleted',
            'workflow.service_request.entered.completed' => 'onEnteredCompleted',
            'workflow.service_request.guard' => 'onGuard',
        ];
    }

    public function onGuard(GuardEvent $event): void
    {
        $user = $this->security->getUser();
        $transition = $event->getTransition();

        if ($user) {
            $this->logger->info('Workflow guard check', [
                'user' => $user->getUserIdentifier(),
                'transition' => $transition->getName(),
                'is_blocked' => $event->isBlocked(),
            ]);
        }
    }

    public function onTransition(TransitionEvent $event): void
    {
        /** @var ServiceRequest $serviceRequest */
        $serviceRequest = $event->getSubject();
        $transition = $event->getTransition();
        $from = $transition->getFroms();
        $to = $transition->getTos();

        $serviceRequest->addWorkflowHistoryEntry(
            $from[0],
            $to[0],
            $transition->getName()
        );
    }

    public function onCompleted(CompletedEvent $event): void
    {
        /** @var ServiceRequest $serviceRequest */
        $serviceRequest = $event->getSubject();

        // TODO: add additional logic, such as sending notifications
    }

    public function onEnteredCompleted(EnteredEvent $event): void
    {
        /** @var ServiceRequest $serviceRequest */
        $serviceRequest = $event->getSubject();

        if ($serviceRequest->getCurrentPlace() === 'completed' && !$serviceRequest->getCompletedDate()) {
            $serviceRequest->setCompletedDate(new \DateTimeImmutable());
            $this->entityManager->persist($serviceRequest);
            $this->entityManager->flush();
        }
    }
}
