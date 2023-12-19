<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\CreateUserType;
use App\Form\EditUserType;
use App\Repository\UserRepository;
use App\Security\Voter\UserVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    #[Route('/user/{id}/show', name: 'app_user_show')]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/trombi', name: 'app_user_trombi')]
    public function trombi(
        UserRepository $userRepository,
        UserVoter $userVoter,
    ): Response {
        if (!$this->isGranted('READ')) {
            $errorCode = $userVoter->calculateErrors(
                'READ',
                null,
                $this->getUser()
            );
            if ($errorCode === $userVoter->getErrorCode('USER_NOT_VALIDATED_BY_ADMIN')) {
                return $this->render('user/user-not-activated.html.twig');
            }
            $this->denyAccessUnlessGranted('READ');
        }
        $users = $userRepository->findAll();
        return $this->render('user/trombi.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/user/create', name: 'app_user_create')]
    public function create(
        Request $request,
         UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = new User();
        // create form from InviteUserType
        $form = $this->createForm(CreateUserType::class, $user);

        // handle form
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            // save user
            $entityManager->persist($user);
            $entityManager->flush();

            // redirect to user show page
            return $this->redirectToRoute('app_user_trombi', [
                'id' => $user->getId(),
            ]);
        }

        return $this->render('user/create.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/user/{id}/edit', name: 'app_user_edit')]
    public function edit(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        // create form from InviteUserType
        $form = $this->createForm(EditUserType::class, $user);

        // handle form
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // save user
            $entityManager->persist($user);
            $entityManager->flush();
            // flash message success
            $this->addFlash('success', 'User updated');
            // redirect to user show page
            return $this->redirectToRoute('app_user_trombi', [
                'id' => $user->getId(),
            ]);
        } else {
            // flash message error
            $this->addFlash('error', 'User not updated');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form
        ]);
    }
    #[Route('/user/{id}/toggle-activate', name: 'app_user_toggle_activate')]
    public function activate(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted('ACTIVATE', $user);

        if ($this->isCsrfTokenValid(
            'activate'.$user->getId(),
            $request->request->get('_token')
        )) {
            if ($user->isValidatedByAdmin()) {
                $user->setValidatedByAdminAt(null);
            } else {
                $user->setValidatedByAdminAt(new \DateTime());
            }
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_trombi');
    }

    #[Route('/user/{id}/delete', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid(
                'delete'.$user->getId(),
                $request->request->get('_token')
        )) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_trombi');
    }
}
