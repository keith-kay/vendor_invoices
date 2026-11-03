<?php

namespace App\Filament\Resources\Invoices\Actions;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\InvoiceWorkflow;
use App\Services\WorkflowException;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

/**
 * Workflow buttons for invoices, gated by Shield custom permissions.
 * The rules themselves live in App\Services\InvoiceWorkflow.
 */
class InvoiceActions
{
    public static function all(): array
    {
        $pending = fn (Invoice $r): bool => in_array($r->status, [
            InvoiceStatus::PendingReceiver, InvoiceStatus::PendingCs,
            InvoiceStatus::PendingFinance, InvoiceStatus::PendingPosting,
        ], true);

        return [
            self::make('submit', 'Submit for approval', 'heroicon-o-paper-airplane', 'Submit:Invoice',
                fn (Invoice $r): bool => $r->status->isEditable(),
                fn (Invoice $r, array $d, InvoiceWorkflow $wf) => $wf->submit($r, auth()->user())),

            self::make('approve', 'Approve', 'heroicon-o-check', 'ReceiverApprove:Invoice',
                fn (Invoice $r): bool => $r->status === InvoiceStatus::PendingReceiver,
                fn (Invoice $r, array $d, InvoiceWorkflow $wf) => $wf->receiverApprove($r, auth()->user(), false, $d['comment'] ?? null),
                [Textarea::make('comment')]),

            self::make('approve_claim', 'Approve and raise claim', 'heroicon-o-exclamation-triangle', 'ReceiverApprove:Invoice',
                fn (Invoice $r): bool => $r->status === InvoiceStatus::PendingReceiver,
                fn (Invoice $r, array $d, InvoiceWorkflow $wf) => $wf->receiverApprove(
                    $r, auth()->user(), true, $d['comment'] ?? null,
                    filled($d['claimed_amount'] ?? null) ? (float) $d['claimed_amount'] : null,
                ),
                [
                    TextInput::make('claimed_amount')->numeric()->label('Amount in dispute'),
                    Textarea::make('comment')->label('What is the claim about?')->required(),
                ],
                'warning'),

            self::make('confirm_accrual', 'Confirm accruals updated', 'heroicon-o-clipboard-document-check', 'ConfirmAccrual:Invoice',
                fn (Invoice $r): bool => $r->status === InvoiceStatus::PendingCs,
                fn (Invoice $r, array $d, InvoiceWorkflow $wf) => $wf->confirmAccrual($r, auth()->user())),

            self::make('finance_approve', 'Finance approve', 'heroicon-o-banknotes', 'FinanceApprove:Invoice',
                fn (Invoice $r): bool => $r->status === InvoiceStatus::PendingFinance,
                fn (Invoice $r, array $d, InvoiceWorkflow $wf) => $wf->financeApprove($r, auth()->user())),

            self::make('post', 'Mark as posted', 'heroicon-o-arrow-up-tray', 'Post:Invoice',
                fn (Invoice $r): bool => $r->status === InvoiceStatus::PendingPosting,
                fn (Invoice $r, array $d, InvoiceWorkflow $wf) => $wf->markPosted($r, auth()->user(), $d['posting_reference']),
                [TextInput::make('posting_reference')->required()]),

            self::make('pay', 'Mark as paid', 'heroicon-o-check-badge', 'MarkPaid:Invoice',
                fn (Invoice $r): bool => $r->status === InvoiceStatus::ReadyForPayment,
                fn (Invoice $r, array $d, InvoiceWorkflow $wf) => $wf->markPaid($r, auth()->user(), $d['payment_reference']),
                [TextInput::make('payment_reference')->required()]),

            self::make('reject', 'Reject', 'heroicon-o-x-circle', 'Reject:Invoice',
                $pending,
                fn (Invoice $r, array $d, InvoiceWorkflow $wf) => $wf->reject($r, auth()->user(), $d['comment']),
                [Textarea::make('comment')->label('Reason')->required()],
                'danger'),
        ];
    }

    private static function make(string $name, string $label, string $icon, string $permission, Closure $visible, Closure $handler, array $fields = [], string $color = 'primary'): Action
    {
        $action = Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->visible(fn (Invoice $record): bool => $visible($record)
                && (auth()->user()?->can($permission) ?? false))
            ->action(function (Invoice $record, array $data) use ($handler, $label): void {
                try {
                    $handler($record, $data, app(InvoiceWorkflow::class));
                    Notification::make()->title($label . ': done')->success()->send();
                } catch (WorkflowException $e) {
                    Notification::make()->title('Not allowed')->body($e->getMessage())->danger()->send();
                }
            });

        return $fields !== [] ? $action->schema($fields) : $action->requiresConfirmation();
    }
}