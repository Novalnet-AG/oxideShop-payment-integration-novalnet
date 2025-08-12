[{if $oModule->getInfo('id') === "novalnet" }]
    <input type=text class="txt" style="width: 250px;" id="[{$module_var}]" name="confstrs[[{$module_var}]]" value="[{$confstrs.$module_var}]" [{$readonly}]>
[{else}]
    [{* all other modules get default block content *}]
    [{$smarty.block.parent}]
[{/if}]
