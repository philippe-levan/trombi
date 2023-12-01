<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\CreateUserType;
use App\Form\EditUserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
    public function trombi(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        return $this->render('user/trombi.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/user/create', name: 'app_user_create')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = new User();
        // create form from InviteUserType
        $form = $this->createForm(CreateUserType::class, $user);

        // handle form
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // save user
            $entityManager->persist($user);
            $entityManager->flush();

            // redirect to user show page
            return $this->redirectToRoute('app_user_trombi', [
                'id' => $user->getId(),
            ]);
        }

        return $this->render('user/create.html.twig', [
            'form' => $form->createView()
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

            // redirect to user show page
            return $this->redirectToRoute('app_user_trombi', [
                'id' => $user->getId(),
            ]);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView()
        ]);
    }
}
