{block name='frontend_index_header_css_screen' append}
    {foreach $elefundsCss as $css}
        <link type="text/css" media="all" rel="stylesheet" href="{link file="engine/Shopware/Plugins/Community/Frontend/LfndsDonation/SDK/$css" }" />
    {/foreach}
{/block}

{block name="frontend_index_header_javascript_jquery" append}
    {foreach $elefundsJs as $js}
        <script type="text/javascript" src="{link file="engine/Shopware/Plugins/Community/Frontend/LfndsDonation/SDK/$js"}"></script>
    {/foreach}
        <script type="text/javascript" src="{link file="engine/Shopware/Plugins/Community/Frontend/LfndsDonation/Views/elefunds.js" }"></script>
{/block}