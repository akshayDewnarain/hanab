<?php

namespace Domain\Auth\Enums;

enum UserInvitationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Expired = 'expired';
    case Revoked = 'revoked';
    case Resent = 'resent';
}
