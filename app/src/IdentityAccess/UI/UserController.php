<?php

namespace App\IdentityAccess\UI;

use App\IdentityAccess\Domain\User;
use App\IdentityAccess\UI\UserType;
use App\IdentityAccess\Infrastructure\Persistence\DoctrineUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/user')]
class UserController extends AbstractController
{
    public function __construct(
        private TranslatorInterface $translator
    ) {}

    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(DoctrineUserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['is_new_user' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($plainPassword = $form->get('plainPassword')->getData()) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', $this->translator->trans('flash.success.created', ['%entity%' => $this->translator->trans('user.title')]));
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, DoctrineUserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $currentUser = $this->getUser();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if current user is editing themselves and trying to remove ROLE_ADMIN
            if ($currentUser === $user) {
                $newRoles = $user->getRoles();
                if (!in_array('ROLE_ADMIN', $newRoles, true)) {
                    $adminCount = $this->countActiveAdmins($userRepository, $user);

                    if ($adminCount === 0) {
                        $this->addFlash('error', $this->translator->trans('user.error.cannot_remove_own_admin'));
                        return $this->redirectToRoute('app_user_edit', ['id' => $user->getId()]);
                    }
                }
            }

            if ($plainPassword = $form->get('plainPassword')->getData()) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $entityManager->flush();

            $this->addFlash('success', $this->translator->trans('flash.success.updated', ['%entity%' => $this->translator->trans('user.title')]));
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager, DoctrineUserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $currentUser = $this->getUser();

            // Prevent deleting yourself if you're the only admin
            if ($currentUser === $user && in_array('ROLE_ADMIN', $user->getRoles(), true)) {
                $adminCount = $this->countActiveAdmins($userRepository, $user);

                if ($adminCount === 0) {
                    $this->addFlash('error', $this->translator->trans('user.error.cannot_delete_only_admin'));
                    return $this->redirectToRoute('app_user_index');
                }
            }

            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', $this->translator->trans('flash.success.deleted', ['%entity%' => $this->translator->trans('user.title')]));
        }

        return $this->redirectToRoute('app_user_index');
    }

    /**
     * Count active administrators excluding the specified user
     */
    private function countActiveAdmins(DoctrineUserRepository $userRepository, User $excludeUser): int
    {
        $allActiveUsers = $userRepository->findBy(['isActive' => true]);
        $adminCount = 0;

        foreach ($allActiveUsers as $u) {
            if ($u->getId() !== $excludeUser->getId() && in_array('ROLE_ADMIN', $u->getRoles(), true)) {
                $adminCount++;
            }
        }

        return $adminCount;
    }
}
