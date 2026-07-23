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
 * Initialise la configuration « Sauter la prochaine livraison » pour les
 * installations existantes (désactivée par défaut, comme à l'installation).
 *
 * La fonctionnalité (report de la prochaine facturation d'un cycle depuis la
 * page Mes abonnements) initialise sa clé via installConfiguration() pour les
 * nouvelles installations ; ce script fait de même pour les boutiques qui
 * migrent, sans écraser une valeur déjà posée.
 */
function upgrade_module_1_22_0($module)
{
    if (Configuration::hasKey(Ciklik::CONFIG_ENABLE_SKIP_NEXT_DELIVERY)) {
        return true;
    }

    return (bool) Configuration::updateGlobalValue(Ciklik::CONFIG_ENABLE_SKIP_NEXT_DELIVERY, '0');
}
