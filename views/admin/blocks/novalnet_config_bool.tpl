[{if $oModule->getInfo('id') == "novalnet" }]
    <input type=hidden name="confbools[[{$module_var}]]" value=false>
    <input type=checkbox name="confbools[[{$module_var}]]" value=true  [{if ($confbools.$module_var)}]checked[{/if}] [{$readonly}]>
[{else}]
    [{* all other modules get default block content *}]
    [{$smarty.block.parent}]
[{/if}]
