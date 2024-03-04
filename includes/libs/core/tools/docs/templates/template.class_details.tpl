{include file="includes/template.head.tpl"}
<div class="intro">

    <h1>{$details.name}<span>{$details.package}</span></h1>
    <div class="about">
        {if !empty($details.details.date)}<div class="date">Date : {$details.details.date}</div>{/if}
        {if !empty($details.details.version)}<div class="version">Version : {$details.details.version}</div>{/if}
    </div>
    <div class="description">
        {implode separator="<br/>" data=$details.details.description}
    </div>
    {foreach from=$details.properties item="prop"}
        <div class="property" id="prop_{$prop.name}">
            <h2>${$prop.name}</h2>
            <span class="scope">{if $prop.protected}protected{/if}{if $prop.public}public{/if}</span>
            <span class="type">&lt;{$prop.details.type}&gt;</span>
            <div class="description">{implode separator="<br/>" data=$prop.details.description}</div>
            {if $prop.value && !empty($prop.value)}<pre>{$prop.value}</pre>{/if}
        </div>
    {/foreach}
    {foreach from=$details.methods item="method"}
        <div class="method" id="method_{$method.name}">
            <h2>{$method.name}</h2>
            <span class="scope">{if $method.protected}protected{/if}{if $method.public}public{/if}</span>
            <div class="description">{implode separator="<br/>" data=$method.details.description}</div>
            <div class="call">
                <h3>Appel</h3>
                <pre class="php">&dollar;instance->{$method.name}(...)</pre>
                <div class="parameters">
                    <table>
                        {foreach from=$method.details.parameters item="param"}
                            <tr>
                                <td class="varname"><span>${$param.name}</span></td>
                                <td class="vartype"><span>{$param.type}</span></td>
                                <td class="vardesc">{$param.desc}</td>
                            </tr>
                        {/foreach}
                    </table>
                </div>
            </div>
            <div class="return">
                <h3>Réponse</h3>
                <div class="vartype"><span>{if $method.details.return.type && $method.details.return.type != false}{$method.details.return.type}{else}void{/if}</span></div>
            </div>
        </div>
    {/foreach}
</div>
{include file="includes/template.footer.tpl"}