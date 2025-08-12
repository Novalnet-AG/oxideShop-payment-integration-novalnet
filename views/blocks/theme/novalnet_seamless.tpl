[{assign var="sGetActiveTheme" value=$oViewConf->getActiveTheme()}]
[{assign var="deliveryCost" value=$oxcmp_basket->getDeliveryCost()}]
[{assign var="sRedirectUrl" value=$oView->getNovalnetPayByLink($deliveryCost->getBruttoPrice())}]
[{assign var="orderDetails" value=$oViewConf->getOrderDetails($oxcmp_basket, $deliveryCost->getBruttoPrice())}]
[{assign var="time" value=$oViewConf->getTimeStamp()}]
[{if $sGetActiveTheme == 'flow'}]
    <div>
        <dl>
            <dt>
                <div style="display:none">
                  <input id="payment_[{$sPaymentID}]" type="radio" name="paymentid" value="[{$sPaymentID}]" [{if $oView->getCheckedPaymentId() == $paymentmethod->oxpayments__oxid->value}]checked[{/if}]>
                </div>
                <label id="[{$sPaymentID}]" for="payment_[{$sPaymentID}]" style="display:none"><b>[{$paymentmethod->oxpayments__oxdesc->value}]</b></label>
                [{if !empty($sRedirectUrl)}]
                    <input type="hidden" id="getShopUrl" value="[{$oViewConf->getShopUrl()}]"/>
                    <input type="hidden" id="orderDetails" value='[{$orderDetails}]' />
                    <input type="hidden" id="novalnet_payment_details" name="dynvalue[novalnet_payment_details]" />
                    <input type="hidden" id="novalnet_payment_error" name="dynvalue[novalnet_payment_error]" />
                    <iframe id="novalnetiframe" scrolling="no" width="100%" src ="[{$sRedirectUrl}]" frameBorder="0" allow="payment"></iframe>
                    <script type="text/javascript" src="https://cdn.novalnet.de/js/pv13/checkout.js?[{$time}]"></script>
                    [{oxscript include=$oViewConf->getModuleUrl('novalnet', 'out/src/js/novalnet_cookie.js')}]
                    [{oxscript include=$oViewConf->getModuleUrl('novalnet', 'out/src/js/novalnet_seamless.js')}]
                [{/if}]
            </dt>
            <dd></dd>
        </dl>
    </div>
[{else}]
    <dl>
        <dt>
            <div style="display:none">
                  <input id="payment_[{$sPaymentID}]" type="radio" name="paymentid" value="[{$sPaymentID}]" [{if $oView->getCheckedPaymentId() == $paymentmethod->oxpayments__oxid->value}]checked[{/if}]>
            </div>
            <label id="[{$sPaymentID}]" for="payment_[{$sPaymentID}]" style="display:none"><b>[{$paymentmethod->oxpayments__oxdesc->value}]</b></label>
            [{if !empty($sRedirectUrl)}]
                <input type="hidden" id="getShopUrl" value="[{$oViewConf->getShopUrl()}]">
                <input type="hidden" id="orderDetails" value='[{$orderDetails}]'>
                <input type="hidden" id="novalnet_payment_details" name="dynvalue[novalnet_payment_details]" />
                <input type="hidden" id="novalnet_payment_error" name="dynvalue[novalnet_payment_error]" />
                <iframe id="novalnetiframe" scrolling="no" width="100%" src ="[{$sRedirectUrl}]" frameBorder="0" allow="payment"></iframe>
                <script type="text/javascript" src="https://cdn.novalnet.de/js/pv13/checkout.js?[{$time}]"></script>
                [{oxscript include=$oViewConf->getModuleUrl('novalnet', 'out/src/js/novalnet_seamless.js')}]
            [{/if}]
        </dt>
        <dd></dd>
    </dl>
[{/if}]
