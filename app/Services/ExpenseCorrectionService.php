<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Expense;
use App\Support\CashOnlyPayments;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Append-only corrections for source-linked expenses (payroll, purchase orders,
 * utility). Originals are never silently rewritten: a signed correction row is
 * appended and naturally nets out in ledger sums.
 */
class ExpenseCorrectionService
{
    /**
     * @return array{result: string, expense: ?Expense, effective: float}
     */
    public function correct(
        Expense $original,
        float $desiredTotal,
        string $reason,
        string $token,
        string $sourceType,
        string $sourceId
    ): array {
        $user = Auth::user();
        if (! $user || ! $user->tenant_id || (string) $user->tenant_id !== (string) $original->tenant_id) {
            throw new RuntimeException('Cross-tenant expense correction is not allowed.');
        }

        $desiredTotal = round($desiredTotal, 2);
        if ($desiredTotal < 0 || $desiredTotal > 9999999999.99) {
            throw new RuntimeException('Correction total is outside the supported amount range.');
        }

        $token = strtoupper(trim($token));
        if (! preg_match('/^[A-Z0-9]{8,64}$/', $token)) {
            $token = strtoupper(substr(bin2hex(random_bytes(8)), 0, 16));
        }
        $marker = '[correction-token:'.$token.']';

        return DB::transaction(function () use ($original, $desiredTotal, $reason, $marker, $sourceType, $sourceId): array {
            $locked = Expense::withoutGlobalScope('not_voided')
                ->whereKey($original->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->is_voided || $locked->corrects_expense_id !== null) {
                throw new RuntimeException('Only the original source-linked expense can be corrected.');
            }

            $existing = Expense::withoutGlobalScope('not_voided')
                ->where('corrects_expense_id', $locked->id)
                ->get();
            if ($existing->contains(fn (Expense $e) => str_contains((string) $e->notes, $marker))) {
                return ['result' => 'duplicate', 'expense' => null, 'effective' => $this->effective($locked)];
            }

            $effective = $this->effective($locked);
            $delta = round(($desiredTotal - $effective) * 100) / 100;
            if (abs($delta) < 0.005) {
                return ['result' => 'no_change', 'expense' => null, 'effective' => $effective];
            }

            $sequence = (int) $existing->max('correction_sequence') + 1;

            $correction = Expense::create([
                'tenant_id' => $locked->tenant_id,
                'category' => $locked->category,
                'title' => 'Correction — '.$locked->title,
                'amount' => $delta,
                'expense_date' => now()->toDateString(),
                'payee' => $locked->payee,
                'payment_method' => CashOnlyPayments::CASH,
                'notes' => $marker.' '.$sourceType.':'.$sourceId.' original='.$locked->id
                    .' before='.number_format($effective, 2).' after='.number_format($desiredTotal, 2)
                    .' reason='.$reason,
                'created_by_user_id' => Auth::id(),
                'corrects_expense_id' => $locked->id,
                'correction_sequence' => $sequence,
            ]);

            AuditLog::log(
                'EXPENSE_CORRECTED',
                sprintf('%s [%s] expense %s corrected from PKR %s to PKR %s (delta %s). Reason: %s',
                    $sourceType, $sourceId, $locked->id, number_format($effective, 2),
                    number_format($desiredTotal, 2), number_format($delta, 2), $reason),
                $locked->tenant_id,
                'Expense',
                $correction->id
            );

            return ['result' => 'corrected', 'expense' => $correction, 'effective' => $desiredTotal];
        });
    }

    private function effective(Expense $original): float
    {
        return round((float) $original->amount + (float) $original->corrections()->sum('amount'), 2);
    }
}
