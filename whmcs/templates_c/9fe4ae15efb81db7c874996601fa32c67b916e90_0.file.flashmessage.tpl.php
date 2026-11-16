<?php
/* Smarty version 3.1.48, created on 2026-10-30 21:54:11
  from 'E:\xampp\htdocs\rackmonk_wordpress\whmcs\templates\twenty-one\includes\flashmessage.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.48',
  'unifunc' => 'content_6ae503f3c21137_18518430',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '9fe4ae15efb81db7c874996601fa32c67b916e90' => 
    array (
      0 => 'E:\\xampp\\htdocs\\rackmonk_wordpress\\whmcs\\templates\\twenty-one\\includes\\flashmessage.tpl',
      1 => 1793220069,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6ae503f3c21137_18518430 (Smarty_Internal_Template $_smarty_tpl) {
$_prefixVariable1 = get_flash_message();
$_smarty_tpl->_assignInScope('message', $_prefixVariable1);
if ($_prefixVariable1) {?>
    <div class="alert alert-<?php if ($_smarty_tpl->tpl_vars['message']->value['type'] == "error") {?>danger<?php } elseif ($_smarty_tpl->tpl_vars['message']->value['type'] == 'success') {?>success<?php } elseif ($_smarty_tpl->tpl_vars['message']->value['type'] == 'warning') {?>warning<?php } else { ?>info<?php }
if ((isset($_smarty_tpl->tpl_vars['align']->value))) {?> text-<?php echo $_smarty_tpl->tpl_vars['align']->value;
}?>">
        <?php echo $_smarty_tpl->tpl_vars['message']->value['text'];?>

    </div>
<?php }
}
}
