<ul>
    {foreach from=$content.backups item="project"}
        <li data-index="{$project.index}" data-name="{$project.name}">{$project.name} ({$project.count})</li>
    {/foreach}
</ul>