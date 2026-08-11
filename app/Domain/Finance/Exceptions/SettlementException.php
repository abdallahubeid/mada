<?php

namespace App\Domain\Finance\Exceptions;

use App\Domain\Finance\Enums\SettlementStatus;
use RuntimeException;

/**
 * An offboarding settlement operation was refused (BR-606).
 */
class SettlementException extends RuntimeException
{
    public static function alreadySettled(string $employeeName): self
    {
        return new self("{$employeeName} already has a live offboarding settlement.");
    }

    public static function noActiveContract(string $employeeName): self
    {
        return new self(
            "{$employeeName} has no active contract, so there is no pay basis to settle against."
        );
    }

    public static function contractHasNoPayRate(string $employeeName): self
    {
        return new self(
            "{$employeeName}'s contract has no pay rate set, so every settlement figure would compute to zero (BR-301a)."
        );
    }

    public static function notSubmittable(string $employeeName, SettlementStatus $status): self
    {
        return new self("Settlement for {$employeeName} is '{$status->value}' and cannot be submitted.");
    }

    public static function notAwaitingApproval(string $employeeName, SettlementStatus $status): self
    {
        return new self("Settlement for {$employeeName} is '{$status->value}', not awaiting approval.");
    }

    public static function authorCannotApprove(string $employeeName): self
    {
        return new self(
            "The user who prepared {$employeeName}'s settlement may not also approve it (BR-615)."
        );
    }

    public static function notApproved(string $employeeName, SettlementStatus $status): self
    {
        return new self(
            "Settlement for {$employeeName} is '{$status->value}'; only an approved settlement can be disbursed."
        );
    }

    public static function locked(string $employeeName): self
    {
        return new self("Settlement for {$employeeName} is approved or paid and can no longer be changed.");
    }
}
