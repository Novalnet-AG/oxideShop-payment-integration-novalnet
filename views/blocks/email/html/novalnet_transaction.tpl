[{ if $payment->oxuserpayments__oxpaymentsid->value == 'novalnetpayments' }]
	[{assign var="sNovalnetComments" value=$oViewConf->getNovalnetOrderComments($order->oxorder__oxordernr->value)}]
    [{if !empty($sNovalnetComments)}]
    <b>[{ oxmultilang ident="NOVALNET_TRANSACTION_DETAILS" }]</b>
        [{$sNovalnetComments}]
    [{/if}]
    <br><br>
[{/if}]
[{ $smarty.block.parent }]
