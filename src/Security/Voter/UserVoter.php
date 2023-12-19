<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class UserVoter extends Voter
{
    public const WRITE = 'WRITE';
    public const READ = 'READ';
    public const ACTIVATE = 'ACTIVATE';

    public const ERROR_MESSAGES = [
        'NOT_CONNECTED' => 'Vous n\'êtes pas connecté',
        'CAN_NOT_ACTIVATE_USER' => "Vous ne pouvez pas activer ou désactiver un utilisateur",
        'USER_NOT_VALIDATED_BY_ADMIN' => "Votre compte n'est pas encore validé par un administrateur",
        'GENERIC_ACCESS_DENIED' => "Vous n'avez pas le droit de faire cette action.",
    ];

    protected function supports(string $attribute, mixed $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return
            in_array($attribute, [self::READ, self::WRITE, self::ACTIVATE]) &&
            ($subject === null || $subject instanceof \App\Entity\User)
        ;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        // on checke si le mec est connecté
        /** @var User $currentUser */
        $currentUser = $token->getUser();
        if ($this->calculateErrors($attribute, $subject, $currentUser) === true) {
            return true;
        }
        return false;
    }

    public function calculateErrors(string $attribute, mixed $subject, ?UserInterface $currentUser): string|bool
    {
        if (!$currentUser instanceof UserInterface) {
            return $this->getErrorCode("NOT_CONNECTED");
        }

        // s'il est admin, on dit ok
        if ($currentUser->hasRole('ROLE_ADMIN')) {
            return true;
        }

        // seul un admin peut activer / désactiver un utilisateur
        if ($attribute === self::ACTIVATE) {
            return $this->getErrorCode("CAN_NOT_ACTIVATE_USER");
        }

        /** @var User $subjectUser */
        $subjectUser = $subject;

        // si il n'y a pas de sujet (juste un READ générique), ici ok dit ok
        // par exemple il a le droit de regarder le trombi.
        if (!$subjectUser instanceof UserInterface) {
            // S'il n'y a pas de sujet, c'est une page globale, il faut avoir été validé
            // par un admin
            if (!$currentUser->isValidatedByAdmin()) {
                return $this->getErrorCode("USER_NOT_VALIDATED_BY_ADMIN");
            }
            if ($attribute === self::READ) {
                return true;
            } elseif ($attribute === self::WRITE) {
                return $this->getErrorCode("GENERIC_ACCESS_DENIED");
            } else {
                return $this->getErrorCode("GENERIC_ACCESS_DENIED");
            }
        }

        ////
        // ici il y a un sujet
        ////

        // si le sujet est le même que l'utilisateur connecté, on dit ok (pour read et write)
        if ($subjectUser->getId() === $currentUser->getId()) {
            return true;
        }

        switch ($attribute) {
            case self::READ:
                if (!$subjectUser->isValidatedByAdmin()) {
                    return $this->getErrorCode("USER_NOT_VALIDATED_BY_ADMIN");
                }
                return true;

            case self::WRITE:
                return $this->getErrorCode("GENERIC_ACCESS_DENIED");
        }

        return $this->getErrorCode("GENERIC_ACCESS_DENIED");
    }

    public function getErrorMessage(string $errorCode): string
    {
        return self::ERROR_MESSAGES[$errorCode];
    }

    public function getErrorCode(string $errorCode): string
    {
        // check if the code exists in ERROR_MESSAGES
        if (!array_key_exists($errorCode, self::ERROR_MESSAGES)) {
            throw new \RuntimeException("Error code $errorCode does not exist");
        }
        return $errorCode;
    }
}
