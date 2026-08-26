<?php
/**
 * @author    Ciklik SAS <support@ciklik.co>
 * @copyright Since 2017 Metrogeek SAS
 * @license   https://opensource.org/license/afl-3-0-php/ Academic Free License (AFL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Enregistre le hook termsAndConditions et initialise la configuration
 * « Consentement à l'abonnement » pour les installations existantes.
 *
 * La case à cocher n'apparaît à l'étape paiement que si le panier contient
 * un abonnement. Activée par défaut, comme à l'installation, sans écraser
 * une valeur déjà posée.
 */
function upgrade_module_1_23_0($module)
{
    if (!$module->isRegisteredInHook('termsAndConditions') && !$module->registerHook('termsAndConditions')) {
        return false;
    }

    if (Configuration::hasKey(Ciklik::CONFIG_ENABLE_SUBSCRIPTION_CONSENT)) {
        return true;
    }

    return (bool) Configuration::updateGlobalValue(Ciklik::CONFIG_ENABLE_SUBSCRIPTION_CONSENT, '1');
}
