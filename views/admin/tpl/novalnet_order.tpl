[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign}]
[{oxscript include="js/libs/jquery.min.js"}]
[{oxscript include=$oViewConf->getModuleUrl('novalnet','out/admin/src/js/novalnet_order.js')}]
[{oxscript add="jQuery.noConflict();" priority=10}]

<script type="text/javascript">
    window.onload = function () {
        top.oxid.admin.updateList('[{$sOxid}]')
    };
</script>

<style>
.extTab {
    padding: 5px; border: 1px solid #A9A9A9 !important;
}
.extTab td{
    border-bottom: 1px solid #ddd !important;
    padding: 4px 8px !important;
}
.extTab th{
    border-bottom: 1px solid #ddd !important;
    padding: 4px 8px !important;
}
body, td {
    font: 12px Open Sans, Tahoma, Verdana, Arial, Helvetica, sans-serif;
}
</style>

<form name="transfer" id="transfer" action="[{$oViewConf->getSelfLink()}]" method="post">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="oxid" value="[{$oxid}]">
    <input type="hidden" name="oxidCopy" value="[{$oxid}]">
    <input type="hidden" name="cl" value="delivery_main">
    <input type="hidden" name="language" value="[{$actlang}]">
</form>

[{if $dIsNovalnetPayment}]
[{if !empty($sNovalnetComments)}]
<table class="extTab" cellspacing="5" cellpadding="0" border="0" width="100%">
    <thead>
            <tr>
            [{if !empty($sNovalnetComments)}]
                <th><h3 style="border-bottom:none">[{ oxmultilang ident="NOVALNET_TRANSACTION_DETAILS" }]</h3></th>
            [{/if}]
            [{if $blOnHold}]
                <th><h3 style="border-bottom:none">[{ oxmultilang ident="NOVALNET_MANAGE_TRANSACTION_TITLE" }]</h3></th>
            [{elseif $blAmountRefund}]
                <th><h3 style="border-bottom:none" colspan="3">[{ oxmultilang ident="NOVALNET_REFUND_AMOUNT_TITLE" }]</h3></th>
            [{elseif $blZeroBook}]
                <th><h3 style="border-bottom:none">[{ oxmultilang ident="NOVALNET_BOOK_AMOUNT_TITLE" }]</h3></th>
            [{/if}]
            [{if !empty($aNovalnetInstalmentDetails)}]
                <th><h3 style="border-bottom:none">[{ oxmultilang ident="NOVALNET_INSTALMENT_HEADER" }]</h3></th>
            [{/if}]
            </tr>
    </thead>
    <tbody>
            <tr>
            [{if !empty($sNovalnetComments)}]
                <td>[{$sNovalnetComments}]</td>
            [{/if}]
            [{if $blOnHold}]
            <td>
                <form action="[{$oViewConf->getSelfLink()}]" method="post" onsubmit="return validateManageProcess();">
                    [{ $oViewConf->getHiddenSid() }]
                    <input type="hidden" name="cl" value="novalnet_order">
                    <input type="hidden" name="fnc" value="performNovalnetAction">
                    <input type="hidden" name="oxid" value="[{ $oxid }]">
                    <input type="hidden" name="novalnet[sRequestType]" value="sOnHold">
                    <input type="hidden" name="novalnet[iOrderNo]" value="[{$iOrderNo}]">
                    <table cellspacing="1" cellpadding="0">
                        <tbody>
                        [{if !empty($sOnHoldFailure) }]
                            <tr>
                                <td align="center" colspan="3">
                                    <p style="color:red; word-break:break-all;" colspan="2">[{ $sOnHoldFailure }]</p>
                                </td>
                            </tr>
                        [{/if}]
                        <tr>
                            <td align="center">
                                [{ oxmultilang ident="NOVALNET_MANAGE_TRANSACTION_LABEL" }]
                            </td>
                            <td align="center">
                                <select id="dNovalnetManageStatus" name="novalnet[sTransStatus]">
                                    <option value="" selected>[{ oxmultilang ident="NOVALNET_PLEASE_SELECT" }]</option>
                                    <option value="100">[{ oxmultilang ident="NOVALNET_CONFIRM" }]</option>
                                    <option value="103">[{ oxmultilang ident="NOVALNET_CANCEL" }]</option>
                                </select>
                                <input type="hidden" id="novalnet_invalid_status" value="[{oxmultilang ident='NOVALNET_INVALID_STATUS' }]">
                                <input type="hidden" id="sNovalnetConfirmCapture" value="[{oxmultilang ident='NOVALNET_CONFIRM_CAPTURE' }]">
                                <input type="hidden" id="sNovalnetConfirmCancel" value="[{oxmultilang ident='NOVALNET_CONFIRM_CANCEL' }]">
                            </td>
                        </tr>
                        <tr>
                            <td align="center" colspan="2">
                                <input class="extsubmit" type="submit" value="[{oxmultilang ident='NOVALNET_UPDATE'}]">
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </form>
             </td>
            [{/if}]
            [{if $blAmountRefund}]
            <td>
                <form action="[{$oViewConf->getSelfLink() }]" method="post" onsubmit="return validateRefundProcess();">
                    [{ $oViewConf->getHiddenSid() }]
                    <input type="hidden" name="cl" value="novalnet_order">
                    <input type="hidden" name="fnc" value="performNovalnetAction">
                    <input type="hidden" name="oxid" value="[{ $oxid }]">
                    <input type="hidden" name="novalnet[sRequestType]" value="sAmountRefund">
                    <input type="hidden" name="novalnet[iOrderNo]" value="[{$iOrderNo}]">
                    <table cellspacing="1" cellpadding="0" width="100%">
                        <tbody>
                        [{if !empty($sAmountRefundFailure) }]
                            <tr>
                                <td align="center" colspan="3">
                                    <p style="color:red; word-break:break-all;">[{ $sAmountRefundFailure }]</p>
                                </td>
                            </tr>
                        [{/if}]
                        <tr>
                            <td align="center">
                                [{ oxmultilang ident="NOVALNET_REFUND_AMOUNT_LABEL" }]
                            </td>
                            <td align="center">
                                <input type="text" size="15" id="novalnet_refund_amount" name="novalnet[sRefundAmount]" autocomplete="off" value="[{$dCreditedAmount}]" onkeypress="return isValidExtensionKey(event);">
                                <input type="hidden" id="novalnet_invalid_refund_amount" value="[{ oxmultilang ident='NOVALNET_INVALID_AMOUNT'}]" >
                                <input type="hidden" id="novalnet_confirm_refund" value="[{oxmultilang ident='NOVALNET_CONFIRM_REFUND'}]">
                            </td>
                            <td align="center">
                                [{ oxmultilang ident="NOVALNET_CENTS" }]
                            </td>
                        </tr>
					   <tr>
							<td align="center">
								[{ oxmultilang ident="NOVALNET_REFUND_REFERENCE_LABEL" }]
							</td>
							<td align="center">
								<input type="text" size="15" id="novalnet_refund_reason" name="novalnet[sRefundReason]">
							</td>
							<td></td>
						</tr>
                        <tr>
                            <td align="center" colspan="3">
                                <input class="extsubmit" type="submit" value="[{oxmultilang ident='NOVALNET_UPDATE'}]">
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </form>
                </td>
            [{/if}]
            [{if $blZeroBook}]
            <td>
                <form action="[{$oViewConf->getSelfLink() }]" method="post" onsubmit="return validateBookProcess();">
                    [{ $oViewConf->getHiddenSid() }]
                    <input type="hidden" name="cl" value="novalnet_order">
                    <input type="hidden" name="fnc" value="performNovalnetAction">
                    <input type="hidden" name="oxid" value="[{ $oxid }]">
                    <input type="hidden" name="novalnet[sRequestType]" value="sZeroBook">
                    <input type="hidden" name="novalnet[iOrderNo]" value="[{$iOrderNo}]">

                    <table cellspacing="1" cellpadding="0" width="100%">
                        <tbody>
                        [{if !empty($sZeroBookFailure) }]
                            <tr>
                                <td align="center" colspan="3">
                                    <p style="color:red; word-break:break-all;">[{ $sZeroBookFailure }]</p>
                                </td>
                            </tr>
                        [{/if}]
                        <tr>
                            <td align="center">
                                [{ oxmultilang ident="NOVALNET_BOOK_AMOUNT_LABEL" }]
                            </td>
                            <td align="center">
                                <input type="text" size="15" id="novalnet_book_amount" name="novalnet[sBookAmount]" autocomplete="off" value="[{$dOrderAmount}]" onkeypress="return isValidExtensionKey(event);">
                                <input type="hidden" id="novalnet_invalid_amount" value="[{ oxmultilang ident='NOVALNET_INVALID_AMOUNT'}]" >
                                <input type="hidden" id="novalnet_confirm_book_amount" value="[{ oxmultilang ident='NOVALNET_CONFIRM_BOOKED'}]" >
                            </td>
                            <td align="center">
                                [{ oxmultilang ident="NOVALNET_CENTS" }]
                            </td>
                        </tr>
                        <tr>
                            <td align="center" colspan="3">
                                <input class="extsubmit" type="submit" id="novalnet_book" value="[{oxmultilang ident='NOVALNET_UPDATE'}]">
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </form>
            </td>
            [{/if}]
        [{if !empty($aNovalnetInstalmentDetails)}]
        [{assign var="oConfig" value=$oViewConf->getConfig()}]
        [{assign var="oSession" value=$oConfig->getSession()}]
        <td>
            <table>
                <thead>
                    <tr></tr>
                    <tr>
                        <th>[{ oxmultilang ident="NOVALNET_INSTALMENT_SNO" }]</th>
                        <th>[{ oxmultilang ident="NOVALNET_INSTALMENT_TID" }]</th>
                        <th>[{ oxmultilang ident="NOVALNET_INSTALMENT_AMOUNT" }]</th>
                        <th>[{ oxmultilang ident="NOVALNET_INSTALMENT_NEXT_CYCLE" }]</th>
                        <th>[{ oxmultilang ident="NOVALNET_INSTALMENT_STATUS" }]</th>
                        <th>[{ oxmultilang ident="NOVALNET_INSTALMENT_REFUND" }]</th>
                    </tr>
                </thead>
                <tbody>
                [{assign var=iCompleted value=0}]
                [{assign var=bAllCyclesExecuted value=0}]
                <input type ="hidden" id="order_id" value ="[{$iOrderNo}]">
                <input type="hidden" id="sToken" name="sToken" value="[{$oSession->getSessionChallengeToken()}]">
                <input type="hidden" id="getShopUrl" value="[{$oViewConf->getNovalnetShopUrl()}]">
                <input type="hidden" id="getSelfLink" value="[{$oViewConf->getSelfLink()}]">
                <input type ="hidden" id="parent_tid" value ="[{$aNovalnetInstalmentDetails.instalment1.tid}]">
                [{foreach from=$aNovalnetInstalmentDetails.cycles key=count item=cycle}]
                [{assign var="confVarName" value="instalment"|cat:$cycle}]
                    <tr>
                        <td>[{$cycle}]</td>
                        [{if !empty($aNovalnetInstalmentDetails.$confVarName.tid)}]
							[{assign var=bAllCyclesExecuted value=1 }]							
                            <td>[{$aNovalnetInstalmentDetails.$confVarName.tid}]</td>
                        [{else}]
							[{assign var=bAllCyclesExecuted value=0 }]
                            <td></td>
                        [{/if}]
                        <td>[{$aNovalnetInstalmentDetails.$confVarName.amount}]</td>
                        [{if !empty($aNovalnetInstalmentDetails.$confVarName.next_instalment_date)}]
                            <td>[{$aNovalnetInstalmentDetails.$confVarName.next_instalment_date}]</td>
                        [{else}]
                            <td></td>
                        [{/if}]
                        <td>[{ oxmultilang ident=$aNovalnetInstalmentDetails.$confVarName.status }]</td>
                        [{if $aNovalnetInstalmentDetails.$confVarName.status == 'NOVALNET_INSTALMENT_STATUS_COMPLETED'}]
                         [{assign var=iCompleted value=1}]
                        <td>
                            <button type="button" name ="refund" id="refund_action_[{$cycle}]" class="btn btn-primary btn-sm" onclick="refundProcessDetail([{$cycle}]);">[{ oxmultilang ident="NOVALNET_INSTALMENT_REFUND_ACTION" }]</button>
                            <div id ="refund_box_[{$cycle}]" style ="display:none;">
                                <table style ="border:none;">
                                    <tr>
                                        <td style="border-top:none;" class="refund_box">[{ oxmultilang ident="NOVALNET_INSTALMENT_REFUND_AMOUNT" }]</td>
                                        [{if !empty($aNovalnetInstalmentDetails.$confVarName.paid_amount) }]
											<td style="border-top:none;"><input type = "text" name ="nn_refund_amount_[{$cycle}]"  id ="nn_refund_amount_[{$cycle}]" value ="[{$aNovalnetInstalmentDetails.$confVarName.paid_amount}]" onkeypress="return isValidExtensionKey(event);"></td>
										[{else}]
											<td style="border-top:none;"><input type = "text" name ="nn_refund_amount_[{$cycle}]"  id ="nn_refund_amount_[{$cycle}]" value ="[{$aNovalnetInstalmentDetails.$confVarName.amount}]" onkeypress="return isValidExtensionKey(event);"></td>
										[{/if}]
                                    </tr>
                                    <tr>
                                        <td style="border-top:none;">[{ oxmultilang ident="NOVALNET_INSTALMENT_REFUND_REASON" }]</td>
                                        <td style="border-top:none;"><input type = "text" name ="nn_refund_reason_[{$cycle}]" id ="nn_refund_reason_[{$cycle}]"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" style="border-top:none;" style="text-align:right;">
                                            <input type ="hidden" id ="refund_tid_[{$cycle}]" value="[{$aNovalnetInstalmentDetails.$confVarName.tid}]">
                                            <input type="hidden" id="novalnet_confirm_refund" value="[{oxmultilang ident='NOVALNET_CONFIRM_REFUND'}]">
                                            <button class= "extsubmit" type="button" name ="refund_confirm" id ="refund_confirm" class="btn btn-primary btn-sm" onclick="refundProcessDetailConfirm([{$cycle}]);">[{ oxmultilang ident="NOVALNET_INSTALMENT_REFUND_CONFIRM" }]</button>
                                            <button class= "extsubmit" type="button" name ="refund_cancel" id="refund_cancel" class="btn btn-primary btn-sm" onclick="refundProcessDetailCancel([{$cycle}]);">[{ oxmultilang ident="NOVALNET_INSTALMENT_REFUND_CANCEL" }]</button>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                        [{else}]
                        <td></td>
                        [{/if}]
                    </tr>
                [{/foreach}]
                <tr>
                    <td style="border-top:none;"></td>
                    <td style="border-top:none;"></td>
                    <td style="border-top:none;"></td>
                    <td style="border-top:none;"></td>
                    [{if $bShowCancel == '1'}]
                    <td style="border-top:none;">
						[{ if  $iCompleted == 1 }]
								<button type="button" name ="all_instalment_cancel" id ="all_instalment_cancel" class="btn btn-primary btn-sm extsubmit" onclick ="return cancelAllInstalment([{$aNovalnetInstalmentDetails.instalment1.tid}]);" style="display:none;">[{ oxmultilang ident="NOVALNET_INSTALMENT_CANCEL_ALL_CYCLE" }]</button>
								<input type="hidden" id="cancel_all_instalment"  value='[{ oxmultilang ident="INSTALMENT_CANCEL_ALL_ALERT" }]'>
						[{/if}]
                    </td>
                    <td style="border-top:none;" style="text-align:right;">
						[{if $bHideAllCycleCancel != 1 }]
							[{if $bHideRemainingCancel != 1 || $iCompleted == 1 }]
								<button type="button" name ="instalment_cancel" id ="instalment_cancel" class="btn btn-primary btn-sm" onclick="instalmentCancelTypeShow();">[{ oxmultilang ident="NOVALNET_INSTALMENT_CANCEL }]</button>
							[{/if}]
						[{/if}]
                        [{if $bHideAllCycleCancel != 1}]
							[{if $bHideRemainingCancel != 1 }]
								[{ if $bAllCyclesExecuted != 1 }]
								<button type="button" name ="remaining_instalment_cancel" id ="remaining_instalment_cancel" class="btn btn-primary btn-sm extsubmit" onclick ="return cancelRemainingInstalment([{$aNovalnetInstalmentDetails.instalment1.tid}]);" style="display:none;" >[{ oxmultilang ident="NOVALNET_INSTALMENT_CANCEL_REMAINING_CYCLE" }]</button>
								<input type="hidden" id="cancel_remaining_instalment" value='[{ oxmultilang ident="INSTALMENT_REMAINING_ALL_ALERT" }]'>
								 [{/if}]
							[{/if}]
                        [{/if}]
                    </td>
                    [{else}]
                    <td style="border-top:none;"></td>
                    <td style="border-top:none;"></td>
                    [{/if}]
                </tr>
                </tbody>
            </table>
            </td>
        [{/if}]
        </tr>
    </tbody>
</table>
[{/if}]
[{else}]
   <div class="messagebox">[{ oxmultilang ident="NOVALNET_PAYMENT_NOT" }]</div>
[{/if}]

[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]
