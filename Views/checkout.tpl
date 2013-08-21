{block name='frontend_index_header_css_screen' append}
    {$elefundsCss}
    {if $elefundsHasAdditionalCss}
        <link type="text/css" media="all" rel="stylesheet" href="{link file="$elefundsAdditionalCss"}" />
    {/if}
{/block}

{block name="frontend_index_header_javascript_jquery" append}
    {$elefundsJs}
{/block}