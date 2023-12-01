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

    protected function supports(string $attribute, mixed $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, [self::READ, self::WRITE])
            && $subject instanceof \App\Entity\User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var User $subjectUser */
        $subjectUser = $subject;

        /** @var User $currentUser */
        $currentUser = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$currentUser instanceof UserInterface) {
            return false;
        }
        if ($subjectUser->getId() === $currentUser->getId()) {
            return true;
        }
        if ($currentUser->hasRole('ROLE_ADMIN')) {
            return true;
        }

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case self::READ:
                if (!$currentUser->hasAcceptedToBeVisible()) {
                    return false;
                }
                if (!$subjectUser->hasAcceptedToBeVisible()) {
                    return false;
                }
                return true;

            case self::WRITE:
                return false;
        }

        return false;
    }
}
