{block name='frontend_index_header_css_screen' append}
    {foreach $elefundsCss as $css}
        <link type="text/css" media="all" rel="stylesheet" href="{link file="engine/Shopware/Plugins/Community/Frontend/LfndsDonation/SDK/$css" }" />
    {/foreach}
{/block}

{block name='frontend_checkout_finish_teaser' prepend}
    {$elefundsFacebookShare}
{/block}

{block name='frontend_checkout_cart_item_total_sum'}
<div class="grid_2 push_4">
	<div class="textright">
		<strong>
		    {if $sBasketItem.donation}
			    {$sBasketItem.amount|currency}
			{else}
			    {$sBasketItem.amount|currency}*
			{/if}
		</strong>
	</div>
</div>
{/block}

{block name='frontend_checkout_cart_item_details'}
<div class="basket_details">
	{* Article name *}
	{if $sBasketItem.modus == 0}
		<a class="title" href="{$sBasketItem.linkDetails}" title="{$sBasketItem.articlename|strip_tags}">
			{$sBasketItem.articlename|strip_tags|truncate:60}
		</a>
		<p class="ordernumber">
			{se name="CartItemInfoId"  namespace="frontend/checkout/cart_item"}{/se} {$sBasketItem.ordernumber}
		</p>
	{elseif $sBasketItem.modus == 999}
	    <a class="title" href="{$sBasketItem.linkDetails}" title="{$sBasketItem.articlename|strip_tags}" target="_blank">
			{$sBasketItem.articlename|strip_tags|truncate:60}
		</a>
		<p class="ordernumber">
			{$sBasketItem.receivers}
		</p>
	{else}
		{$sBasketItem.articlename}
	{/if}
	
	{block name='frontend_checkout_cart_item_details_inline'}{/block}
</div>
{/block}