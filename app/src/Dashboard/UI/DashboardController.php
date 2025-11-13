<?php

namespace App\Dashboard\UI;

use App\IdentityAccess\Domain\User;
use App\Property\Infrastructure\Persistence\DoctrineHouseRepository;
use App\ServiceManagement\Infrastructure\Persistence\DoctrineServiceRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        DoctrineServiceRequestRepository $serviceRequestRepository,
        DoctrineHouseRepository $houseRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $upcomingRequests = $serviceRequestRepository->findUpcoming();

        if ($this->isGranted('ROLE_CLEANER') && !$this->isGranted('ROLE_MANAGER')) {
            $upcomingRequests = $serviceRequestRepository->findByAssignedCleaner($user->getId());
        } elseif ($this->isGranted('ROLE_OWNER') && !$this->isGranted('ROLE_MANAGER')) {
            $houses = $houseRepository->findByOwner($user->getId());
            $houseIds = array_map(fn($h) => $h->getId(), $houses);
            $upcomingRequests = array_filter(
                $upcomingRequests,
                fn($sr) => in_array($sr->getHouse()->getId(), $houseIds)
            );
        }

        return $this->render('dashboard/index.html.twig', [
            'upcoming_requests' => $upcomingRequests,
        ]);
    }
}
