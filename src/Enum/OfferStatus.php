<?php

namespace App\Enum;

enum OfferStatus: int
{
    case DRAFT = 1;
    case REJECTED = 2;
    case VERIFICATION = 3;
    case BLOCKED_BY_ADMIN = 4;
    case AUCTION_STARTED = 5;
    case AUCTION_FINISHED = 6;
    case QUALIFICATION = 7;
    case COMPLETED = 8;
    case CANCELED = 9;
}
