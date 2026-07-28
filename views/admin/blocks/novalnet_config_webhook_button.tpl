[{$smarty.block.parent}]
[{if $oModule->getInfo('id') === "novalnet"}]
    <input type="button" class="confinput" name="configureWebhook" value="[{ oxmultilang ident='NOVALNET_WEBHOOK_BUTTON_TEXT'}]" title="[{ oxmultilang ident='NOVALNET_WEBHOOK_BUTTON_HELP_TEXT'}]" onClick="Javascript:document.module_configuration.fnc.value='setWebhookConfig'" [{$readonly}]>
[{/if}]
