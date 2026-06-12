<?php
/**
 * @author    Metrogeek SAS <support@ciklik.co>
 * @copyright Since 2017 Metrogeek SAS
 * @license   https://opensource.org/license/afl-3-0-php/ Academic Free License (AFL 3.0)
 */

declare(strict_types=1);

namespace PrestaShop\Module\Ciklik\Helpers;

use PrestaShop\Module\Ciklik\Managers\CiklikFrequency;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Résout la cadence (interval/interval_count) d'un abonnement pour le report de livraison.
 */
class SkipCadenceResolver
{
    /**
     * Résout la cadence d'un abonnement, tous modes confondus.
     *
     * Ordre de résolution :
     * 1. cadence au niveau racine du corps API (présente notamment en mode fréquence) ;
     * 2. mode fréquence : fréquence portée par le fingerprint ;
     * 3. mode attributs : fréquence portée par la combinaison de la première ligne de
     *    contenu (external_id = id_product_attribute dans ce mode).
     *
     * @param array $body Corps brut de l'abonnement renvoyé par l'API
     * @param int|null $fingerprintFrequencyId ID de fréquence porté par le fingerprint (mode fréquence)
     * @param array $contents Lignes de contenu de l'abonnement
     *
     * @return array|null ['interval' => string, 'interval_count' => int] ou null si indéterminable
     */
    public static function resolve(array $body, $fingerprintFrequencyId, array $contents)
    {
        if (!empty($body['interval']) && isset($body['interval_count']) && (int) $body['interval_count'] > 0) {
            return ['interval' => $body['interval'], 'interval_count' => (int) $body['interval_count']];
        }

        if (\Configuration::get(\Ciklik::CONFIG_USE_FREQUENCY_MODE) && $fingerprintFrequencyId) {
            $frequency = CiklikFrequency::getFrequencyById((int) $fingerprintFrequencyId);
            if ($frequency && !empty($frequency['interval'])) {
                return ['interval' => $frequency['interval'], 'interval_count' => (int) $frequency['interval_count']];
            }
        }

        foreach ($contents as $content) {
            if (empty($content['external_id'])) {
                continue;
            }
            $idProductAttribute = (int) $content['external_id'];
            if ($idProductAttribute <= 0) {
                continue;
            }
            $frequency = CiklikFrequency::getByIdProductAttribute($idProductAttribute);
            if ($frequency && !empty($frequency['interval'])) {
                return ['interval' => $frequency['interval'], 'interval_count' => (int) $frequency['interval_count']];
            }
        }

        return null;
    }
}
