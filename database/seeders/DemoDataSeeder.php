<?php

namespace Database\Seeders;

use App\Enums\ApprovalDecision as D;
use App\Enums\ApprovalStage as S;
use App\Enums\ClaimStatus;
use App\Enums\InvoiceStatus as St;
use App\Models\Claim;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

/**
 * Development only: departments, vendors and ten invoices spread across the whole workflow,
 * each with allocations, a consistent history trail and (where relevant) a claim.
 */
class DemoDataSeeder extends Seeder
{
    /** How many forward workflow steps each status has been through. */
    private const STEPS = [
        'registered' => 0, 'pending_receiver' => 1, 'pending_cs' => 2, 'pending_finance' => 3,
        'pending_posting' => 4, 'ready_for_payment' => 5, 'paid' => 6, 'rejected' => 2,
    ];

    public function run(): void
    {
        $fallback = User::first();

        if (! $fallback) {
            $this->command?->error('Create a user first (php artisan make:filament-user).');

            return;
        }

        $who = fn (string $email): User => User::where('email', $email)->first() ?? $fallback;
        $registrar = $who('registrar@example.test');
        $receiver = $who('receiver@example.test');
        $cs = $who('cs@example.test');
        $finance = $who('finance@example.test');
        $gbs = $who('gbs@example.test');
        $claimsUser = $who('claims@example.test');

        $departments = [];
        foreach (['Operations', 'Warehouse', 'Customer Service', 'Finance'] as $name) {
            $department = Department::firstOrCreate(['name' => $name]);
            if (! $department->default_approver_id) {
                $department->update(['default_approver_id' => $receiver->id]);
            }
            $departments[$name] = $department;
        }

        $vendors = [];
        foreach ([
            ['Test Vendor Ltd', 'P000000001A', 'V001'],
            ['Sample Haulage Ltd', 'P051234567B', 'V002'],
            ['Sample Port Services', 'P052345678C', 'V003'],
            ['Sample Marine Supplies', 'P053456789D', 'V004'],
            ['Sample Customs Agents', 'P054567890E', 'V005'],
        ] as [$name, $pin, $code]) {
            $vendors[] = Vendor::firstOrCreate(['name' => $name], ['kra_pin' => $pin, 'vendor_code' => $code]);
        }

        // v = vendor index, days = how long ago it was registered, claim = [status, claimed, recovered, credit note, notes]
        $specs = [
            ['v' => 0, 'no' => 'INV-1001', 'status' => St::Registered, 'gross' => 100000, 'cur' => 'KES', 'days' => 1,
                'lines' => [['JOB-1001', 'Operations', 'Freight', 60000], ['JOB-1002', 'Warehouse', 'Handling', 40000]]],
            ['v' => 1, 'no' => 'INV-1002', 'status' => St::Registered, 'gross' => 45000, 'cur' => 'KES', 'days' => 6,
                'lines' => [['JOB-1003', 'Operations', 'Haulage', 45000]]],
            ['v' => 2, 'no' => 'INV-1003', 'status' => St::PendingReceiver, 'gross' => 250000, 'cur' => 'KES', 'days' => 3,
                'lines' => [['JOB-1004', 'Operations', 'Port charges', 150000], ['JOB-1005', 'Operations', 'Port charges', 100000]]],
            ['v' => 3, 'no' => 'INV-1004', 'status' => St::PendingCs, 'gross' => 87500, 'cur' => 'KES', 'days' => 5,
                'lines' => [['JOB-1006', 'Warehouse', 'Supplies', 87500]],
                'claim' => [ClaimStatus::Open, 12500, 0, null, 'Short-delivered items']],
            ['v' => 1, 'no' => 'INV-1005', 'status' => St::PendingFinance, 'gross' => 132000, 'cur' => 'KES', 'days' => 6,
                'lines' => [['JOB-1007', 'Operations', 'Haulage', 132000]]],
            ['v' => 4, 'no' => 'INV-1006', 'status' => St::PendingPosting, 'gross' => 64000, 'cur' => 'KES', 'days' => 7,
                'lines' => [['JOB-1008', 'Operations', 'Customs', 64000]],
                'claim' => [ClaimStatus::UnderInvestigation, 8000, 0, null, 'Duplicate handling fee']],
            ['v' => 2, 'no' => 'INV-1007', 'status' => St::ReadyForPayment, 'gross' => 310000, 'cur' => 'KES', 'days' => 9,
                'lines' => [['JOB-1009', 'Operations', 'Port charges', 200000], ['JOB-1010', 'Warehouse', 'Storage', 110000]],
                'claim' => [ClaimStatus::CreditNotePending, 25000, 0, null, 'Storage billed for extra days']],
            ['v' => 0, 'no' => 'INV-1008', 'status' => St::Paid, 'gross' => 58000, 'cur' => 'KES', 'days' => 14,
                'lines' => [['JOB-1011', 'Operations', 'Freight', 58000]],
                'claim' => [ClaimStatus::Closed, 6000, 6000, 'CN-0042', 'Overcharged fuel surcharge']],
            ['v' => 3, 'no' => 'INV-1009', 'status' => St::Rejected, 'gross' => 72000, 'cur' => 'KES', 'days' => 4,
                'lines' => [['JOB-1012', 'Warehouse', 'Supplies', 72000]]],
            ['v' => 4, 'no' => 'INV-1010', 'status' => St::Paid, 'gross' => 1500, 'cur' => 'USD', 'days' => 20,
                'lines' => [['JOB-1013', 'Operations', 'Customs', 1500]]],
        ];

        $channels = ['email', 'hard_copy', 'portal'];
        $created = 0;

        foreach ($specs as $n => $s) {
            $vendor = $vendors[$s['v']];

            if (Invoice::where('vendor_id', $vendor->id)->where('invoice_number', $s['no'])->exists()) {
                continue;
            }

            $status = $s['status'];
            $rejected = $status === St::Rejected;
            $steps = self::STEPS[$status->value];
            $num = preg_replace('/\D/', '', $s['no']);
            $registered = now()->subDays($s['days'])->startOfHour();
            $at = fn (int $i) => $registered->copy()->addHours(4 * $i);
            $withClaim = isset($s['claim']);

            $invoice = new Invoice([
                'vendor_id' => $vendor->id,
                'invoice_number' => $s['no'],
                'invoice_date' => $registered->copy()->subDays(2)->toDateString(),
                'currency' => $s['cur'],
                'gross_amount' => $s['gross'],
                'tax_amount' => $s['cur'] === 'KES' ? round($s['gross'] * 16 / 116, 2) : 0,
                'receipt_channel' => $channels[$n % 3],
                'status' => $status,
                'claim_required' => $withClaim,
                'posting_reference' => ! $rejected && $steps >= 5 ? "POST-{$num}" : null,
                'payment_reference' => ! $rejected && $steps >= 6 ? "PAY-{$num}" : null,
                'paid_at' => ! $rejected && $steps >= 6 ? $at(6) : null,
                'registered_by' => $registrar->id,
            ]);
            $invoice->forceFill(['created_at' => $registered, 'updated_at' => $at(max($steps, 1))])->save();

            $accrualConfirmed = ! $rejected && $steps >= 3;
            foreach ($s['lines'] as [$job, $department, $cost, $amount]) {
                $invoice->allocations()->create([
                    'department_id' => $departments[$department]->id,
                    'cargowise_job_ref' => $job,
                    'cost_type' => $cost,
                    'allocated_amount' => $amount,
                    'accrual_confirmed' => $accrualConfirmed,
                    'accrual_confirmed_by' => $accrualConfirmed ? $cs->id : null,
                    'accrual_confirmed_at' => $accrualConfirmed ? $at(3) : null,
                ]);
            }

            $path = [
                [S::Registration, D::Submitted, $registrar, St::PendingReceiver, null],
                [S::Receiver, $withClaim ? D::ApprovedWithClaim : D::Approved, $receiver, St::PendingCs, $withClaim ? $s['claim'][4] : null],
                [S::CustomerService, D::Confirmed, $cs, St::PendingFinance, null],
                [S::Finance, D::Approved, $finance, St::PendingPosting, null],
                [S::Gbs, D::Posted, $gbs, St::ReadyForPayment, "Posting ref: POST-{$num}"],
                [S::Payment, D::Paid, $finance, St::Paid, "Payment ref: PAY-{$num}"],
            ];

            if ($rejected) {
                $path = [$path[0], [S::Receiver, D::Rejected, $receiver, St::Rejected, 'Wrong job reference']];
            }

            $from = St::Registered;
            foreach (array_slice($path, 0, $steps) as $i => [$stage, $decision, $actor, $to, $comment]) {
                $invoice->approvals()->create([
                    'user_id' => $actor->id,
                    'stage' => $stage,
                    'decision' => $decision,
                    'from_status' => $from->value,
                    'to_status' => $to->value,
                    'comment' => $comment,
                    'decided_at' => $at($i + 1),
                ]);
                $from = $to;
            }

            if ($withClaim) {
                [$claimStatus, $claimed, $recovered, $creditNote, $notes] = $s['claim'];

                $claim = Claim::create([
                    'invoice_id' => $invoice->id,
                    'owner_id' => $claimStatus === ClaimStatus::Open ? $receiver->id : $claimsUser->id,
                    'status' => $claimStatus,
                    'claimed_amount' => $claimed,
                    'recovered_amount' => $recovered,
                    'credit_note_ref' => $creditNote,
                    'notes' => $notes,
                    'closed_at' => $claimStatus === ClaimStatus::Closed ? $at(7) : null,
                ]);
                $claim->forceFill(['created_at' => $at(2), 'updated_at' => $at(2)])->save();
            }

            $created++;
        }

        $this->command?->info("Demo data ready: {$created} new invoices (existing ones were skipped).");
    }
}