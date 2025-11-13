<?php

namespace App\Property\UI;

use App\IdentityAccess\Domain\User;
use App\Property\Domain\House;
use App\Property\Infrastructure\Persistence\DoctrineHouseRepository;
use App\Property\UI\HouseType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/house')]
class HouseController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator
    ) {}

    #[Route('/', name: 'app_house_index', methods: ['GET'])]
    public function index(DoctrineHouseRepository $houseRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MANAGER')) {
            $houses = $houseRepository->findActiveHouses();
        } else {
            $houses = $houseRepository->findByOwner($user->getId());
        }

        return $this->render('house/index.html.twig', [
            'houses' => $houses,
        ]);
    }

    #[Route('/new', name: 'app_house_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MANAGER');

        $house = new House();
        $form = $this->createForm(HouseType::class, $house);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($house);
            $entityManager->flush();

            $this->addFlash('success', $this->translator->trans('flash.success.created', ['%entity%' => $this->translator->trans('house.title')]));
            return $this->redirectToRoute('app_house_show', ['id' => $house->getId()]);
        }

        return $this->render('house/new.html.twig', [
            'house' => $house,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_house_show', methods: ['GET'])]
    public function show(House $house): Response
    {
        $this->checkAccess($house);

        return $this->render('house/show.html.twig', [
            'house' => $house,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_house_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, House $house, EntityManagerInterface $entityManager): Response
    {
        $this->checkAccess($house);

        $form = $this->createForm(HouseType::class, $house);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', $this->translator->trans('flash.success.updated', ['%entity%' => $this->translator->trans('house.title')]));
            return $this->redirectToRoute('app_house_show', ['id' => $house->getId()]);
        }

        return $this->render('house/edit.html.twig', [
            'house' => $house,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_house_delete', methods: ['POST'])]
    public function delete(Request $request, House $house, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete' . $house->getId(), $request->request->get('_token'))) {
            $entityManager->remove($house);
            $entityManager->flush();

            $this->addFlash('success', $this->translator->trans('flash.success.deleted', ['%entity%' => $this->translator->trans('house.title')]));
        }

        return $this->redirectToRoute('app_house_index');
    }

    private function checkAccess(House $house): void
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_MANAGER')) {
            return;
        }

        if ($house->isOwner($user)) {
            return;
        }

        throw $this->createAccessDeniedException($this->translator->trans('flash.error.access_denied', ['%entity%' => $this->translator->trans('house.title')]));
    }
}
