<?php

namespace App\Enums;

enum ApprovalDecision: string
{
    case Submitted = 'submitted';
    case Approved = 'approved';
    case ApprovedWithClaim = 'approved_with_claim';
    case Rejected = 'rejected';
    case Confirmed = 'confirmed';
    case Posted = 'posted';
    case Paid = 'paid';
}
