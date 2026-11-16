<link rel="stylesheet" type="text/css" href="/modules/addons/product_page/assets/css/clientstyle.css?v=3.2.6">
<link rel="stylesheet" type="text/css" id="u0" href="/assets/js/tinymce/skins/lightgray/skin.min.css">
<link rel="stylesheet" type="text/css" id="u0" href="/assets/js/tinymce/skins/lightgray/content.min.css">
<link rel="stylesheet" type="text/css" id="u2" href="//www.tinymce.com/css/codepen.min.css">
<link rel="stylesheet" data-mce-href="/assets/js/tinymce/plugins/codesample/css/prism.css"
    href="/assets/js/tinymce/plugins/codesample/css/prism.css">
<script type="text/javascript" src="/assets/js/tinymce/tinymce.min.js"></script>
<script type="text/javascript" src="/modules/addons/product_page/assets/js/adminjs.js?v=latest"></script>
<form action="./index.php?m=product_page&pid={$currenct_product_id}&name={$titleforurl_current}&tab=review"
    method="post" style="display: none;" id="review_reply_form">
    <textarea style="width:100%;" rows="15" class="tinymce" name="review_reply_textarea"
        id="review_reply_textarea"></textarea>
    <div class="btn-container" style="text-align: center;margin-top: 10px;">
        <input type="hidden" name="review_id_modal" id="review_id_modal" value="">
        <button id="review_reply" type="submit" name="review_reply_btn" value="Reply" class="btn btn-primary">
            <i class="fa fa-reply" aria-hidden="true"></i>&nbsp;Reply
        </button>
        <!-- <input id="review_reply" type="submit" name="review_reply_btn" value="Reply" class="btn btn-primary"> -->
        <input id="review_reply_cancel" type="button" name="review_reply_cancel_btn" value="Cancel"
            class="btn btn-cancel">

    </div>
</form>
{if $Template == 'twenty-one'}
<div class="container"
    style="background: white;padding: 30px 30px;background-color: #f9f9f9;border-radius: 8px;border: 1px solid #ddd;margin: auto;box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
    {else}
    <div class="container"
        style="background: white;padding: 30px 30px;background-color: #f9f9f9;border-radius: 8px;border: 1px solid #ddd;margin: auto;box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
        {/if}
        <div class="row">
            <div class="col-lg-3 col-md-4 col-xs-12 col-sm-4">
                <img src="/{$logossss}" style="max-width: 100%;"
                    onerror="this.onerror=null;this.src='/modules/addons/product_page/assets/img/defaultimage.png';">
            </div>
            <div class="col-lg-9 col-md-8 col-xs-12 col-sm-8">
                <div class="row">
                    <div class="col-lg-7 col-md-8 col-xs-12 col-sm-12">
                        <div>
                            <h2
                                style="margin-bottom: 10px;margin-top: 0px;font-weight: bold;max-height: 84px;overflow: hidden;text-overflow: ellipsis;display: -webkit-box;-webkit-line-clamp: 2;-webkit-box-orient: vertical;line-height: 1.5;font-family: 'Open Sans';">
                                {$insight_title}</h2>
                        </div>
                    </div>
                    <div class="col-lg-5 col-md-4 col-xs-12 col-sm-12">
                        <h4 class="product-priceheading">
                            <p class="product-price {if $product_price == "Free"}free{else}commercial{/if}"
                                style="font-size: 16px;font-family: 'Open Sans', sans-serif;">{$product_price}</p>
                        </h4>
                    </div>
                </div>
                {if $insight_tagline neq ''}
                <div>
                    <h5 style="margin-bottom: 10px; margin-top: 0px;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;"
                        class="">{$insight_tagline}</h5>
                </div>
                {/if}
                <div class="stars customstartstyle" style="--rating: {$averageRating};margin-bottom: 10px;"
                    aria-label="Rating of {$averageRating} out of 5"></div>&nbsp<a id="totalReviews"
                    style="color: #337ab7;cursor: pointer;">{$totalReviews} Reviews</a>
                <div>
                    <p style="margin-bottom: 15px;" class="description-container">
                        {$insight_short_description}</p>
                </div>
                <div>
                    <a href="{$product_urlsss}" style="list-style-type: none;color: white;" target="{$orderbtn_window}">
                        <button class="btn btn-{$orderbtn_color}">
                            <i class="fa {$orderbtnicon}"></i>&nbsp;{$orderbtntext}
                        </button>
                    </a>
                    {foreach $buttons as $key => $value}
                    <a href="{$value['btnlink']}" style="list-style-type: none;color: white;"
                        target="{$value['window']}">
                        <button class="btn btn-{$value['color']}">
                            <i class="fa {$value['btnicon']}" aria-hidden="true"></i>&nbsp;{$value['btntext']}
                        </button>
                    </a>
                    {/foreach}

                </div>
            </div>
            <div class="col-md-12" style="margin-top: 30px;">
                {if $review_error}
                <div class="alert alert-danger" style="margin: 0px;">{$review_error}</div>
                {/if}
                {if $reviewSubmitted}
                <div class="alert alert-success" style="margin: 0px;">Your review has been submitted successfully!</div>
                {/if}
                {if $review_delete_success}
                <div class="alert alert-success" style="margin: 0px;">{$review_delete_success}</div>
                {/if}
                {if $review_edit_success}
                <div class="alert alert-success" style="margin: 0px;">{$review_edit_success}</div>
                {/if}
            </div>
            <div class="tabs-container" style="display:grid;">
                <div class="tabs">
                    <div class="tab {if $review_error}{elseif $reviewSubmitted}{elseif $forcereviewtab}{else}active{/if}"
                        onclick="switchTab(event, 'tab1')">Description
                    </div>
                    <div class="tab {if $review_error}active{elseif $reviewSubmitted}active{elseif $forcereviewtab}active{/if}"
                        id="reviewtab" onclick="switchTab(event, 'tab2')">
                        Reviews</div>
                </div>

                <div id="tab1"
                    class="tab-content {if $review_error}{elseif $reviewSubmitted}{elseif $forcereviewtab}{else}active{/if}">
                    <!-- description content here -->
                    <div class="col-md-12" style="margin-top:20px;">
                        {$insight_long_description|unescape:'html'}
                    </div>

                    {if $slider_images != 'null' && $slider_images != ''}
                    <div class="col-md-12" style="margin-top: 20px;">
                        <div class="slider-container">
                            <!-- Main image display with arrows for navigation -->
                            <div class="main-image" id="mainImage" onclick="openModal()">
                                <div class="arrow arrow-left" onclick="prevImage(event)">&#10094;</div>
                                <div class="arrow arrow-right" onclick="nextImage(event)">&#10095;</div>
                            </div>

                            <!-- Thumbnail container with vertical scroll and scroll arrows -->
                            <div class="thumbnail-container">
                                <div class="thumbnail-arrow thumbnail-arrow-up" onclick="scrollThumbnails(-1)">↑</div>
                                <div class="thumbnails" id="thumbnailContainer"></div>
                                <div class="thumbnail-arrow thumbnail-arrow-down" onclick="scrollThumbnails(1)">↓</div>
                            </div>
                        </div>

                        <!-- Modal for image popup -->
                        <div id="imageModal" class="modal" onclick="closeModal(event)">
                            <span class="close" onclick="closeModal(event)">&times;</span>
                            <img class="modal-content" id="modalImage">
                        </div>
                    </div>
                    {/if}

                </div>

                <div id="tab2"
                    class="tab-content {if $review_error}active{elseif $reviewSubmitted}active{elseif $forcereviewtab}active{/if}">
                    <div class="col-md-12" style="margin-top:20px;">
                        <div id="reviews">
                            <div class="review-card">
                                <h3 style="text-align: left;">Overall Reviews</h3>
                                <div class="card-overall">
                                    <div class="overall-rating">
                                        <div class="stars" style="--rating: {$averageRating};"
                                            aria-label="Rating of {$averageRating} out of 5"></div>
                                        <p class="rating-text">Overall Rating: {$averageRating|string_format:"%.1f"} / 5
                                        </p>
                                    </div>
                                </div>
                                <div class="card-breakdown">
                                    {foreach from=[5, 4, 3, 2, 1] item=rating}
                                    <div class="breakdown-row">
                                        <span class="rating-label">{$rating} star{if $rating > 1}s{/if}</span>
                                        <div class="rating-bar">
                                            <div class="rating-fill" style="width: {$reviewPercentages[$rating]}%;">
                                            </div>
                                        </div>
                                        <span class="rating-count">{$reviewSummary[$rating]}</span>
                                    </div>
                                    {/foreach}
                                </div>
                            </div>
                            <hr>

                            {if $userId}
                            {if $productbuyerror}
                            <div class="alert alert-warning">
                                <div>{$productbuyerror}!</div>
                            </div>
                            {else}
                            <div style="margin-top: 30px;">
                                <h2 style="font-weight: bold;font-size: 24px; color: #333; margin-bottom: 10px;"
                                    id="leaveareview">Leave a review</h2>
                                {if $warning}
                                <div class="alert alert-warning">{$warning}</div>
                                {if $this_client_review|@count > 0}
                                <div class="thisclientreview">
                                    <div class="review">
                                        {foreach from=$this_client_review item=review}
                                        <div class="review-header">
                                            <img src="/modules/addons/product_page/assets/img/avatar.png" alt="Avatar"
                                                class="review-avatar">
                                            <div class="review-details">
                                                <span class="review-name">{$review->firstname}
                                                    {$review->lastname}</span>
                                                <button id="editreview" class="btn btn-default"
                                                    style="float: right;">Edit review
                                                </button>
                                                <div class="review-date">{$review->created_at|date_format:"%B %d, %Y at
                                                    %I:%M %p"}</div>

                                            </div>
                                        </div>
                                        <div class="review-stars">
                                            {for $i=1 to $review->rating}★{/for}{for $i=$review->rating+1 to 5}☆{/for}
                                        </div>
                                        <div class="review-message">
                                            {$review->review|nl2br}
                                        </div>

                                        <div class="admin_reply" style="align-items: center;margin-top: 20px;">
                                            {if $review->reply != ""}
                                            <div class="admin_reply_content scroll_{$review->id}"
                                                style="width: 95%;background-color: white;padding: 15px;border-radius: 10px;margin: auto;margin-top: 20px;">
                                                <div class="review-header">
                                                    <div class="review-details">
                                                        <div class="row">
                                                            <div class="col-md-8">
                                                                <span class="review-name">{$review->admin_name}</span>
                                                                <div class="review-date">
                                                                    {$review->reply_created_at|date_format:"%B
                                                                    %d, %Y at
                                                                    %I:%M %p"}</div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                {if $adminlogin == "yes"}
                                                                <button type="button"
                                                                    class="btn btn-danger delete-btn btn-sm"
                                                                    onclick="deleteReviewReply({$review->reply_id})"
                                                                    style="float: right;"><i class="fa fa-trash"
                                                                        aria-hidden="true"></i>
                                                                    Delete Reply</button>
                                                                <button type="button"
                                                                    class="btn btn-primary edit-btn admin_editreplybtn btn-sm"
                                                                    data-toggle="modal" data-target="#exampleModalLong"
                                                                    data-rewid="{$review->id}"
                                                                    data-target=".bd-example-modal-lg"
                                                                    style="float: right;margin-right: 5px;">
                                                                    <i class="fa fa-reply" aria-hidden="true"></i> Edit
                                                                    Reply
                                                                </button>
                                                                {/if}
                                                            </div>
                                                        </div>




                                                    </div>
                                                </div>
                                                <div>
                                                    {$review->reply|unescape:'html'}
                                                </div>
                                            </div>
                                            {/if}
                                        </div>

                                        {/foreach}
                                        {if $adminlogin == "yes"}
                                        <div class="addminaccess">
                                            <div class="pull-left loader hide" id="product_page_loader"
                                                style="display: block;float: left;">
                                                <i class="fas fa-circle-notch fa-spin"></i>
                                                Loading...
                                            </div>
                                            <!-- Create two buttons one with edit and delete with fa icons -->
                                            <!-- <a
                                                href="/admin/addonmodules.php?module=product_page&action=replyreview&pid={$review->product_id_get}&rwid={$review->id}"> -->
                                            {if $review->reply == ""}
                                            <button type="button" class="btn btn-primary edit-btn admin_replybtn btn-sm"
                                                data-toggle="modal" data-target="#exampleModalLong"
                                                data-rewid="{$review->id}" data-target=".bd-example-modal-lg"> <i
                                                    class="fa fa-reply" aria-hidden="true"></i> Reply</button>
                                            {/if}
                                            <!-- </a> -->
                                            <button type="button" class="btn btn-danger delete-btn btn-sm"
                                                onclick="deleteReview({$review->id})"><i class="fa fa-trash"
                                                    aria-hidden="true"></i>
                                                Delete</button>
                                        </div>
                                        {/if}
                                    </div>

                                </div>
                                <form method="post" action="" style="display: none;" id="editform">
                                    <input type="hidden" name="product_id" value="{$productId}">

                                    <div class="form-group">
                                        <label for="rating">Rating</label>
                                        <div class="star-rating" id="rating">
                                            <input type="radio" id="star5" name="rating" value="5" {if $review->rating
                                            == 5}checked{/if}>
                                            <label for="star5" title="5 stars">★</label>
                                            <input type="radio" id="star4" name="rating" value="4" {if $review->rating
                                            == 4}checked{/if}>
                                            <label for="star4" title="4 stars">★</label>
                                            <input type="radio" id="star3" name="rating" value="3" {if $review->rating
                                            == 3}checked{/if}>
                                            <label for="star3" title="3 stars">★</label>
                                            <input type="radio" id="star2" name="rating" value="2" {if $review->rating
                                            == 2}checked{/if}>
                                            <label for="star2" title="2 stars">★</label>
                                            <input type="radio" id="star1" name="rating" value="1" {if $review->rating
                                            == 1}checked{/if}>
                                            <label for="star1" title="1 star">★</label>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="review">Review</label>
                                        <textarea name="review" id="review" class="form-control" rows="5"
                                            placeholder="Write your review here...">{$review->review}</textarea>
                                    </div>
                                    <button type="button" id="editreview_cancel" class="btn btn-default">Cancel
                                    </button>
                                    <button type="submit" class="btn btn-primary" name="reviewsubmit_edit">Edit
                                        Review</button>
                                </form>
                                {/if}
                                {else}
                                <form method="post" action="">
                                    <input type="hidden" name="product_id" value="{$productId}">

                                    <div class="form-group">
                                        <label for="rating">Rating</label>
                                        <div class="star-rating" id="rating">
                                            <input type="radio" id="star5" name="rating" value="5">
                                            <label for="star5" title="5 stars">★</label>
                                            <input type="radio" id="star4" name="rating" value="4">
                                            <label for="star4" title="4 stars">★</label>
                                            <input type="radio" id="star3" name="rating" value="3">
                                            <label for="star3" title="3 stars">★</label>
                                            <input type="radio" id="star2" name="rating" value="2">
                                            <label for="star2" title="2 stars">★</label>
                                            <input type="radio" id="star1" name="rating" value="1">
                                            <label for="star1" title="1 star">★</label>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="review">Review</label>
                                        <textarea name="review" id="review" class="form-control" rows="5"
                                            placeholder="Write your review here...">{$postreviewcheck}</textarea>
                                    </div>

                                    <button type="submit" class="btn btn-primary" name="reviewsubmit">Submit
                                        Review</button>
                                </form>
                                {/if}
                            </div>
                            {/if}
                            {else}
                            <div class="alert alert-warning">
                                <div style="float: left;">Login to leave a review!</div>
                                <div style="text-align:end;">
                                    <button class="btn btn-default"
                                        onclick="window.location.href='login.php'">Login</button>
                                </div>
                            </div>
                            {/if}
                            <hr>
                            <form id="review_delete_form" action="" method="post" style="display: none;">
                                <input type="hidden" name="review_id" value="" id="review_id_to_delete">
                            </form>
                            <form id="review_reply_delete_form" action="" method="post" style="display: none;">
                                <input type="hidden" name="review_reply_id" value="" id="review_reply_id_to_delete">
                            </form>
                            <h2 style="font-weight: bold;font-size: 24px; color: #333; margin-bottom: 10px;">Client
                                Reviews</h2>

                            {if $reviews|@count > 0}
                            <div class="reviews-list" style="overflow: scroll;">
                                {foreach from=$reviews item=review}

                                <div class="review">
                                    <div class="review-header">
                                        <img src="/modules/addons/product_page/assets/img/avatar.png" alt="Avatar"
                                            class="review-avatar">
                                        <div class="review-details">
                                            <span class="review-name">{$review->firstname} {$review->lastname}</span>
                                            <div class="review-date">{$review->created_at|date_format:"%B %d, %Y at
                                                %I:%M %p"}</div>
                                        </div>
                                        <div class="review-stars">
                                            {for $i=1 to $review->rating}★{/for}{for $i=$review->rating+1 to 5}☆{/for}
                                        </div>
                                    </div>

                                    <div class="review-message">
                                        {$review->review|nl2br}
                                    </div>
                                    <div class="admin_reply" style="align-items: center; margin-top: 20px;">
                                        {if $review->reply != ""}
                                        <div class="admin_reply_content scroll_{$review->id}"
                                            style="width: 95%;background-color: white;padding: 15px;border-radius: 10px;margin: auto;margin-top: 20px;">
                                            <div class="review-header">
                                                <div class="review-details">
                                                    <div class="row">
                                                        <div class="col-md-8"><span
                                                                class="review-name">{$review->admin_name}</span>
                                                            <div class="review-date">
                                                                {$review->reply_created_at|date_format:"%B
                                                                %d, %Y at
                                                                %I:%M %p"}</div>
                                                        </div>
                                                        <div class="col-md-4 col-sm-12 col-xs-12">{if $adminlogin ==
                                                            "yes"}

                                                            <button type="button"
                                                                class="btn btn-danger delete-btn btn-sm"
                                                                onclick="deleteReviewReply({$review->reply_id})"
                                                                style="float: right;"><i class="fa fa-trash"
                                                                    aria-hidden="true"></i>
                                                                Delete Reply</button>
                                                            <button type="button"
                                                                class="btn btn-primary edit-btn admin_editreplybtn btn-sm"
                                                                data-toggle="modal" data-target="#exampleModalLong"
                                                                data-rewid="{$review->id}"
                                                                data-target=".bd-example-modal-lg"
                                                                style="float: right; margin-right: 5px;">
                                                                <i class="fa fa-reply" aria-hidden="true"></i> Edit
                                                                Reply
                                                            </button>
                                                            {/if}
                                                        </div>
                                                    </div>



                                                </div>
                                            </div>
                                            <div>
                                                {$review->reply|unescape:'html'}
                                            </div>
                                        </div>
                                        {/if}
                                    </div>


                                    {if $adminlogin == "yes"}
                                    <div class="addminaccess">
                                        <div class="pull-left loader hide" id="product_page_loader"
                                            style="display: block;float: left;">
                                            <i class="fas fa-circle-notch fa-spin"></i>
                                            Loading...
                                        </div>
                                        <!-- Create two buttons one with edit and delete with fa icons -->
                                        <!-- <a
                                            href="/admin/addonmodules.php?module=product_page&action=replyreview&pid={$review->product_id_get}&rwid={$review->id}"> -->
                                        {if $review->reply == ""}
                                        <button type="button" class="btn btn-primary edit-btn admin_replybtn btn-sm"
                                            data-toggle="modal" data-target="#exampleModalLong"
                                            data-rewid="{$review->id}" data-target=".bd-example-modal-lg"> <i
                                                class="fa fa-reply" aria-hidden="true" style="color: white;"></i>

                                            Reply</button>
                                        {/if}
                                        <!-- </a> -->
                                        <button type="button" class="btn btn-danger delete-btn btn-sm"
                                            onclick="deleteReview({$review->id})"><i class="fa fa-trash"
                                                aria-hidden="true"></i>
                                            Delete</button>
                                    </div>
                                    {/if}
                                </div>
                                {/foreach}
                            </div>
                            {else}
                            <p>No reviews available.</p>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    {if count($seealso) > 0}

    <div style="margin-top: 30px;">
        <h3><b>See Also</b></h3>
        
        <div class="container_seealso">
        <ul id="autoWidth" class="cs-hidden">
            {foreach $seealso as $key_seealso => $value_seealso}
                <li class="item-a">
                    <div class="box">
                        <a href="/index.php?m=product_page&pid={$value_seealso['pid']}&name={$value_seealso['titleforurl']}"
                            class="rel-anc">
                            <div class="slider-img">
                                <img src="/{$value_seealso['logo']}" alt="{$value_seealso['title']}"
                                    onerror="this.onerror=null;this.src='/modules/addons/product_page/assets/img/defaultimage.png';">
                            </div>

                            <div style="background-color: white;padding: 5px 10px;">
                                <p class="text-container" style="color: black;"><b>{$value_seealso['title']}</b></p>
                            </div>
                            <!-- Rating -->
                            <div class="product-rating">
                                <div class="stars" style="--rating: {$value_seealso['rating']};"
                                    aria-label="Rating of {$value_seealso['rating']} out of 5"></div>
                            </div>
                            <!-- Product Description -->
                            <p class="product-descriptionn">{$value_seealso['insight_short_description']|truncate:80:"..."}
                            </p>
                            <!-- Pricing -->
                            <p class="product-price {if $value_seealso['paytype'] == "free"}free{else}commercial{/if}">
                                {if $value_seealso['paytype'] == "free"}
                                Free
                                {else}
                                Commercial
                                {/if}
                            </p>
                        </a>
                    </div>
                </li>
            {/foreach}
            <!-- <li class="item-a">
                <div class="box">
                    <p class="marvel">MARVEL</p>
                    <img src="https://i.postimg.cc/L552NTd2/Ant-Man.png" alt="hero image" class="model">
                    <div class="details">
                        <img src="https://i.postimg.cc/15Lsrwv1/Ant-Man-logo.png" alt="logo" class="logo" width="100px" style="height: auto;">
                        <p>Scott Lang, a reformed thief, steals the Ant-Man suit from Hank Pym to save his daughter, Cassie. This storyline is pivotal as it showcases Scott's transformation from a criminal to a hero, emphasizing redemption and fatherhood.</p>
                    </div>
                </div>
            </li> -->

            
        </ul>


    </div>
    </div>
    {literal}
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script> -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightslider/1.1.6/js/lightslider.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightslider/1.1.6/css/lightslider.min.css" />
    <script>
        $(document).ready(function() {
            $('#autoWidth').lightSlider({
                autoWidth: true,
                loop: true,
                onSliderLoad: function() {
                    $('#autoWidth').removeClass('cS-hidden');
                }
            });
        });
        const slider = document.querySelector('.slider-items');
        const prevBtn = document.querySelector('.prev-btn');
        const nextBtn = document.querySelector('.next-btn');
        const visibleItems = 5; // Number of visible items
        const totalItems = slider.children.length;
        let currentIndexs = 0;

        const updateSliderPosition = () => {
            const itemWidth = slider.children[0].offsetWidth + 10; // Add margin or gap
            slider.style.transform = `translateX(-${currentIndexs * itemWidth}px)`;
        };

        prevBtn.addEventListener('click', () => {
            currentIndexs = Math.max(currentIndexs - visibleItems, 0);
            updateSliderPosition();
        });

        nextBtn.addEventListener('click', () => {
            currentIndexs = Math.min(currentIndexs + visibleItems, totalItems - visibleItems);
            updateSliderPosition();
        });
    </script>
    {/literal}
    {/if}

    <script>
        var sliderimages = '{$slider_images|escape:"javascript"}';
    </script>
    {if $slider_images != 'null' && $slider_images != ''}
    {literal}
    <script type="text/javascript">
        sliderImages = JSON.parse(sliderimages); // Parse JSON string into an array

        // Initialize the images array with default values
        const images = [
        ];

        // Add values from sliderImages to the images array
        sliderImages.forEach(image => {
            images.push(`/modules/addons/product_page/upload/${image.value}`);
        });
        let currentIndex = 0;
        const mainImage = document.getElementById("mainImage");
        const thumbnailContainer = document.getElementById("thumbnailContainer");
        let scrollIndex = 0;

        function initializeSlider() {
            mainImage.style.backgroundImage = `url(${images[0]})`;

            images.forEach((src, index) => {
                const thumbnail = document.createElement("img");
                thumbnail.src = src;
                thumbnail.classList.add("thumbnail");
                if (index === 0) thumbnail.classList.add("active");
                thumbnail.onclick = () => changeImage(index);
                thumbnailContainer.appendChild(thumbnail);
            });
        }

        function changeImage(index) {
            mainImage.style.backgroundImage = `url(${images[index]})`;
            currentIndex = index;

            document.querySelectorAll('.thumbnail').forEach((thumb, i) => {
                thumb.classList.toggle('active', i === index);
            });

            if (index >= scrollIndex + 4) {
                scrollIndex = index - 3;
            } else if (index < scrollIndex) {
                scrollIndex = index;
            }
            updateThumbnailScroll();
        }

        function nextImage(event) {
            event.stopPropagation(); // Prevents the modal from opening
            currentIndex = (currentIndex + 1) % images.length;
            changeImage(currentIndex);
        }

        function prevImage(event) {
            event.stopPropagation(); // Prevents the modal from opening
            currentIndex = (currentIndex - 1 + images.length) % images.length;
            changeImage(currentIndex);
        }

        function autoSlide() {
            nextImage(new Event("autoSlide"));
        }

        function scrollThumbnails(direction) {
            const maxIndex = images.length - 4;
            scrollIndex = Math.min(Math.max(scrollIndex + direction, 0), maxIndex);
            updateThumbnailScroll();
        }

        function updateThumbnailScroll() {
            const thumbnailHeight = 85;
            thumbnailContainer.style.transform = `translateY(-${scrollIndex * thumbnailHeight}px)`;
        }

        // Modal functions
        function openModal() {
            const modal = document.getElementById("imageModal");
            const modalImage = document.getElementById("modalImage");
            modalImage.src = images[currentIndex];
            modal.style.display = "flex";
        }

        function closeModal(event) {
            if (event.target === event.currentTarget || event.target.classList.contains("close")) {
                document.getElementById("imageModal").style.display = "none";
            }
        }

        initializeSlider();
        setInterval(autoSlide, 3000);
    </script>
    {/literal}
    {/if}
    {literal}
    <script>
        function switchTab(event, tabId) {
            const tabs = document.querySelectorAll('.tab');
            const contents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => tab.classList.remove('active'));
            contents.forEach(content => content.classList.remove('active'));

            event.target.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        }
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"
        integrity="sha512-AA1Bzp5Q0K1KanKKmvN/4d3IRKVlv9PYgwFPvm32nPO6QS8yH1HO7LbgB1pgiOxPtfeg5zEn2ba64MUcqJx6CA=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        $(document).ready(function () {
            $("#totalReviews").click(function () {
                $("#reviewtab").trigger("click");
                $('html, body').animate({
                    scrollTop: $('.tabs-container').offset().top
                }, 800); // 800 is the duration of the scroll in milliseconds
            });

            $("#editreview").click(function () {
                $("#editform").show();
                $(".thisclientreview").hide();
            });
            $("#editreview_cancel").click(function () {
                $("#editform").hide();
                $(".thisclientreview").show();
            });
            $(".btn-cancel").click(function () {
                $(".admin_reply_content").siblings('.admin_reply').children('.admin_reply_content').hide();
                $('#review_reply_form').hide(500);
                $(".admin_reply_content").show(500);
                // $(this).parent('div').parent('form').parent('.admin_reply').siblings('.addminaccess').children('.admin_replybtn').show();
                $('.admin_replybtn').show();
                $('.admin_editreplybtn').show();
            });
            $('.admin_editreplybtn').click(function () {
                $('.admin_replybtn').show();
                $('.admin_editreplybtn').show();
                var thisbtn = $(this);
                var rew_id = $(this).data('rewid');
                var clickedButton = $(this); // Store reference to clicked button
                let target = clickedButton.parent().parent().parent().parent().parent().parent('.admin_reply').siblings('.addminaccess').children('.loader');
                $(target).addClass('show');
                $(target).removeClass('hide');

                tinymce.get('review_reply_textarea').setContent('');

                $.ajax({
                    url: './index.php?m=product_page&action=getrewdata',
                    type: 'POST',
                    data: {
                        rew_id: rew_id,
                        action: 'getrewdata',
                    },
                    success: function (response) {
                        $(target).removeClass('show');
                        $(target).addClass('hide');
                        var decoded_response = JSON.parse(response);
                        if (decoded_response.status == 'success') {
                            thisbtn.hide();
                            let form = $('#review_reply_form');
                            let target = clickedButton.closest('.admin_reply');

                            if (form.length && target.length) { // Ensure both elements exist
                                form.detach().appendTo(target).show(); // Move & show the form

                                // Destroy old TinyMCE instance and reinitialize
                                tinymce.remove('#review_reply_textarea');
                                tinymce.init({
                                    selector: '#review_reply_textarea',
                                    height: 300,
                                    menubar: true,
                                    plugins: 'advlist autolink lists link image charmap print preview anchor '
                                        + 'searchreplace visualblocks code fullscreen '
                                        + 'insertdatetime media table paste code help wordcount',
                                    toolbar: 'undo redo | formatselect | bold italic underline strikethrough | forecolor backcolor | '
                                        + 'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | '
                                        + 'blockquote subscript superscript removeformat | '
                                        + 'link image media table charmap hr anchor | code fullscreen preview print',
                                });
                                $('.admin_reply_content').show();
                                target.children('.admin_reply_content').hide();

                            } else {
                                console.warn("Form or target div not found.");
                            }

                            $('#review_id_modal').val(rew_id);
                            var get_data = decoded_response.data;

                            var reply = get_data.reply;
                            if (reply !== null) {
                                tinymce.get('review_reply_textarea').setContent(cs_decodeHtml(reply));
                            }

                        } else {
                            $('.review-modal').html('<div class="alert alert-danger">Review not found</div>');
                            $(".review-modal").removeClass('hide');
                        }
                    }
                });
            })
            $('.admin_replybtn').click(function () {
                $('.admin_replybtn').show();
                $('.admin_editreplybtn').show();
                var thisbtn = $(this);
                var rew_id = $(this).data('rewid');
                var clickedButton = $(this); // Store reference to clicked button
                let target = clickedButton.closest('.addminaccess').children('.loader');
                $(target).addClass('show');
                $(target).removeClass('hide');

                tinymce.get('review_reply_textarea').setContent('');

                $.ajax({
                    url: './index.php?m=product_page&action=getrewdata',
                    type: 'POST',
                    data: {
                        rew_id: rew_id,
                        action: 'getrewdata',
                    },
                    success: function (response) {
                        $(target).removeClass('show');
                        $(target).addClass('hide');
                        var decoded_response = JSON.parse(response);
                        if (decoded_response.status == 'success') {
                            thisbtn.hide();
                            let form = $('#review_reply_form');
                            let target = clickedButton.closest('.addminaccess').siblings('.admin_reply');

                            if (form.length && target.length) { // Ensure both elements exist
                                form.detach().appendTo(target).show(); // Move & show the form

                                // Destroy old TinyMCE instance and reinitialize
                                tinymce.remove('#review_reply_textarea');
                                tinymce.init({
                                    selector: '#review_reply_textarea',
                                    height: 300,
                                    menubar: true,
                                    plugins: 'advlist autolink lists link image charmap print preview anchor '
                                        + 'searchreplace visualblocks code fullscreen '
                                        + 'insertdatetime media table paste code help wordcount',
                                    toolbar: 'undo redo | formatselect | bold italic underline strikethrough | forecolor backcolor | '
                                        + 'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | '
                                        + 'blockquote subscript superscript removeformat | '
                                        + 'link image media table charmap hr anchor | code fullscreen preview print',
                                });
                                $('.admin_reply_content').show();
                                target.children('.admin_reply_content').hide();

                            } else {
                                console.warn("Form or target div not found.");
                            }

                            $('#review_id_modal').val(rew_id);
                            var get_data = decoded_response.data;

                            var reply = get_data.reply;
                            if (reply !== null || get_data.reply !== undefined || get_data.reply !== '') {
                                tinymce.get('review_reply_textarea').setContent(cs_decodeHtml(reply));
                            }

                        } else {
                            $('.review-modal').html('<div class="alert alert-danger">Review not found</div>');
                            $(".review-modal").removeClass('hide');
                        }
                    }
                });
            });

        });
        function formatDate(date) {
            return new Date(date).toLocaleString('en-US', {
                month: 'long',    // "January"
                day: '2-digit',   // "22"
                year: 'numeric',  // "2025"
                hour: '2-digit',  // "06"
                minute: '2-digit', // "44"
                hour12: true      // "AM/PM"
            }).replace(',', ' at'); // Replace ',' with ' at' for proper formatting
        }
        function nl2br(str) {
            return str.replace(/\n/g, "<br>");
        }
        function deleteReview(id) {
            swal({
                title: "Are you sure you want delete?",
                icon: "warning",
                buttons: true, // This enables both "OK" and "Cancel" buttons
                dangerMode: true, // Optional, makes "OK" button appear in red
            })
                .then((willConfirm) => {
                    if (willConfirm) {
                        $("#review_id_to_delete").val(id);
                        $("#review_delete_form").submit();
                    }
                });
        }
        function deleteReviewReply(id) {
            swal({
                title: "Are you sure you want delete?",
                icon: "warning",
                buttons: true, // This enables both "OK" and "Cancel" buttons
                dangerMode: true, // Optional, makes "OK" button appear in red
            })
                .then((willConfirm) => {
                    if (willConfirm) {
                        $("#review_reply_id_to_delete").val(id);
                        $("#review_reply_delete_form").submit();
                    }
                });
        }

        function cs_decodeHtml(html) {
            var txt = document.createElement("textarea");
            txt.innerHTML = html;
            return txt.value;
        }
    </script>
    {/literal}

    {if $replied_review_id}
    <script>
        $(document).ready(function () {
            var asdfasdf = {$replied_review_id};
            $('html, body').animate({
                scrollTop: $('.scroll_' + asdfasdf).offset().top
            }, 800);
        })
    </script>
    {else}
    {if $forcereviewtab}
    {literal}
    <script>
        $(document).ready(function () {
            $('html, body').animate({
                scrollTop: $('.tabs-container').offset().top
            }, 1000); // 800 is the duration of the scroll in milliseconds
        });
    </script>
    {/literal}
    {/if}
    {/if}