</div>
</div>
</div>
</section>

{if $homepage_banner_image != ""} 
        <div class="section_main_homepage container-fluid">
          <div class="row" style="background: none !important;">
            <div class="container">
              <div class="col-md-8 col content_col" style="float: left; padding-bottom: 0px; padding-top: 80px;">
                {if $homepage_banner_heading != ""}
                <h5 class="primary-color" style="">{$homepage_banner_heading|unescape:'html'}</h5>
                {else}
                <h5 class="primary-color" style=""><span style="font-weight:700;">
                  {$lang.homepage.ClientPortalControlAccessYourServicesSeamlessly}
                </h5>
                {/if}
                {if $homepage_banner_description != ""}
                <p>{$homepage_banner_description|unescape:'html'}</p>
                {else}
                <p>
                {$lang.homepage.WelcometoWHMCSYouhavemadeagreatchoiceandwewanttohelpyougetupandrunningasquicklyaspossible}
              </p>
                {/if}
                {if $homepage_banner_button_text != ""}
                <div class="text-{$homepage_banner_position}">
                <a href="{$homepage_banner_button_link}" target="_blank" rel="noopener">
                <button type="button" class="btn btn-primary btn-lg">{$homepage_banner_button_text}</button></a>
                </div>
                {else}
                <div class="text-{$homepage_banner_position}">
                <a href="#">
                <button type="button" class="btn btn-primary btn-lg">
                {$lang.homepage.getStarted}
                </button></a> </div>
                {/if}   
              </div>
            <div class="col-md-4 content_col" style=" display: flex;
                          justify-content: center;
                          align-items: center;
                          position: relative; 
                          float: left; padding-top: 80px;">
                      <img src="{$WEB_ROOT}/templates/aqua-sky-one/images/{$homepage_banner_image}" class="img-fluid" alt="Banner Image">
                  </div>
          </div>
          {else}
        <div class="section_main_homepage container-fluid">

            <div class="row">
            <div class="container">
              <div class="col-md-7 col-sm-12 col content_col">
                {if $homepage_banner_heading != ""}
                <h5 class="primary-color">{$homepage_banner_heading|unescape:'html'}</h5>
                {else}
                <h5 class="primary-color"><span style="font-weight:700;">
                  {$lang.homepage.ClientPortalControlAccessYourServicesSeamlessly}
                </h5>
                {/if}
                {if $homepage_banner_description != ""}
                <p>{$homepage_banner_description|unescape:'html'}</p>
                {else}
                <p>
                {$lang.homepage.WelcometoWHMCSYouhavemadeagreatchoiceandwewanttohelpyougetupandrunningasquicklyaspossible}
              </p>
                {/if}
                {if $homepage_banner_button_text != ""}
                <div class="text-{$homepage_banner_position}">
                <a href="{$homepage_banner_button_link}" target="_blank" rel="noopener">
                <button type="button" class="btn btn-primary btn-lg">{$homepage_banner_button_text}</button></a>
                </div>
                {else}
                <div class="text-{$homepage_banner_position}">
                <a href="#">
                <button type="button" class="btn btn-primary btn-lg">
                {$lang.homepage.getStarted}
                </button></a> </div>
                {/if}   
              </div>
              <div class="col-md-5"></div>
        </div>
{/if}
    </div>
  </div>

{if !empty($productGroups) || $registerdomainenabled || $transferdomainenabled}
<div class="container">
  <div class="product_service_section">
    <div class="container">
      <p class="text-center">{$lang.homepage.ourproductsservices}</p>
      <h2 class="text-center m-4">{lang key='clientHomePanels.productsAndServices'}</h2>
      {assign var=cardIndex value=0}
      <div class="card-columns home">
        {foreach $productGroups as $productGroup}
        {if $cardIndex < 3} <div class="card mb-3">
          <div class="card-body p-lg-4 p-xl-5 text-center">
            <img src="{$WEB_ROOT}/templates/aqua-sky-one/images/product.png" class="float-start" alt="...">
            <h3 class="card-title pricing-card-title">
              {$productGroup->name}
            </h3>
            <p class="product-description">{$productGroup->tagline}</p>
            {* <a href="{$productGroup->getRoutePath()}" class="btn  btn-outline-primary">
              {lang key='browseProducts'}
            </a> *}
            <div class="d-grid gap-2 mx-auto browseProducts-btn">
              <a href="{$productGroup->getRoutePath()}"> <button class="btn btn-primary" type="button"> {lang
                  key='browseProducts'}</button> </a>

            </div>
          </div>
      </div>
      {assign var="cardIndex" value=$cardIndex+1}
      {/if}
      {/foreach}

      {if $registerdomainenabled}
      <div class="card mb-3">
        <div class="card-body p-lg-4 p-xl-5 text-center">
          <img src="{$WEB_ROOT}/templates/aqua-sky-one/images/product.png" class="float-start" alt="...">
          <h3 class="card-title pricing-card-title">
            {lang key='orderregisterdomain'}
          </h3>
          <p class="product-description">{lang key='secureYourDomain'}</p>

          <div class="d-grid gap-2 mx-auto browseProducts-btn">
            <a href="{$WEB_ROOT}/cart.php?a=add&domain=register"> <button class="btn btn-primary" type="button"> {lang
                key='navdomainsearch'}</button> </a>

          </div>
        </div>
      </div>
      {/if}


    </div>
    <div class="d-grid gap-2 mx-auto explore-btn">
    
      <a href="{$WEB_ROOT}/index.php?rp=/store/"> <button class="btn btn-primary" type="button">{$lang.homepage.exploremore}</button> </a>

    </div>
  </div>
</div>
</div>
{/if}
<div class="howcanhelp_section">
  <div class="container">
    <p class="text-center">
    {$lang.homepage.ourSupport}
    </p>
    <h2 class="text-center m-4">{lang key='howCanWeHelp'}</h2>

    <div class="container text-center">
      <div class="row g-2">
        <div class="col-4">
          <div class="p-2">
           <a href="{routePath('announcement-index')}" class="account d-flex">
              <img src="{$WEB_ROOT}/templates/aqua-sky-one/images/annoucment.png" class="float-start" alt="...">
              <h3 class="card-title pricing-card-title">
                {lang key='announcementstitle'}
              </h3>
            </a>
          </div>
        </div>
        <div class="col-4">
          <div class="p-2">
            <a href="serverstatus.php" class="service d-flex">
              <img src="{$WEB_ROOT}/templates/aqua-sky-one/images/network.png" class="float-start" alt="...">
              <h3 class="card-title pricing-card-title">
                {lang key='networkstatustitle'}
              </h3>
            </a>
          </div>
        </div>
        <div class="col-4">
          <div class="p-2">
            <a href="{routePath('knowledgebase-index')}" class="account d-flex">
              <img src="{$WEB_ROOT}/templates/aqua-sky-one/images/knowledge.png" class="float-start" alt="...">
              <h3 class="card-title pricing-card-title">
                {lang key='knowledgebasetitle'}
              </h3>
            </a>
          </div>
        </div>
      </div>
      <div class="download_ticket_section">
        <div class="row g-2">
          <div class="col-4">
            <div class="p-2"> <a href="{routePath('download-index')}" class="account d-flex">
                <img src="{$WEB_ROOT}/templates/aqua-sky-one/images/downloads.png" class="float-start" alt="...">
                <h3 class="card-title pricing-card-title">
                  {lang key='downloadstitle'}
                </h3>
              </a></div>
          </div>
          <div class="col-4 submitticket_sec">
            <div class="p-2"> <a href="submitticket.php" class="ticket d-flex">
                <img src="{$WEB_ROOT}/templates/aqua-sky-one/images/ticket.png" class="float-start" alt="...">
                <h3 class="card-title pricing-card-title">
                  {$lang.homepage.submitTicket}
                </h3>
              </a></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="youraccount_section">
  <div class="container">
    <p class="text-center">
    {$lang.homepage.account}
  </p>
    <h2 class="text-center m-4">
    {$lang.homepage.yourAccount}
    </h2>

    <div class="row accounts_inner_section">
      <div class="col-3">
        <div class="card">
          <img src="{$WEB_ROOT}/templates/aqua-sky-one/images/account.png" class="card-img-top" alt="...">
          <div class="card-body">
            <a href="clientarea.php" class="card-accent-midnight-blue">
              <h5 class="card-title text-center">
               {$lang.homepage.yourAccount}
              </h5>
            </a>
          </div>
        </div>
      </div>
      <div class="col-3">
        <div class="card">
          <img src="{$WEB_ROOT}/templates/aqua-sky-one/images/services.png" class="card-img-top" alt="...">
          <div class="card-body">
            <a href="clientarea.php?action=services" class="card-accent-midnight-blue">
              <h5 class="card-title text-center">
              {$lang.homepage.manageServices}
              </h5>
            </a>
          </div>
        </div>
      </div>
      {if $registerdomainenabled || $transferdomainenabled || $numberOfDomains}
      <div class="col-3">
        <div class="card">
          <img src="{$WEB_ROOT}/templates/aqua-sky-one/images/domains.png" class="card-img-top" alt="...">
          <div class="card-body">
            <a href="clientarea.php?action=domains" class="card-accent-midnight-blue">
              <h5 class="card-title text-center">
                {$lang.homepage.manageDomains}
              </h5>
            </a>
          </div>
        </div>
      </div>
      {/if}
      <div class="col-3">
        <div class="card">
          <img src="{$WEB_ROOT}/templates/aqua-sky-one/images/request.png" class="card-img-top" alt="...">
          <div class="card-body">
            <a href="supporttickets.php" class="card-accent-midnight-blue">
              <h5 class="card-title text-center">
              {$lang.homepage.supportRequests}
              </h5>
            </a>
          </div>
        </div>
      </div>
      <div class="col-3">
        <div class="card">
          <img src="{$WEB_ROOT}/templates/aqua-sky-one/images/payment.png" class="card-img-top" alt="...">
          <div class="card-body">
            <a href="clientarea.php?action=masspay&all=true" class="card-accent-midnight-blue">
              <h5 class="card-title text-center">
                  {$lang.homepage.makeAPayment}
              </h5>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>