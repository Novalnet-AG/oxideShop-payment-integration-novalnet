[{$smarty.block.parent}]
[{if !empty($aNovalnetToken) }]
<script src="[{$aNovalnetBarzahlensUrl}]"
            class="bz-checkout"
            data-token="[{$aNovalnetToken}]">
</script>

<style type="text/css">
 #bz-checkout-modal { position: fixed !important; }</style><br>
<button class="bz-checkout-btn">
[{ oxmultilang ident="NOVALNET_BARZAHLEN_BUTTON" }]
</button>
[{/if}]
