<?php
/**
 * @author    Metrogeek SAS <support@ciklik.co>
 * @copyright Since 2017 Metrogeek SAS
 * @license   https://opensource.org/license/afl-3-0-php/ Academic Free License (AFL 3.0)
 */

declare(strict_types=1);

namespace PrestaShop\Module\Ciklik\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use PrestaShop\Module\Ciklik\Helpers\IntervalHelper;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Teste IntervalHelper::addIntervalToDate.
 *
 * Couvre la logique « mois sans débordement » (essentielle au report de livraison :
 * next_billing + 1 cycle), les autres intervalles, la préservation de l'heure et le
 * rejet des intervalles non supportés.
 */
class IntervalHelperTest extends TestCase
{
    /**
     * 31 janvier + 1 mois = 28 février (année non bissextile), pas le 3 mars.
     */
    public function testAddMonthNoOverflowToFebruaryNonLeap()
    {
        $date = new \DateTimeImmutable('2023-01-31');

        $result = IntervalHelper::addIntervalToDate($date, 'month', 1);

        $this->assertSame('2023-02-28', $result->format('Y-m-d'));
    }

    /**
     * 31 janvier + 1 mois = 29 février sur une année bissextile.
     */
    public function testAddMonthNoOverflowToFebruaryLeap()
    {
        $date = new \DateTimeImmutable('2024-01-31');

        $result = IntervalHelper::addIntervalToDate($date, 'month', 1);

        $this->assertSame('2024-02-29', $result->format('Y-m-d'));
    }

    /**
     * Cas nominal : ajout d'un mois sans débordement de fin de mois.
     */
    public function testAddMonthRegular()
    {
        $date = new \DateTimeImmutable('2024-03-15');

        $result = IntervalHelper::addIntervalToDate($date, 'month', 1);

        $this->assertSame('2024-04-15', $result->format('Y-m-d'));
    }

    /**
     * Ajout de plusieurs mois avec passage d'année.
     */
    public function testAddSeveralMonthsCrossingYear()
    {
        $date = new \DateTimeImmutable('2024-11-30');

        $result = IntervalHelper::addIntervalToDate($date, 'month', 3);

        $this->assertSame('2025-02-28', $result->format('Y-m-d'));
    }

    /**
     * L'heure est préservée lors d'un ajout de mois.
     */
    public function testAddMonthPreservesTime()
    {
        $date = new \DateTimeImmutable('2024-01-31 14:25:36');

        $result = IntervalHelper::addIntervalToDate($date, 'month', 1);

        $this->assertSame('2024-02-29 14:25:36', $result->format('Y-m-d H:i:s'));
    }

    /**
     * Intervalle year.
     */
    public function testAddYear()
    {
        $date = new \DateTimeImmutable('2024-06-10');

        $result = IntervalHelper::addIntervalToDate($date, 'year', 2);

        $this->assertSame('2026-06-10', $result->format('Y-m-d'));
    }

    /**
     * Intervalle week.
     */
    public function testAddWeek()
    {
        $date = new \DateTimeImmutable('2024-06-10');

        $result = IntervalHelper::addIntervalToDate($date, 'week', 2);

        $this->assertSame('2024-06-24', $result->format('Y-m-d'));
    }

    /**
     * Intervalle day.
     */
    public function testAddDay()
    {
        $date = new \DateTimeImmutable('2024-06-10');

        $result = IntervalHelper::addIntervalToDate($date, 'day', 5);

        $this->assertSame('2024-06-15', $result->format('Y-m-d'));
    }

    /**
     * La méthode est immuable : la date d'origine n'est pas modifiée.
     */
    public function testDoesNotMutateOriginalDate()
    {
        $date = new \DateTimeImmutable('2024-01-31');

        IntervalHelper::addIntervalToDate($date, 'month', 1);

        $this->assertSame('2024-01-31', $date->format('Y-m-d'));
    }

    /**
     * Un intervalle non supporté lève une InvalidArgumentException
     * (le contrôleur de skip s'appuie sur cette garde).
     */
    public function testUnsupportedIntervalThrows()
    {
        $this->expectException(\InvalidArgumentException::class);

        IntervalHelper::addIntervalToDate(new \DateTimeImmutable('2024-01-01'), 'fortnight', 1);
    }
}
