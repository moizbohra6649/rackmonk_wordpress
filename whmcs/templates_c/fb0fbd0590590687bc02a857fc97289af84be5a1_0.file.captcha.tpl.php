<?php
/* Smarty version 3.1.48, created on 2026-10-30 19:29:43
  from 'E:\xampp\htdocs\rackmonk_wordpress\whmcs\templates\twenty-one\includes\captcha.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.48',
  'unifunc' => 'content_6ae4e217348cd0_18378104',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'fb0fbd0590590687bc02a857fc97289af84be5a1' => 
    array (
      0 => 'E:\\xampp\\htdocs\\rackmonk_wordpress\\whmcs\\templates\\twenty-one\\includes\\captcha.tpl',
      1 => 1793220069,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6ae4e217348cd0_18378104 (Smarty_Internal_Template $_smarty_tpl) {
if ($_smarty_tpl->tpl_vars['captcha']->value->isEnabled() && $_smarty_tpl->tpl_vars['captcha']->value->isEnabledForForm($_smarty_tpl->tpl_vars['captchaForm']->value)) {?>
    <div class="text-center<?php if ($_smarty_tpl->tpl_vars['containerClass']->value) {
echo $_smarty_tpl->tpl_vars['containerClass']->value;
} else { ?> row justify-content-center<?php }?>">
        <?php if ($_smarty_tpl->tpl_vars['templatefile']->value == 'homepage') {?>
            <div class="domainchecker-homepage-captcha">
        <?php }?>

        <?php if ($_smarty_tpl->tpl_vars['captcha']->value->recaptcha->isEnabled() && !$_smarty_tpl->tpl_vars['captcha']->value->recaptcha->isInvisible()) {?>
            <div id="google-recaptcha-domainchecker" class="form-group recaptcha-container mx-auto" data-action="<?php echo $_smarty_tpl->tpl_vars['captchaForm']->value;?>
"></div>
        <?php } elseif (!$_smarty_tpl->tpl_vars['captcha']->value->recaptcha->isEnabled()) {?>
            <div class="col-md-8 mx-auto mb-3 mb-sm-0">
                <div id="default-captcha-domainchecker" class="<?php if ($_smarty_tpl->tpl_vars['filename']->value == 'domainchecker') {?>input-group input-group-box <?php }?>text-center row pb-3">
                    <p><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>"captchaverify"),$_smarty_tpl ) );?>
</p>

                    <div class="col-6 captchaimage">
                        <img id="inputCaptchaImage" data-src="<?php echo $_smarty_tpl->tpl_vars['systemurl']->value;?>
includes/verifyimage.php" src="<?php echo $_smarty_tpl->tpl_vars['systemurl']->value;?>
includes/verifyimage.php" align="middle" />
                    </div>

                    <div class="col-6">
                        <input id="inputCaptcha" type="text" name="code" maxlength="6" class="form-control <?php if ($_smarty_tpl->tpl_vars['filename']->value == 'register') {?>float-left<?php }?>"
                               data-toggle="tooltip" data-placement="right" data-trigger="manual" title="<?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'orderForm.required'),$_smarty_tpl ) );?>
"/>
                    </div>
                </div>
            </div>
        <?php }?>

        <?php if ($_smarty_tpl->tpl_vars['templatefile']->value == 'homepage') {?>
            </div>
        <?php }?>
    </div>
<?php }
}
}
