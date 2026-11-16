
{include file="orderforms/standard_cart/common.tpl"}
<div class="products_list_section">
<div id="order-standard_cart">
    <div class="row">
        <div class="cart-sidebar sidebar">
            {include file="orderforms/standard_cart/sidebar-categories.tpl"}
        </div>
        <div class="cart-body">

            <div class="header-lined">
                <h1 class="font-size-36">
                    {if $productGroup.headline}
                        {$productGroup.headline}
                    {else}
                        {$productGroup.name}
                    {/if}
                </h1>
                {if $productGroup.tagline}
                    <p>{$productGroup.tagline}</p>
                {/if}
            </div>
            {if $errormessage}
                <div class="alert alert-danger">
                    {$errormessage}
                </div>
            {elseif !$productGroup}
                <div class="alert alert-info">
                    {lang key='orderForm.selectCategory'}
                </div>
            {/if}

            {include file="orderforms/standard_cart/sidebar-categories-collapsed.tpl"}
<div class="products" id="products">
    <div class="row row-eq-height">
        <div class="col-md-12 main_product_group">

        {foreach $products as $key => $product}
            {$idPrefix = ($product.bid) ? ("bundle"|cat:$product.bid) : ("product"|cat:$product.pid)}
            <div class="card product_box" style="max-width: 992px;" id="{$idPrefix}">
                <div class="row g-0">
                    <div class="col-md-2">
                        <img src="{$WEB_ROOT}/templates/aqua-sky-one/images/product-box.png" class="rounded-start" alt="...">
                    </div>
                    <div class="col-md-7">
                        <div class="card-body">
                            <h5 class="card-title">
                                <span id="{$idPrefix}-name">{$product.name}</span>
                                {if $product.stockControlEnabled}
                                    <span class="qty">{$product.qty} {$LANG.orderavailable}</span>
                                {/if}
                            </h5>
                            <p class="card-text product-pricing" id="{$idPrefix}-price">
                                {if $product.bid}
                                    {$LANG.bundledeal}<br />
                                    {if $product.displayprice}
                                        <span class="price">{$product.displayprice}</span>
                                    {/if}
                                {else}
                                    {if $product.pricing.hasconfigoptions}
                                        {$LANG.startingfrom}
                                    {/if}
                                    <span class="price">{$product.pricing.minprice.price}</span>
                                    {if $product.pricing.minprice.cycle eq "monthly"}
                                        {$LANG.orderpaymenttermmonthly}
                                    {elseif $product.pricing.minprice.cycle eq "quarterly"}
                                        {$LANG.orderpaymenttermquarterly}
                                    {elseif $product.pricing.minprice.cycle eq "semiannually"}
                                        {$LANG.orderpaymenttermsemiannually}
                                    {elseif $product.pricing.minprice.cycle eq "annually"}
                                        {$LANG.orderpaymenttermannually}
                                    {elseif $product.pricing.minprice.cycle eq "biennially"}
                                        {$LANG.orderpaymenttermbiennially}
                                    {elseif $product.pricing.minprice.cycle eq "triennially"}
                                        {$LANG.orderpaymenttermtriennially}
                                    {/if}
                                    <br>
                                    {if $product.pricing.minprice.setupFee}
                                        <small>{$product.pricing.minprice.setupFee->toPrefixed()} {$LANG.ordersetupfee}</small>
                                    {/if}
                                {/if}
                            </p>
                            <p class="card-text product_description">
                                {if $product.featuresdesc}
                                    <p class="product_description" id="{$idPrefix}-description">
                                        {$product.featuresdesc}
                                    </p>
                                {/if}
                                <ul>
                                    {foreach $product.features as $feature => $value}
                                        <li id="{$idPrefix}-feature{$value@iteration}">
                                            <span class="feature-value">{$value}</span> {$feature}
                                        </li>
                                    {/foreach}
                                </ul>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-3 order_button">
                        <a href="{$product.productUrl}" class="btn btn-success btn-sm btn-order-now" id="{$idPrefix}-order-button"{if $product.hasRecommendations} data-has-recommendations="1"{/if}>
                            <i class="fas fa-shopping-cart"></i> {$LANG.ordernowbutton}
                        </a>
                    </div>
                </div>
            </div>
        {/foreach}
        
        </div>
    </div>
</div>

                </div>
            </div>
        </div>
    </div>
</div>
</div>
{include file="orderforms/standard_cart/recommendations-modal.tpl"}

