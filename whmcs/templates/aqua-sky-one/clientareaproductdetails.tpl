{if $modulecustombuttonresult}
    {if $modulecustombuttonresult == "success"}
        {include file="$template/includes/alert.tpl" type="success" msg="{lang key='moduleactionsuccess'}" textcenter=true idname="alertModuleCustomButtonSuccess"}
    {else}
        {include file="$template/includes/alert.tpl" type="error" msg="{lang key='moduleactionfailed'}"|cat:' ':$modulecustombuttonresult textcenter=true idname="alertModuleCustomButtonFailed"}
    {/if}
{/if}

{if $pendingcancellation}
    {include file="$template/includes/alert.tpl" type="error" msg="{lang key='cancellationrequestedexplanation'}" textcenter=true idname="alertPendingCancellation"}
{/if}

{if $unpaidInvoice}
    <div class="alert alert-{if $unpaidInvoiceOverdue}danger{else}warning{/if}" id="alert{if $unpaidInvoiceOverdue}Overdue{else}Unpaid{/if}Invoice">
        <div class="float-right">
            <a href="viewinvoice.php?id={$unpaidInvoice}" class="btn btn-xs btn-default">
                {lang key='payInvoice'}
            </a>
        </div>
        {$unpaidInvoiceMessage}
    </div>
{/if}
<style type="text/css">
    .text-domain{
        color: #5bc0de;
    }
    .addnew{
        background-color: #5cb85c;
        height: 10px;
    }
    .space{
        padding: 16px;
    }
</style> 
<div class="tab-content margin-bottom">
    <div class="tab-pane fade show active" role="tabpanel" id="tabOverview">
        {if $tplOverviewTabOutput}
            {$tplOverviewTabOutput}
        {else}
        <div class="show tab-panel fade in active" id="tabOverview">
            <div class="product-details clearfix">
                <div class="row">
                    <div class="col-md-6 panel_design">
                        <div class="panel-heading">
                            <h3 class="panel-title">
                                <i class="fab fa-product-hunt"></i>&nbsp;Product Details
                            </h3>
                        </div>
                        <div class="panel panel-default panel-accent-blue panelfont">
                        <div class="addnew" style="height: 2px;"></div>
                            <div class="panel-heading" style="background-color: #1a4d80;color: white;height: 40px;">
                                <h3 class="panel-title" style="font-size:15px;padding: 7px;    padding-top: 9px;">
                                    <i class="fas fa-tv"></i>&nbsp;Plex Service Details
                                </h3>
                            </div>
                            <div class="list-group">
                                <div class="list-group-item space ">
                                    Package Name : <strong class="text-domain"> {$groupname} - {$product}</strong>
                                </div>
                                <div class="list-group-item space">
                                    <i class="fas fa-shield text-grey" data-toggle="tooltip" data-placement="auto right"
                                        title="" data-original-title="Your IPTV service Username to login via APP"></i> <span
                                        class="label label-info">Username :</span> <strong
                                        class="text-domain">{$username} </strong>
                                </div>
                                <div class="list-group-item space">
                                    <i class="fas fa-shield text-grey" data-toggle="tooltip" data-placement="auto right"
                                        title="" data-original-title="Your IPTV service Password to login via APP"></i>
                                    <span class="label label-info">Email :</span><strong
                                        class="text-domain">{$clientsdetails['email']} </strong>
                                </div>
                                <div class="list-group-item space">
                                    <span> Expiry Date:</span> <strong class="text-domain">{$nextduedate} </strong>
                                </div>
                                <form method="post" action="clientarea.php?action=productdetails&id={$serviceid}">
                                    {if $customfields}
                                    {foreach from=$customfields item=field}
                                    {if $lang.custom_field_mag == {$field.name}}
                                    {if $field.value neq ''}
                                    <div class="list-group-item">
                                        MAG Portal! : <strong class="text-domain"><a target="_blank"
                                                href='{$mag_portal}'>{$mag_portal} </a></strong>
                                    </div>

                                    <div class="list-group-item">
                                        {$field.name} : <strong id="mag_span" class="text-domain">{if $field.value neq
                                            ''}{$field.value}{else}None{/if}</strong>

                                        <input style="display:none;width: 40%;" type="text" name="magupdate" id="magupdateInput"
                                            value="{if $field.value neq ''}{$field.value}{/if}">
                                        <span id="updatemagaddress" class="label status status-active"
                                            style="display: inline;cursor: pointer;" onclick="
                                                                jQuery('#mag_span').css('display', 'none');
                                                                jQuery('#updatemagaddress').css('display', 'none');
                                                                jQuery('#magupdateInput').css('display', 'inline-block');
                                                                jQuery('#updatemag').css('display', 'inline-block');">
                                            Update
                                        </span>
                                        <button id="updatemag" type="submit" class="label status status-active"
                                            style="display: none;cursor: pointer;color: white;padding: 0;width: 12%;">
                                            Save
                                        </button>
                                    </div>
                                    {/if}
                                    <input type="hidden" name="customAction" value="update_magaddress">
                                    <input type="hidden" name="magFieldId" value="{$field.id}">
                                    <input type="hidden" name="serviceid" value="{$serviceid}">
                                    {/if}
                                    {/foreach}
                                    {/if}
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="panel panel-default panel-accent-green panelfont" style="margin-top:40px">
                            <div class="addnew" style="height: 2px;"></div>
                            <div class="list-group">
                                <div class="list-group-item">
                                    Product : <strong class="text-domain">{$product} - {$groupname}</strong>
                                </div>
                                <div class="list-group-item">
                                    {$LANG.clientareastatus} : <span class="label status status-{$rawstatus}"
                                        style="display: inline;">{$status}</span>
                                </div>
                                <div class="list-group-item">
                                    {$LANG.clientareahostingregdate} : <strong class="text-domain"> {$regdate}</strong>
                                </div>

                                <div class="list-group-item">
                                    {$LANG.recurringamount} : <strong class="text-domain">{$recurringamount}</strong>
                                </div>
                                <div class="list-group-item">
                                    {$LANG.orderbillingcycle} : <strong class="text-domain"> {$billingcycle}</strong>
                                </div>
                                <div class="list-group-item">
                                    {$LANG.orderpaymentmethod} : <strong class="text-domain">{$paymentmethod}</strong>
                                </div>
                                <div class="list-group-item">
                                    {$LANG.firstpaymentamount} : <strong class="text-domain">{$firstpaymentamount}</strong>
                                </div>
                                {if $configurableoptions}
                            </div>
                            {/if}
                            {if $lastupdate}
                            <div class="list-group-item">
                                {$LANG.clientareadiskusage} : <strong class="text-domain">{$diskusage}MB / {$disklimit}MB
                                    ({$diskpercent})</strong>
                            </div>
                            <div class="list-group-item">
                                {$LANG.clientareabwusage} : <strong class="text-domain">{$bwusage}MB / {$bwlimit}MB
                                    ({$bwpercent})</strong>
                            </div>
                            {/if}
                            {if $suspendreason}
                            <div class="list-group-item">
                                {$LANG.suspendreason} : <strong class="text-domain">{$suspendreason}</strong>
                            </div>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>
        </div>
            {foreach $hookOutput as $output}
                <div>
                    {$output}
                </div>
            {/foreach}

        {/if}

    </div>
    <div class="tab-pane fade" role="tabpanel" id="tabDownloads">

        <h3>{lang key='downloadstitle'}</h3>

        {include file="$template/includes/alert.tpl" type="info" msg="{lang key="clientAreaProductDownloadsAvailable"}" textcenter=true}

        <div class="row">
            {foreach from=$downloads item=download}
                <div class="col-10 offset-1">
                    <h4>{$download.title}</h4>
                    <p>
                        {$download.description}
                    </p>
                    <p>
                        <a href="{$download.link}" class="btn btn-default"><i class="fas fa-download"></i> {lang key='downloadname'}</a>
                    </p>
                </div>
            {/foreach}
        </div>

    </div>
    <div class="tab-pane fade" id="tabAddons">

        <h3>{lang key='clientareahostingaddons'}</h3>

        {if $addonsavailable}
            {include file="$template/includes/alert.tpl" type="info" msg="{lang key="clientAreaProductAddonsAvailable"}" textcenter=true}
        {/if}

        <div class="row">
            {foreach from=$addons item=addon}
                <div class="col-10 offset-1">
                    <div class="card bg-default card-accent-blue">
                        <div class="card-header">
                            {$addon.name}
                            <div class="float-right label status-{$addon.rawstatus|strtolower}">{$addon.status}</div>
                        </div>
                        <div class="row card-body">
                            <div class="col-md-6">
                                <p>
                                    {$addon.pricing}
                                </p>
                                <p>
                                    {lang key='registered'}: {$addon.regdate}
                                </p>
                                <p>
                                    {lang key='clientareahostingnextduedate'}: {$addon.nextduedate}
                                </p>
                            </div>
                        </div>
                        <div class="card-footer">
                            {$addon.managementActions}
                        </div>
                    </div>
                </div>
            {/foreach}
        </div>

    </div>
    <div class="tab-pane fade" id="tabChangepw">

        <div class="card">
            <div class="card-body">
                <h3 class="card-title">{lang key='serverchangepassword'}</h3>

                {if $modulechangepwresult}
                    {if $modulechangepwresult == "success"}
                        {include file="$template/includes/alert.tpl" type="success" msg=$modulechangepasswordmessage textcenter=true}
                    {elseif $modulechangepwresult == "error"}
                        {include file="$template/includes/alert.tpl" type="error" msg=$modulechangepasswordmessage|strip_tags textcenter=true}
                    {/if}
                {/if}

                <form class="using-password-strength" method="post" action="{$smarty.server.PHP_SELF}?action=productdetails#tabChangepw" role="form">
                    <input type="hidden" name="id" value="{$id}" />
                    <input type="hidden" name="modulechangepassword" value="true" />

                    <div id="newPassword1" class="form-group row has-feedback">
                        <label for="inputNewPassword1" class="col-xl-4 col-form-label">{lang key='newpassword'}</label>
                        <div class="col-xl-5">
                            <input type="password" class="form-control" id="inputNewPassword1" name="newpw" autocomplete="off" />
                            {include file="$template/includes/pwstrength.tpl"}
                        </div>
                        <div class="col-xl-3">
                            <button type="button" class="btn btn-default btn-block generate-password" data-targetfields="inputNewPassword1,inputNewPassword2">
                                {lang key='generatePassword.btnLabel'}
                            </button>
                        </div>
                    </div>
                    <div id="newPassword2" class="form-group row has-feedback">
                        <label for="inputNewPassword2" class="col-xl-4 col-form-label">{lang key='confirmnewpassword'}</label>
                        <div class="col-xl-5">
                            <input type="password" class="form-control" id="inputNewPassword2" name="confirmpw" autocomplete="off" />
                            <div id="inputNewPassword2Msg">
                            </div>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="offset-xl-4 col-xl-6">
                            <input class="btn btn-primary" type="submit" value="{lang key='clientareasavechanges'}" />
                            <input class="btn" type="reset" value="{lang key='cancel'}" />
                        </div>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>
