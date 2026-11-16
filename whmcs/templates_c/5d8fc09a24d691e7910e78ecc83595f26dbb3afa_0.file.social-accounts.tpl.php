<?php
/* Smarty version 3.1.48, created on 2026-10-30 19:29:43
  from 'E:\xampp\htdocs\rackmonk_wordpress\whmcs\templates\twenty-one\includes\social-accounts.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.48',
  'unifunc' => 'content_6ae4e217b6c999_60988215',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '5d8fc09a24d691e7910e78ecc83595f26dbb3afa' => 
    array (
      0 => 'E:\\xampp\\htdocs\\rackmonk_wordpress\\whmcs\\templates\\twenty-one\\includes\\social-accounts.tpl',
      1 => 1793220069,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6ae4e217b6c999_60988215 (Smarty_Internal_Template $_smarty_tpl) {
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['socialAccounts']->value, 'account');
$_smarty_tpl->tpl_vars['account']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['account']->value) {
$_smarty_tpl->tpl_vars['account']->do_else = false;
?>
    <li class="list-inline-item">
        <a class="btn btn-icon mb-1" href="<?php echo $_smarty_tpl->tpl_vars['account']->value->getUrl();?>
" target="_blank">
            <i class="<?php echo $_smarty_tpl->tpl_vars['account']->value->getFontAwesomeIcon();?>
"></i>
        </a>
    </li>
<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);
}
}
