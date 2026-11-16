{$userFullname}
<ul>
{for $foo=1 to 3}
    <li>{$foo}</li>
{/for}
</ul>
<ul>
{for $x = 14 to 50 step 4}
  <li style="color:red;">{$x}</li>
{/for}
</ul>
<div style="color:black; background : yellow; text-align:center; padding:20px;"> 
Hello wold 
</div>
{block name="header"}
  <h1>Default Header</h1>
 {/block}

{block name="content"}
    <p>Default Content</p>
{/block}

{block name="footer"}
  <footer>Default Footer</footer>
{/block}
<ul>
{for $foo=1 to 10}
    <li style="color:green;">{$foo}</li>
{/for}
</ul>