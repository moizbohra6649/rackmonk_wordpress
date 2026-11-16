<?php

use WHMCS\Database\Capsule;

//Hook to make option to review the product from the service page

add_hook('AdminAreaFooterOutput', 1, function ($vars) {
    $product_id = $_GET['id'];
    if ($vars['filename'] === 'configproducts' && empty($_GET['action'])) {
        return <<<HTML
        <script type="text/javascript">
            $(document).ready(function() {
            // Select all <tr> elements with the class "product"
            var productRows = $('.datatable tr.product');

            // Do something with the selected rows
            productRows.each(function() {
                getproductif = $(this).find('td').eq(7).find('a').attr('href');
                
                // Use a regular expression to match the 'id' parameter
                var idValue = getproductif.match(/[\?&]id=([^&]+)/);
                if (idValue) {
                    // console.log(idValue[1]); // Outputs: 1
                }
                var ninthTdHtml = $(this).find('td').eq(8).append('<a href="addonmodules.php?module=product_page&action=editproduct&pid='+idValue[1]+'" title="add product page" target="_blank"><i class="fa fa-plus-circle" aria-hidden="true"></i></a>');
                $(this).find('td').eq(8).css('width', '5%');
                $(this).find('td').eq(5).css('width', '15%');
            });
            
            var firstRow_text = $('.datatable tr:first').find('th').eq(5).text();
            if(firstRow_text == 'Features'){
                $('.datatable tr:first').find('th').eq(8).css('width', '5%');
                $('.datatable tr:first').find('th').eq(5).css('width', '15%');
            }

        });
        </script>
HTML;
    }
});

add_hook('ClientAreaHeadOutput', 1, function ($vars) {
    if ($vars['templatefile'] == "products" && $vars['inShoppingCart'] == 1) {
        // require_once __DIR__ . '/product_page.php';
        // $licenseinfo = product_page_whmcs_doCheckLicense();
        // if (isset($licenseinfo['status']) && $licenseinfo['status'] == "Active") {

            if (!empty($vars['products'])) {
                global $smarty;

                if (isset($smarty) && isset($smarty->tpl_vars['products']) && is_array($smarty->tpl_vars['products']->value)) {
                    $allProducts = $smarty->tpl_vars['products']->value;
                    $btntext = "More Info";
                    $btnicon = 'fas fa-info';
                    $pids = [];
                    $idsforapi = array();
                    foreach ($allProducts as $index => $value) {
                        $pid = $value['pid'];

                        // get all data from ms_product_page table of product
                        $ms_product_page_datacount = Capsule::table("ms_product_page")
                            ->where("pid", $pid)
                            ->count();
                        if ($ms_product_page_datacount > 0) {
                            $ms_product_page_data = Capsule::table("ms_product_page")
                                ->where("pid", $pid)
                                ->first();
                            if ($ms_product_page_data->enable == 1) {
                                if ($ms_product_page_data->more_Info_Button == 1) {
                                    $titleforurl = strtolower(str_replace(" ", "-", $ms_product_page_data->insight_title));
                                    $pids[$titleforurl] = $ms_product_page_data->pid;
                                    $idsforapi["ids"][] = $ms_product_page_data->pid;
                                    $idsforapi["attachments"][$ms_product_page_data->pid]["modulelink"] = "?m=product_page&pid=" . $ms_product_page_data->pid . "&name=" . $titleforurl;
                                } else {
                                    $insight_title = $ms_product_page_data->insight_title;
                                    $titleforurl = strtolower(str_replace(" ", "-", $insight_title));
                                    $smarty->tpl_vars['products']->value[$index]['productUrl'] = "index.php?m=product_page&pid=" . $pid . "&name=" . $titleforurl;
                                }
                            }
                        }
                    }
                    $finalarraypids = json_encode($pids);
                    $carttpl = $vars['carttpl'];

                    // switch case for  cart templates
                    switch ($carttpl) {
                        case 'cloud_slider':
                            if (isset($idsforapi) && !empty($idsforapi)) {

                                $onlyids = implode(", ", $idsforapi["ids"]);
                                $command = 'GetProducts';
                                $postData = array(
                                    'pid' => $onlyids,
                                );
                                $results = localAPI($command, $postData);
                                if (isset($results["result"]) && $results["result"] == "success" && !empty($results["products"]["product"])) {
                                    $newfileterarray = array();
                                    $products = $results["products"]["product"];
                                    foreach ($products as $prodata) {
                                        $newfileterarray[$prodata["product_url"]] = $idsforapi["attachments"][$prodata["pid"]]["modulelink"];
                                    }

                                    $finalarray = json_encode($newfileterarray);

                                    return <<<HTML
            <script type="text/javascript">
            var fnarr = $finalarray;
            $(document).ready(function(){
                $("#product-order-button").after("<span id='appenddatahere'></span>");
                current = $("#product-order-button").attr("href");
                embedbuttonbycurrenturl(current);

                $('#products-top').on('change', function () {
                    setTimeout(function () {
                    current = $("#product-order-button").attr("href");
                    embedbuttonbycurrenturl(current);
                    }, 500);  //
                });
            })

            function embedbuttonbycurrenturl(currenturl = "")
            {
                appenddata = "";
                $.each(fnarr, function(index, value) {

                    if (index.includes(currenturl)) {
                       appenddata    = '<div style="padding-top:10px;"><a href="'+value+'" target="_blank" class="btn btn-info"><i class="fa $btnicon"></i>&nbsp;&nbsp;$btntext</a></div>'
                    }
                });
                $("#appenddatahere").html(appenddata);

            }
            </script>
HTML;
                                }
                            }
                            break;
                        case 'legacy_boxes':
                            return <<<HTML
                                <script type="text/javascript">
                                $(document).ready(function(){
                                    var pids = $finalarraypids;
                                    $('[name="pid"]').each(function() {
                                        number = $(this).val(); 
                                        let key = Object.keys(pids).find(k => pids[k] === number);
                                    if (Object.values(pids).includes(number)) {
                                        console.log(key);
                                         $(this).parent().after('<a href="?m=product_page&pid='+number+'&name='+key+'" target="_blank" class="btn btn-info" style="float:right;"><i class="fa $btnicon"></i>&nbsp;&nbsp;$btntext</a>');
                                    }
                                    });
                                })
            </script>
HTML;
                            break;
                        case 'standard_cart':
                            return <<<HTML
            <script type="text/javascript">
            $(document).ready(function(){
                var pids = $finalarraypids;
                $('.btn-success').each(function (index, element) {
                    var text = $(this).attr('id'); // get class name to extract product id
                    if (typeof text === "string" && text.match(/\d+/)) {
                        let number = text.match(/\d+/)[0]; // extracting product id from class in var text
                    let key = Object.keys(pids).find(k => pids[k] === number);
                    if (Object.values(pids).includes(number)) {
                        $(this).after('<div style="padding-top:10px;"><a href="?m=product_page&pid='+number+'&name='+key+'" target="_blank" class="btn btn-info"><i class="fa $btnicon"></i>&nbsp;&nbsp;$btntext</a></div>');
                    }
                    }
                });
            })
            </script>
HTML;
                            break;
                        case 'legacy_modern':
                            return <<<HTML
            <script type="text/javascript">
            $(document).ready(function(){
                var pids = $finalarraypids;
                $('.btn-success').each(function (index, element) {
                    $(this).parent().parent().removeAttr('onclick');
                    var text = $(this).parent().parent().attr('id'); // get class name to extract product id);
                    if (typeof text === "string" && text.match(/\d+/)) {
                        let number = text.match(/\d+/)[0]; // extracting product id from class in var text
                    let key = Object.keys(pids).find(k => pids[k] === number);
                    if (Object.values(pids).includes(number)) {
                        $(this).after('<div style="padding-top:10px;"><a href="?m=product_page&pid='+number+'&name='+key+'" target="_blank" class="btn btn-info"><i class="fa $btnicon"></i>&nbsp;&nbsp;$btntext</a></div>');
                    }
                    }
                });
            })
            </script>
HTML;
                            break;
                        case 'premium_comparison':
                            return <<<HTML
            <script type="text/javascript">
            $(document).ready(function(){
                var pids = $finalarraypids;
                $('.order-button').each(function (index, element) {
                    var text = $(this).attr('id'); // get class name to extract product id
                    if (typeof text === "string" && text.match(/\d+/)) {
                        let number = text.match(/\d+/)[0]; // extracting product id from class in var text
                    let key = Object.keys(pids).find(k => pids[k] === number);
                    if (Object.values(pids).includes(number)) {
                        $(this).after('<div style="padding-top:10px;"><a href="?m=product_page&pid='+number+'&name='+key+'" target="_blank" class="btn btn-info"><i class="fa $btnicon"></i>&nbsp;&nbsp;$btntext</a></div>');
                    }
                    }
                });
            })
            </script>
HTML;
                            break;
                        case 'pure_comparison':
                            return <<<HTML
            <script type="text/javascript">
            $(document).ready(function(){
                var pids = $finalarraypids;
                $('.order-button').each(function (index, element) {
                    var text = $(this).attr('id'); // get class name to extract product id
                    if (typeof text === "string" && text.match(/\d+/)) {
                        let number = text.match(/\d+/)[0]; // extracting product id from class in var text
                    let key = Object.keys(pids).find(k => pids[k] === number);
                    if (Object.values(pids).includes(number)) {
                        $(this).after('<div ><a href="?m=product_page&pid='+number+'&name='+key+'" target="_blank" class="btn btn-info"><i class="fa $btnicon"></i>&nbsp;&nbsp;$btntext</a></div>');
                    }
                    }
                });
            })
            </script>
HTML;
                            break;
                        case 'supreme_comparison':
                            return <<<HTML
            <script type="text/javascript">
            $(document).ready(function(){
                var pids = $finalarraypids;
                $('.order-now').each(function (index, element) {
                    var text = $(this).parent().attr('id'); // get class name to extract product id
                    if (typeof text === "string" && text.match(/\d+/)) {
                        let number = text.match(/\d+/)[0]; // extracting product id from class in var text
                    let key = Object.keys(pids).find(k => pids[k] === number);
                    if (Object.values(pids).includes(number)) {
                        $(this).parent().after('<div style="padding-top:10px;"><a href="?m=product_page&pid='+number+'&name='+key+'" target="_blank" class="btn btn-info"><i class="fa $btnicon"></i>&nbsp;&nbsp;$btntext</a></div>');
                    }
                    }
                });
            })
            </script>
HTML;
                            break;
                        case 'universal_slider':
                            return <<<HTML
            <script type="text/javascript">
            $(document).ready(function(){
                var pids = $finalarraypids;
                $('.order-button').each(function (index, element) {
                    var text = $(this).attr('id'); // get class name to extract product id
                    if (typeof text === "string" && text.match(/\d+/)) {
                        let number = text.match(/\d+/)[0]; // extracting product id from class in var text
                    let key = Object.keys(pids).find(k => pids[k] === number);
                    if (Object.values(pids).includes(number)) {
                        $(this).after('<div style="padding-top:10px;"><a href="?m=product_page&pid='+number+'&name='+key+'" target="_blank" class="btn btn-info"><i class="fa $btnicon"></i>&nbsp;&nbsp;$btntext</a></div>');
                    }
                    }
                });
            })
            </script>
HTML;
                            break;
                        default:
                            //code block
                    }
                }
            }
        // }
    }

    // show button on service page
    if (isset($_GET['action']) && $_GET['action'] == "productdetails" && $vars['templatefile'] == "clientareaproductdetails") {
        $enable = "0";
        // Access the service object
        $service = $vars['service'];
        // Alternatively, if you're looking for the package (product) ID:
        $productId = $vars['pid'];

        $clientId = $vars['clientsdetails']['client_id'];
        $product = $service->product;

        // ms_product_page our custom table having pid product id get the insight_title  if exists and change productName variable value
        $checkexists = Capsule::table('ms_product_page')
            ->where('pid', $productId)
            ->count();
        if ($checkexists > 0) {
            // Get the product name from ms_product_page table
            $pdata = Capsule::table('ms_product_page')
                ->where('pid', $productId)
                ->get();
            $productName = $pdata[0]->insight_title;
            $enable = $pdata[0]->enable;
        }

        if ($enable == "1") {
            // Check in table  userid and product id table name is ms_product_page_reviews
            $review = Capsule::table('ms_product_page_reviews')
                ->where('user_id', $clientId)
                ->where('product_id', $productId)
                ->count();

            // Check if review exists
            $btnname = "Write a review";
            if ($review > 0) {
                $btnname = "Edit Review";
            }

            $productName =  strtolower(str_replace(" ", "-", $productName));
            $button = '<a class="btn btn-primary" href="index.php?m=product_page&pid=' . $productId . '&name=' . $productName . '&tab=review"><i class="fa fa-comments" aria-hidden="true"></i> ' . $btnname . '</a>';
            if ($vars['status'] == "Active") {
                return <<<HTML
                <script type="text/javascript">
                $(document).ready(function(){
                   var button = '$button';
                    $('#tabOverview').append(button);
                })
                </script>
               HTML;
            }
        }
    }
});