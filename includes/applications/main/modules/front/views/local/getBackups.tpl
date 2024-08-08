<ul>
    {foreach from=$content.backups item="backup"}
        <li data-name="{$backup.name}" data-project="{$backup.project}">{$backup.date}</li>
    {/foreach}
</ul>