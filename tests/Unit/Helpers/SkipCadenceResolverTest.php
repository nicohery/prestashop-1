<?php
/**
 * @author    Metrogeek SAS <support@ciklik.co>
 * @copyright Since 2017 Metrogeek SAS
 * @license   https://opensource.org/license/afl-3-0-php/ Academic Free License (AFL 3.0)
 */

declare(strict_types=1);

namespace PrestaShop\Module\Ciklik\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use PrestaShop\Module\Ciklik\Helpers\SkipCadenceResolver;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Teste SkipCadenceResolver::resolve sur ses trois branches de résolution :
 * cadence racine, mode fréquence (fingerprint), mode attributs (combinaison de contenu).
 */
class SkipCadenceResolverTest extends TestCase
{
    protected function setUp(): void
    {
        \Db::resetMocks();
        \Configuration::resetMocks();
    }

    /**
     * Branche 1 : la cadence portée au niveau racine du corps API est utilisée en priorité.
     */
    public function testResolvesFromRootBody()
    {
        $cadence = SkipCadenceResolver::resolve(
            ['interval' => 'month', 'interval_count' => 2],
            null,
            []
        );

        $this->assertSame(['interval' => 'month', 'interval_count' => 2], $cadence);
    }

    /**
     * Branche 1 : la cadence racine prime sur la fréquence du fingerprint (court-circuit,
     * la BDD n'est pas consultée).
     */
    public function testRootBodyTakesPrecedenceOverFingerprint()
    {
        \Configuration::updateValue(\Ciklik::CONFIG_USE_FREQUENCY_MODE, '1');
        \Db::setMockGetRow(['interval' => 'week', 'interval_count' => 9]);

        $cadence = SkipCadenceResolver::resolve(
            ['interval' => 'month', 'interval_count' => 1],
            5,
            []
        );

        $this->assertSame(['interval' => 'month', 'interval_count' => 1], $cadence);
    }

    /**
     * Une cadence racine invalide (interval_count à 0) est ignorée.
     */
    public function testRootBodyIgnoredWhenCountIsZero()
    {
        $cadence = SkipCadenceResolver::resolve(
            ['interval' => 'month', 'interval_count' => 0],
            null,
            []
        );

        $this->assertNull($cadence);
    }

    /**
     * Branche 2 : mode fréquence, cadence résolue depuis la fréquence du fingerprint.
     */
    public function testResolvesFromFingerprintFrequencyInFrequencyMode()
    {
        \Configuration::updateValue(\Ciklik::CONFIG_USE_FREQUENCY_MODE, '1');
        \Db::setMockGetRow(['id_frequency' => 5, 'interval' => 'month', 'interval_count' => 3]);

        $cadence = SkipCadenceResolver::resolve([], 5, []);

        $this->assertSame(['interval' => 'month', 'interval_count' => 3], $cadence);
    }

    /**
     * Branche 2 ignorée hors mode fréquence, même si un frequency_id est fourni.
     */
    public function testFingerprintFrequencyIgnoredWhenNotFrequencyMode()
    {
        \Db::setMockGetRow(['interval' => 'month', 'interval_count' => 3]);

        $cadence = SkipCadenceResolver::resolve([], 5, []);

        $this->assertNull($cadence);
    }

    /**
     * Branche 3 : mode attributs, cadence résolue depuis la combinaison (external_id) de la
     * première ligne de contenu.
     */
    public function testResolvesFromContentCombinationInAttributeMode()
    {
        \Db::setMockGetRow(['interval' => 'week', 'interval_count' => 4]);

        $cadence = SkipCadenceResolver::resolve(
            [],
            null,
            [['external_id' => '34', 'quantity' => 1]]
        );

        $this->assertSame(['interval' => 'week', 'interval_count' => 4], $cadence);
    }

    /**
     * Branche 3 : une ligne sans external_id exploitable est ignorée, et la résolution
     * passe à la ligne suivante.
     */
    public function testSkipsContentLinesWithoutUsableExternalId()
    {
        // 1er getRow (ligne valide '34') renvoie une fréquence sans interval => ignorée ;
        // 2e getRow (ligne '56') renvoie une fréquence valide.
        \Db::setMockGetRowResults([
            [],
            ['interval' => 'day', 'interval_count' => 10],
        ]);

        $cadence = SkipCadenceResolver::resolve(
            [],
            null,
            [
                ['external_id' => '0'],
                ['external_id' => '34'],
                ['external_id' => '56'],
            ]
        );

        $this->assertSame(['interval' => 'day', 'interval_count' => 10], $cadence);
    }

    /**
     * Aucune source exploitable : retourne null (le contrôleur affichera une erreur).
     */
    public function testReturnsNullWhenNothingResolvable()
    {
        $cadence = SkipCadenceResolver::resolve([], null, []);

        $this->assertNull($cadence);
    }
}
