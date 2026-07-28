[{if $oView->getShopTheme() == 'flow'}]
    [{if count($oOrders) > 0}]
        [{assign var=oArticleList value=$oView->getOrderArticleList()}]
        <ol class="list-unstyled">
            [{foreach from=$oOrders item=order}]
				[{if not ($order->oxorder__oxordernr->value == 0 && $order->oxorder__oxpaymenttype->value == 'novalnetpayments')}]
					<li>
						<div class="panel panel-default">
							<div class="panel-heading">
								<div class="row">
									<div class="col-xs-3">
										<strong>[{oxmultilang ident="DD_ORDER_ORDERDATE"}]</strong>
										<span id="accOrderDate_[{$order->oxorder__oxordernr->value}]">[{$order->oxorder__oxorderdate->value|date_format:"%d.%m.%Y"}]</span>
										<span>[{$order->oxorder__oxorderdate->value|date_format:"%H:%M:%S"}]</span>
									</div>
									<div class="col-xs-3">
										<strong>[{oxmultilang ident="STATUS"}]</strong>
										<span id="accOrderStatus_[{$order->oxorder__oxordernr->value}]">
											[{if $order->oxorder__oxstorno->value}]
												<span class="note">[{oxmultilang ident="ORDER_IS_CANCELED"}]</span>
											[{elseif $order->oxorder__oxsenddate->value !="-"}]
												<span>[{oxmultilang ident="SHIPPED"}]</span>
											[{else}]
												<span class="note">[{oxmultilang ident="NOT_SHIPPED_YET"}]</span>
											[{/if}]
										</span>
									</div>
									<div class="col-xs-3">
										<strong>[{oxmultilang ident="ORDER_NUMBER"}]</strong>
										<span id="accOrderNo_[{$order->oxorder__oxordernr->value}]">[{$order->oxorder__oxordernr->value}]</span>
									</div>
									<div class="col-xs-3">
										<strong>[{oxmultilang ident="SHIPMENT_TO"}]</strong>
											<span id="accOrderName_[{$order->oxorder__oxordernr->value}]">
											[{if $order->oxorder__oxdellname->value}]
												[{$order->oxorder__oxdelfname->value}]
												[{$order->oxorder__oxdellname->value}]
											[{else}]
												[{$order->oxorder__oxbillfname->value}]
												[{$order->oxorder__oxbilllname->value}]
											[{/if}]
										</span>
										[{if $order->getShipmentTrackingUrl()}]
											&nbsp;|&nbsp;<strong>[{oxmultilang ident="TRACKING_ID"}]</strong>
											<span id="accOrderTrack_[{$order->oxorder__oxordernr->value}]">
												<a href="[{$order->getShipmentTrackingUrl()}]">[{oxmultilang ident="TRACK_SHIPMENT"}]</a>
											</span>
										[{/if}]
									</div>
								</div>
							</div>
							<div class="panel-body">
								<strong>[{oxmultilang ident="CART"}]</strong>
								<ol class="list-unstyled">
									[{foreach from=$order->getOrderArticles(true) item=orderitem name=testOrderItem}]
										[{assign var=sArticleId value=$orderitem->oxorderarticles__oxartid->value}]
										[{assign var=oArticle value=$oArticleList[$sArticleId]}]
										<li id="accOrderAmount_[{$order->oxorder__oxordernr->value}]_[{$smarty.foreach.testOrderItem.iteration}]">
											[{$orderitem->oxorderarticles__oxamount->value}] [{oxmultilang ident="QNT"}]
											[{if $oArticle->oxarticles__oxid->value && $oArticle->isVisible()}]
												<a id="accOrderLink_[{$order->oxorder__oxordernr->value}]_[{$smarty.foreach.testOrderItem.iteration}]" href="[{$oArticle->getLink()}]">
											[{/if}]
											[{$orderitem->oxorderarticles__oxtitle->value}] [{$orderitem->oxorderarticles__oxselvariant->value}] <span class="amount"></span>
											[{if $oArticle->oxarticles__oxid->value && $oArticle->isVisible()}]
												</a>
											[{/if}]
											[{foreach key=sVar from=$orderitem->getPersParams() item=aParam}]
												[{if $aParam}]
													<br />[{oxmultilang ident="DETAILS"}]: [{$aParam}]
												[{/if}]
											[{/foreach}]
											[{* Commented due to Trusted Shops precertification. Enable if needed *}]
											[{*
											[{oxhasrights ident="TOBASKET"}]
											[{if $oArticle->isBuyable()}]
											  [{if $oArticle->oxarticles__oxid->value}]
												<a id="accOrderToBasket_[{$order->oxorder__oxordernr->value}]_[{$smarty.foreach.testOrderItem.iteration}]" href="[{oxgetseourl ident=$oViewConf->getSelfLink()|cat:"cl=account_order" params="fnc=tobasket&amp;aid=`$oArticle->oxarticles__oxid->value`&amp;am=1"}]">[{oxmultilang ident="TO_CART"}]</a>
											  [{/if}]
											[{/if}]
											[{/oxhasrights}]
											*}]
										</li>
									[{/foreach}]
								</ol>
							</div>
							<!-- Novalnet code begins -->
							[{if preg_match("/novalnet/i", $order->oxorder__oxpaymenttype->value)}]
								<div class="panel-body">
									<strong>[{ oxmultilang ident="NOVALNET_PAYMENT_TYPE" suffix="COLON" }]</strong>
									<ol class="list-unstyled">
										<li id="accOrderNovalnetPaymentType_[{$order->oxorder__oxordernr->value}]">
											[{$oView->getNovalnetPayment($order->oxorder__oxordernr->value, $order->oxorder__oxpaymenttype->value)|html_entity_decode}]
										</li>
									</ol>
								</div>
								<div class="panel-body">
									<ol class="list-unstyled">
										<li id="accOrderNovalnetTransactionDetails_[{$order->oxorder__oxordernr->value}]">
										[{assign var="sNovalnetComments" value=$oView->getNovalnetOrderComments($order->oxorder__oxordernr->value)}]
											[{if !empty($sNovalnetComments)}]
											<b>[{ oxmultilang ident="NOVALNET_TRANSACTION_DETAILS" }]</b>
												[{$sNovalnetComments}]
											[{/if}]
										</li>
									</ol>
								</div>
								[{assign var="aNovalnetInstalmentDetails" value=$oView->getNovalnetInstalmentComments($order->oxorder__oxordernr->value)}]
								[{if !empty($aNovalnetInstalmentDetails)}]
								<div class ="panel-body">
									<table>
										<thead>
											<tr><h4 style="border-bottom:none; font-weight: bold;">[{ oxmultilang ident="NOVALNET_INSTALMENT_HEADER" }]</h4></tr>
											<tr style="border:1px solid #ddd;">
												<th style="padding: 4px 8px;">[{ oxmultilang ident="NOVALNET_INSTALMENT_SNO" }]</th>
												<th style="padding: 4px 8px;">[{ oxmultilang ident="NOVALNET_INSTALMENT_TID" }]</th>
												<th style="padding: 4px 8px;">[{ oxmultilang ident="NOVALNET_INSTALMENT_AMOUNT" }]</th>
												<th style="padding: 4px 8px;">[{ oxmultilang ident="NOVALNET_INSTALMENT_NEXT_CYCLE" }]</th>
												<th style="padding: 4px 8px;">[{ oxmultilang ident="NOVALNET_INSTALMENT_STATUS" }]</th>
											</tr>
										</thead>
										<tbody>
											[{foreach from=$aNovalnetInstalmentDetails key=count item=data}]
												<tr style="border:1px solid #ddd;">
													<td style="padding: 4px 8px;">[{$count}]</td>
													[{if !empty($data.tid)}]
														<td style="padding: 4px 8px;">[{$data.tid}]</td>
													[{else}]
														<td></td>
													[{/if}]
													<td style="padding: 4px 8px;">[{$data.amount}]</td>
													[{if !empty($data.next_instalment_date)}]
														<td style="padding: 4px 8px;">[{$data.next_instalment_date}]</td>
													[{else}]
														<td></td>
													[{/if}]
													<td style="padding: 4px 8px;">[{ oxmultilang ident=$data.status }]</td>
												</tr>
											[{/foreach}]
										</tbody>
									</table>
								</div>
								[{/if}]
							[{/if}]
							<!-- Novalnet code ends -->
						</div>
					</li>
				[{/if}]
            [{/foreach}]
        </ol>
        [{include file="widget/locator/listlocator.tpl" locator=$oView->getPageNavigation() place="bottom"}]
    [{else}]
        [{oxmultilang ident="ORDER_EMPTY_HISTORY"}]
    [{/if}]
[{else}]
    [{if count($oOrders) > 0}]
        [{assign var=oArticleList value=$oView->getOrderArticleList()}]
        <ul class="orderList">
            [{foreach from=$oOrders item=order}]
				[{if not ($order->oxorder__oxordernr->value == 0 && $order->oxorder__oxpaymenttype->value == 'novalnetpayments')}]
					<li>
						<table class="orderitems">
							<tr>
								<td>
									<dl>
										<dt title="[{oxmultilang ident="ORDER_DATE" suffix="COLON"}]">
											<strong id="accOrderDate_[{$order->oxorder__oxordernr->value}]">[{ $order->oxorder__oxorderdate->value|date_format:"%d.%m.%Y" }]</strong>
											<span>[{ $order->oxorder__oxorderdate->value|date_format:"%H:%M:%S" }]</span>
										</dt>
										<dd>
											<strong>[{ oxmultilang ident="STATUS" suffix="COLON" }]</strong>
											<span id="accOrderStatus_[{$order->oxorder__oxordernr->value}]">
												[{if $order->oxorder__oxstorno->value}]
													<span class="note">[{ oxmultilang ident="ORDER_IS_CANCELED" }]</span>
												[{elseif $order->oxorder__oxsenddate->value !="-" }]
													<span>[{ oxmultilang ident="SHIPPED" }]</span>
												[{else}]
													<span class="note">[{ oxmultilang ident="NOT_SHIPPED_YET" }]</span>
												[{/if}]
											</span>
										</dd>
										<dd>
											<strong>[{ oxmultilang ident="ORDER_NUMBER" suffix="COLON" }]</strong>
											<span id="accOrderNo_[{$order->oxorder__oxordernr->value}]">[{ $order->oxorder__oxordernr->value }]</span>
										</dd>
										[{if $order->getShipmentTrackingUrl()}]
											<dd>
												<strong>[{ oxmultilang ident="TRACKING_ID" suffix="COLON" }]</strong>
												<span id="accOrderTrack_[{$order->oxorder__oxordernr->value}]">
													<a href="[{$order->getShipmentTrackingUrl()}]">[{ oxmultilang ident="TRACK_SHIPMENT" }]</a>
												</span>
											</dd>
										[{/if}]
										<dd>
											<strong>[{ oxmultilang ident="SHIPMENT_TO" suffix="COLON" }]</strong>
											<span id="accOrderName_[{$order->oxorder__oxordernr->value}]">
											[{if $order->oxorder__oxdellname->value }]
												[{ $order->oxorder__oxdelfname->value }]
												[{ $order->oxorder__oxdellname->value }]
											[{else }]
												[{ $order->oxorder__oxbillfname->value }]
												[{ $order->oxorder__oxbilllname->value }]
											[{/if}]
											</span>
										</dd>
									</dl>
								</td>
								<td>
									<h3>[{ oxmultilang ident="CART" suffix="COLON" }]</h3>
									<table class="orderhistory">
										[{foreach from=$order->getOrderArticles(true) item=orderitem name=testOrderItem}]
											[{assign var=sArticleId value=$orderitem->oxorderarticles__oxartid->value }]
											[{assign var=oArticle value=$oArticleList[$sArticleId] }]
											<tr id="accOrderAmount_[{$order->oxorder__oxordernr->value}]_[{$smarty.foreach.testOrderItem.iteration}]">
											  <td>
												[{if $oArticle->oxarticles__oxid->value && $oArticle->isVisible() }]
													<a  id="accOrderLink_[{$order->oxorder__oxordernr->value}]_[{$smarty.foreach.testOrderItem.iteration}]" href="[{$oArticle->getLink()}]">
												[{/if}]
													[{ $orderitem->oxorderarticles__oxtitle->value }] [{ $orderitem->oxorderarticles__oxselvariant->value }] <span class="amount"> - [{ $orderitem->oxorderarticles__oxamount->value }] [{oxmultilang ident="QNT"}]</span>
												[{if $oArticle->oxarticles__oxid->value && $oArticle->isVisible() }]</a>[{/if}]
												[{foreach key=sVar from=$orderitem->getPersParams() item=aParam}]
													[{if $aParam }]
													<br />[{ oxmultilang ident="DETAILS" suffix="COLON" }] [{$aParam}]
													[{/if}]
												[{/foreach}]
											  </td>
											  <td class="small">
												[{* Commented due to Trusted Shops precertification. Enable if needed *}]
												[{*
												[{oxhasrights ident="TOBASKET"}]
												[{if $oArticle->oxarticles__oxid->value && $oArticle->isBuyable() }]
													<a id="accOrderToBasket_[{$order->oxorder__oxordernr->value}]_[{$smarty.foreach.testOrderItem.iteration}]" href="[{ oxgetseourl ident=$oViewConf->getSelfLink()|cat:"cl=account_order" params="fnc=tobasket&amp;aid=`$oArticle->oxarticles__oxid->value`&amp;am=1" }]" rel="nofollow">[{ oxmultilang ident="TO_CART" }]</a>
												[{/if}]
												[{/oxhasrights}]
												*}]
											  </td>
											</tr>

										[{/foreach}]
									</table>
							  </td>
							</tr>
							<!-- Novalnet code begins -->
							[{if preg_match("/novalnet/i", $order->oxorder__oxpaymenttype->value)}]
								<tr>
									<td colspan="2">
										<strong>[{ oxmultilang ident="NOVALNET_PAYMENT_TYPE" suffix="COLON" }]</strong>
										<span id="accOrderNovalnetPaymentType_[{$order->oxorder__oxordernr->value}]">[{$oView->getNovalnetPayment($order->oxorder__oxordernr->value, $order->oxorder__oxpaymenttype->value)|html_entity_decode}]</span>
									</td>
								</tr>
								<tr>
									<td colspan="2">
										<span id="accOrderNovalnetTransactionDetails_[{$order->oxorder__oxordernr->value}]">
											[{assign var="sNovalnetComments" value=$oView->getNovalnetOrderComments($order->oxorder__oxordernr->value)}]
											[{if !empty($sNovalnetComments)}]
											<b>[{ oxmultilang ident="NOVALNET_TRANSACTION_DETAILS" }]</b>
											 [{$sNovalnetComments}]
											[{/if}]
										</span>
									</td>
								</tr>
								<tr>
									<td colspan="2">
									[{assign var="aNovalnetInstalmentDetails" value=$oView->getNovalnetInstalmentComments($order->oxorder__oxordernr->value)}]
									[{if !empty($aNovalnetInstalmentDetails)}]
										<table>
											<thead>
												<tr><h4 style="border-bottom:none; font-weight: bold;">[{ oxmultilang ident="NOVALNET_INSTALMENT_HEADER" }]</h4></tr>
												<tr style="border:1px solid #ddd;">
													<th style="padding: 4px 8px;">[{ oxmultilang ident="NOVALNET_INSTALMENT_SNO" }]</th>
													<th style="padding: 4px 8px;">[{ oxmultilang ident="NOVALNET_INSTALMENT_TID" }]</th>
													<th style="padding: 4px 8px;">[{ oxmultilang ident="NOVALNET_INSTALMENT_AMOUNT" }]</th>
													<th style="padding: 4px 8px;">[{ oxmultilang ident="NOVALNET_INSTALMENT_NEXT_CYCLE" }]</th>
													<th style="padding: 4px 8px;">[{ oxmultilang ident="NOVALNET_INSTALMENT_STATUS" }]</th>
												</tr>
											</thead>
											<tbody>
												[{foreach from=$aNovalnetInstalmentDetails key=count item=data}]
													<tr style="border:1px solid #ddd;">
														<td style="padding: 4px 8px;">[{$count}]</td>
														[{if !empty($data.tid)}]
															<td style="padding: 4px 8px;">[{$data.tid}]</td>
														[{else}]
															<td></td>
														[{/if}]
														<td style="padding: 4px 8px;">[{$data.amount}]</td>
														[{if !empty($data.next_instalment_date)}]
															<td style="padding: 4px 8px;">[{$data.next_instalment_date}]</td>
														[{else}]
															<td></td>
														[{/if}]
														<td style="padding: 4px 8px;">[{ oxmultilang ident=$data.status }]</td>
													</tr>
												[{/foreach}]
											</tbody>
										</table>
									[{/if}]
									</td>
								</tr>
							[{/if}]
							<!-- Novalnet code ends -->
						</table>
					</li>
				[{/if}]
            [{/foreach}]
        </ul>
        [{include file="widget/locator/listlocator.tpl" locator=$oView->getPageNavigation() place="bottom"}]
    [{else}]
        [{ oxmultilang ident="ORDER_EMPTY_HISTORY" }]
    [{/if}]
[{/if}]
