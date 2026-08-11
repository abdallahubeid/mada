<?php

namespace App\Domain\Finance\Exceptions;

use RuntimeException;

/**
 * A payroll run was refused before any money was computed.
 *
 * Every guard fails loudly rather than degrading: a run built on unresolved
 * ledger days, unpriced contracts or mixed currencies would produce numbers
 * that look plausible and are wrong, which is the worst possible outcome for a
 * record the system is required to keep forever (NFR-10/11).
 */
class PayrollRunException extends RuntimeException
{
    public static function periodAlreadyHasLiveRun(string $period): self
    {
        return new self("A live payroll run already exists for period {$period} (BR-611).");
    }

    public static function invalidPeriod(string $period): self
    {
        return new self("Payroll period '{$period}' is not a valid YYYY-MM value.");
    }

    public static function noActiveContracts(string $period): self
    {
        return new self("No active employee contracts to pay for period {$period}.");
    }

    /**
     * @param  list<string>  $employeeNames
     */
    public static function contractsMissingPayRate(array $employeeNames): self
    {
        return new self(
            'Cannot open a payroll run while these contracts have no pay rate set: '
            .implode(', ', $employeeNames).' (BR-301a).'
        );
    }

    public static function ledgerNotReconciled(string $period, int $unresolvedDays): self
    {
        return new self(
            "Work Ledger for {$period} still holds {$unresolvedDays} unresolved workday row(s). "
            .'Reconcile the period before opening a run (BR-405).'
        );
    }

    /**
     * An EMPTY ledger is the dangerous case, not merely an incomplete one.
     *
     * The BR-405 guard counts `workday` sentinel rows, so zero rows passes it.
     * With no rows, periodScheduledDays is 0, PayslipCalculator falls back to
     * the full base rate and computes no absence deduction — quietly paying
     * every employee a full salary. Emptiness therefore needs its own guard.
     */
    public static function ledgerEmpty(string $period): self
    {
        return new self(
            "Work Ledger for {$period} is empty. Reconcile the period before opening a run — "
            .'an unreconciled period would pay every employee in full with no absence deductions.'
        );
    }

    /**
     * @param  list<string>  $currencies
     */
    public static function mixedCurrencies(array $currencies): self
    {
        return new self(
            'Active contracts span multiple pay currencies ('.implode(', ', $currencies).'). '
            .'A single run cannot mix currencies — each tenant operates in one currency (PROJECT_VISION.md §4).'
        );
    }
}
