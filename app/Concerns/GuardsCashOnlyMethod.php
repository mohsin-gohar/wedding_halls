<?php

namespace App\Concerns;

use App\Support\CashOnlyPayments;
use Illuminate\Database\Eloquent\Model;

/**
 * Last line of defence for financial models.
 *
 * New rows can only carry CASH. Existing historical rows are left untouched:
 * updating an old row's notes stays allowed, but changing its method to any
 * non-cash label is rejected.
 */
trait GuardsCashOnlyMethod
{
    protected static function bootGuardsCashOnlyMethod(): void
    {
        static::creating(static function (Model $model): void {
            $field = $model->cashOnlyMethodField();

            $model->{$field} = $model->{$field} === null
                ? CashOnlyPayments::CASH
                : CashOnlyPayments::assert($model->{$field});
        });

        static::updating(static function (Model $model): void {
            $field = $model->cashOnlyMethodField();

            if (! $model->isDirty($field)) {
                return;
            }

            $model->{$field} = CashOnlyPayments::assert($model->{$field});
        });
    }

    abstract protected function cashOnlyMethodField(): string;
}
