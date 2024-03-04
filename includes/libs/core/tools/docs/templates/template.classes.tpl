{include file="includes/template.head.tpl"}
<h3>Classes</h3>
<div class="index">
    {foreach from=$classIndex item="byLetter" key="key"}
        <div class="letter">
            <h4>{$key}</h4>
            <ul>
                {foreach from=$byLetter item="className"}
                    <li><a href="{$className.href}">{$className.name}</a></li>
                {/foreach}
            </ul>
        </div>
    {/foreach}
</div>
{include file="includes/template.footer.tpl"}