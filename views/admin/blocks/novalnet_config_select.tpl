[{if $oModule->getInfo('id') === "novalnet"}]
    [{oxscript include="js/libs/jquery.min.js"}]
    [{oxscript include=$oViewConf->getModuleUrl('novalnet', 'out/admin/src/js/novalnet_config.js')}]
    [{assign var="oConfig" value=$oViewConf->getConfig()}]
    [{assign var="oSession" value=$oConfig->getSession()}]
    <input type="hidden" id="sToken" name="sToken" value="[{$oSession->getSessionChallengeToken()}]">
    <input type="hidden" id="sGetUrl" name="sGetUrl" value="[{$oViewConf->getNovalnetShopUrl()}]">
    <input type="hidden" id="sMandatoryError" value="[{ oxmultilang ident='NOVALNET_MANDATORY_ERROR'}]">
    <input type="hidden" id="sWebhookSuccess" value="[{ oxmultilang ident='NOVALNET_WEBHOOK_SUCCESS_TEXT'}]">
    [{if $var_group == 'novalnetGlobalSettings'  && $module_var  == 'sTariffId'}]
        <select class="select" name="confselects[sTariffId]" id="dNovalnetTariffId" [{$readonly}]>
            <option value="" [{if $confselects.$module_var == '' }]selected="selected"[{/if}]>[{ oxmultilang ident="NOVALNET_PLEASE_SELECT" }]</option>
        </select>
        <input type="hidden" id="novalnetSavedTariff" value="[{$confselects.$module_var}]" />
    [{/if}]
[{else}]
    [{* all other modules get default block content *}]
    [{$smarty.block.parent}]
[{/if}]
