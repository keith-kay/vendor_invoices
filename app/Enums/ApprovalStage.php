<?php

namespace App\Enums;

enum ApprovalStage: string
{
    case Registration = 'registration';
    case Receiver = 'receiver';
    case CustomerService = 'customer_service';
    case Finance = 'finance';
    case Gbs = 'gbs';
    case Payment = 'payment';
}
