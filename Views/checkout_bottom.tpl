{extends file='checkout.tpl'}

{block name="frontend_checkout_cart_footer_tax_information" prepend}
<div class="bottom_positioned_elefunds" style="margin-left: -2px">
    <div class="clear"></div>
    {$elefunds}
</div>
{/block}