{*
 * @author    UniversalPay
 * @copyright Copyright (c) 2018 UniversalPay
 * @license   http://opensource.org/licenses/LGPL-3.0  Open Software License (LGPL 3.0)
 *
*}
{extends file=$layout}

{block name='content'}
    <div class="box clearfix">
        <h2 style="margin: 30px 0">
            {l s='Order status' mod='universalpay'} {$orderPublicId} -
            {if $orderEvoStatus!='success'}
                <span style="color: red; font-weight: bold;">{$orderStatus}</span>
            {else}
                {$orderStatus}
            {/if} <br/>
        </h2>

        {$HOOK_ORDER_CONFIRMATION}
        {$HOOK_PAYMENT_RETURN}

        <a class="btn btn-primary float-xs-left" href="{$redirectUrl}">
        <span>
            {l s='Order details' mod='universalpay'}
            <i class="icon-chevron-right"></i>
        </span>
        </a>
        {if $orderEvoStatus!='success'}
            <a class="btn btn-primary float-xs-right continue" href="{url entity='module' name='evopayments' controller='payment' params = ['retry' => $orderId, 'retryToken' => $token]}">
            <span>
                {l s='Try to pay again' mod='universalpay'}
                <i class="icon-chevron-right"></i>
            </span>
            </a>
        {/if}

    </div>
{/block}