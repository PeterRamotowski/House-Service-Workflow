<?php

namespace App\ServiceManagement\UI;

use App\IdentityAccess\Domain\User;
use App\ServiceManagement\Application\WorkflowAccessChecker;
use App\ServiceManagement\Domain\ServiceRequest;
use App\ServiceManagement\Infrastructure\Persistence\DoctrineServiceRequestRepository;
use App\ServiceManagement\UI\ServiceRequestType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/service-request')]
class ServiceRequestController extends AbstractController
{
    public function __construct(
        private WorkflowInterface $serviceRequestStateMachine,
        private WorkflowAccessChecker $workflowAccessChecker,
        private TranslatorInterface $translator
    ) {}

    #[Route('/', name: 'app_service_request_index', methods: ['GET'])]
    public function index(DoctrineServiceRequestRepository $serviceRequestRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MANAGER')) {
            $serviceRequests = $serviceRequestRepository->findAll();
        } elseif ($this->isGranted('ROLE_CLEANER')) {
            $assignedRequests = $serviceRequestRepository->findByAssignedCleaner($user->getId());
            $availableRequests = $serviceRequestRepository->findAvailableForSelfAssignment();
            $serviceRequests = array_merge($assignedRequests, $availableRequests);
        } else {
            // Owner - show requests for their houses
            $serviceRequests = [];
            foreach ($user->getOwnedHouses() as $house) {
                $serviceRequests = array_merge(
                    $serviceRequests,
                    $serviceRequestRepository->findByHouse($house->getId())
                );
            }
        }

        return $this->render('service_request/index.html.twig', [
            'service_requests' => $serviceRequests,
        ]);
    }

    #[Route('/new', name: 'app_service_request_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        $serviceRequest = new ServiceRequest();
        $serviceRequest->setCreatedBy($this->getUser());

        $houseId = $request->query->get('house');
        if ($houseId && is_numeric($houseId) && $houseId > 0) {
            try {
                $house = $entityManager->getRepository(\App\Property\Domain\House::class)->find((int)$houseId);
                if ($house) {
                    $serviceRequest->setHouse($house);
                }
            } catch (\Exception $e) {
                // Silently ignore invalid house ID
            }
        }

        $form = $this->createForm(ServiceRequestType::class, $serviceRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($serviceRequest);
            $entityManager->flush();

            $this->addFlash('success', $this->translator->trans('flash.success.created', ['%entity%' => $this->translator->trans('service_request.title')]));
            return $this->redirectToRoute('app_service_request_show', ['id' => $serviceRequest->getId()]);
        }

        return $this->render('service_request/new.html.twig', [
            'service_request' => $serviceRequest,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_service_request_show', methods: ['GET'])]
    public function show(ServiceRequest $serviceRequest): Response
    {
        $this->checkAccess($serviceRequest);

        $workflow = $this->serviceRequestStateMachine;

        $enabledTransitions = $this->workflowAccessChecker->getAvailableTransitions(
            $workflow,
            $serviceRequest
        );

        return $this->render('service_request/show.html.twig', [
            'service_request' => $serviceRequest,
            'enabled_transitions' => $enabledTransitions,
            'workflow_places' => $workflow->getDefinition()->getPlaces(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_service_request_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ServiceRequest $serviceRequest, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        $form = $this->createForm(ServiceRequestType::class, $serviceRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', $this->translator->trans('flash.success.updated', ['%entity%' => $this->translator->trans('service_request.title')]));
            return $this->redirectToRoute('app_service_request_show', ['id' => $serviceRequest->getId()]);
        }

        return $this->render('service_request/edit.html.twig', [
            'service_request' => $serviceRequest,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/transition/{transition}', name: 'app_service_request_transition', methods: ['POST'])]
    public function transition(
        ServiceRequest $serviceRequest,
        string $transition,
        EntityManagerInterface $entityManager
    ): Response {
        $this->checkAccess($serviceRequest);

        try {
            if ($this->serviceRequestStateMachine->can($serviceRequest, $transition)) {
                if ($transition === 'self_assign' && $this->isGranted('ROLE_CLEANER')) {
                    $serviceRequest->setAssignedCleaner($this->getUser());
                }

                $this->serviceRequestStateMachine->apply($serviceRequest, $transition);
                $entityManager->flush();

                $this->addFlash('success', $this->translator->trans('flash.success.transition', ['%transition%' => $transition]));
            } else {
                $this->addFlash('error', $this->translator->trans('flash.error.transition_not_possible', ['%transition%' => $transition]));
            }
        } catch (\Exception $e) {
            $this->addFlash('error', $this->translator->trans('flash.error.transition_error', ['%error%' => $e->getMessage()]));
        }

        return $this->redirectToRoute('app_service_request_show', ['id' => $serviceRequest->getId()]);
    }

    #[Route('/{id}', name: 'app_service_request_delete', methods: ['POST'])]
    public function delete(Request $request, ServiceRequest $serviceRequest, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete' . $serviceRequest->getId(), $request->request->get('_token'))) {
            $entityManager->remove($serviceRequest);
            $entityManager->flush();

            $this->addFlash('success', $this->translator->trans('flash.success.deleted', ['%entity%' => $this->translator->trans('service_request.title')]));
        }

        return $this->redirectToRoute('app_service_request_index');
    }

    private function checkAccess(ServiceRequest $serviceRequest): void
    {
        $user = $this->getUser();

        // Admin and Manager have full access
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MANAGER')) {
            return;
        }

        // Cleaner can access assigned requests OR scheduled requests (for self-assignment)
        if ($this->isGranted('ROLE_CLEANER')) {
            if ($serviceRequest->getAssignedCleaner() === $user) {
                return;
            }
            // Allow access to unassigned scheduled requests
            if ($serviceRequest->getCurrentPlace() === 'scheduled' && $serviceRequest->getAssignedCleaner() === null) {
                return;
            }
        }

        // Owner can only access requests for their houses
        if ($this->isGranted('ROLE_OWNER') && $serviceRequest->getHouse()->getOwner() === $user) {
            return;
        }

        throw $this->createAccessDeniedException($this->translator->trans('flash.error.access_denied', ['%entity%' => $this->translator->trans('service_request.title')]));
    }
}
