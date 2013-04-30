{extends file='checkout.tpl'}

{block name='frontend_index_header_css_screen' append}
    <link type="text/css" media="all" rel="stylesheet" href="{link file="engine/Shopware/Plugins/Community/Frontend/LfndsDonation/Views/elefunds_bottom.css" }" />
{/block}

{block name="frontend_checkout_cart_footer_tax_information" prepend}
<div class="bottom_positioned_elefunds">
    <div class="clear"></div>
    {$elefunds}
</div>
{/block}
