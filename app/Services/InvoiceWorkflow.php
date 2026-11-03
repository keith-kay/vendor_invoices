<?php

namespace App\Services;

use App\Enums\ApprovalDecision as D;
use App\Enums\ApprovalStage as S;
use App\Enums\ClaimStatus;
use App\Enums\InvoiceStatus as St;
use App\Models\Claim;
use App\Models\Invoice;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

/**
 * Every status change goes through here, so the guards and the audit trail
 * (invoice_approvals) can't be bypassed from a button.
 */
class InvoiceWorkflow
{
    public function submit(Invoice $invoice, User $user): void
    {
        $allocations = $invoice->allocations()->get();

        if ($allocations->isEmpty()) {
            throw new WorkflowException('Add at least one job allocation before submitting.');
        }
        if ($allocations->contains(fn ($a) => blank($a->cargowise_job_ref))) {
            throw new WorkflowException('Every allocation needs a CargoWise job reference.');
        }
        if (! $invoice->isBalanced()) {
            throw new WorkflowException(sprintf(
                'Allocations total %s but the invoice is %s.',
                number_format($invoice->allocatedTotal(), 2),
                number_format((float) $invoice->gross_amount, 2),
            ));
        }

        $this->move($invoice, [St::Registered, St::Matched, St::Allocated, St::Rejected],
            St::PendingReceiver, S::Registration, D::Submitted, $user);
    }

    public function receiverApprove(Invoice $invoice, User $user, bool $raiseClaim = false, ?string $comment = null, ?float $claimedAmount = null): void
    {
        DB::transaction(function () use ($invoice, $user, $raiseClaim, $comment, $claimedAmount) {
            $this->move($invoice, [St::PendingReceiver], St::PendingCs, S::Receiver,
                $raiseClaim ? D::ApprovedWithClaim : D::Approved, $user, $comment);

            if ($raiseClaim) {
                $invoice->update(['claim_required' => true]);
                Claim::create([
                    'invoice_id' => $invoice->id,
                    'owner_id' => $user->id,
                    'status' => ClaimStatus::Open,
                    'claimed_amount' => $claimedAmount ?? 0,
                    'notes' => $comment,
                ]);
            }
        });

        if ($raiseClaim) {
            $this->notifyClaimsTeam($invoice);
        }
    }

    public function confirmAccrual(Invoice $invoice, User $user): void
    {
        DB::transaction(function () use ($invoice, $user) {
            $this->move($invoice, [St::PendingCs], St::PendingFinance, S::CustomerService, D::Confirmed, $user);
            $invoice->allocations()->update([
                'accrual_confirmed' => true,
                'accrual_confirmed_by' => $user->id,
                'accrual_confirmed_at' => now(),
            ]);
        });
    }

    public function financeApprove(Invoice $invoice, User $user, ?string $comment = null): void
    {
        $this->move($invoice, [St::PendingFinance], St::PendingPosting, S::Finance, D::Approved, $user, $comment);
    }

    public function markPosted(Invoice $invoice, User $user, string $postingReference): void
    {
        DB::transaction(function () use ($invoice, $user, $postingReference) {
            $this->move($invoice, [St::PendingPosting], St::ReadyForPayment, S::Gbs, D::Posted, $user, "Posting ref: {$postingReference}");
            $invoice->update(['posting_reference' => $postingReference]);
        });
    }

    public function markPaid(Invoice $invoice, User $user, string $paymentReference): void
    {
        DB::transaction(function () use ($invoice, $user, $paymentReference) {
            $this->move($invoice, [St::ReadyForPayment], St::Paid, S::Payment, D::Paid, $user, "Payment ref: {$paymentReference}");
            $invoice->update(['payment_reference' => $paymentReference, 'paid_at' => now()]);
        });
    }

    /** Any approval stage can reject. A reason is mandatory. */
    public function reject(Invoice $invoice, User $user, string $comment): void
    {
        if (blank($comment)) {
            throw new WorkflowException('A reason is required to reject an invoice.');
        }

        $stage = match ($invoice->status) {
            St::PendingReceiver => S::Receiver,
            St::PendingCs => S::CustomerService,
            St::PendingFinance => S::Finance,
            St::PendingPosting => S::Gbs,
            default => throw new WorkflowException('This invoice is not awaiting a decision.'),
        };

        $this->move($invoice, [$invoice->status], St::Rejected, $stage, D::Rejected, $user, $comment);
    }

    private function move(Invoice $invoice, array $from, St $to, S $stage, D $decision, ?User $user, ?string $comment = null): void
    {
        if (! in_array($invoice->status, $from, true)) {
            throw new WorkflowException("Invoice {$invoice->invoice_number} is '{$invoice->status->getLabel()}' and cannot take this action.");
        }

        DB::transaction(function () use ($invoice, $to, $stage, $decision, $user, $comment) {
            $previous = $invoice->status;
            $invoice->update(['status' => $to]);
            $invoice->approvals()->create([
                'user_id' => $user?->id,
                'stage' => $stage,
                'decision' => $decision,
                'from_status' => $previous->value,
                'to_status' => $to->value,
                'comment' => $comment,
                'decided_at' => now(),
            ]);
        });
    }

    private function notifyClaimsTeam(Invoice $invoice): void
    {
        $recipients = User::role('claims')->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::make()
            ->title('New claim raised')
            ->body("Invoice {$invoice->invoice_number} was approved with a claim.")
            ->warning()
            ->sendToDatabase($recipients);
    }
}