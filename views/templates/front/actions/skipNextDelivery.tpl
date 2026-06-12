{**
 * @author    Metrogeek SAS <support@ciklik.co>
 * @copyright Since 2017 Metrogeek SAS
 * @license   https://opensource.org/license/afl-3-0-php/ Academic Free License (AFL 3.0)
 *}
<form action="{$subcription_base_link|escape:'html':'UTF-8'}/{$subscription->uuid|escape:'html':'UTF-8'}/skip" method="POST" style="display:inline;">
    <input type="hidden" name="token" value="{$token|escape:'html':'UTF-8'}">
    <small><button type="submit" class="btn btn-link" style="padding:0;">{l s='Skip next delivery' mod='ciklik'}</button></small>
</form>
