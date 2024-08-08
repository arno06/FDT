<ul>
    {foreach from=$content.files item="file"}
        <li title="{$file.filename}">{$file.name}</li>
        {foreachelse}
            <li class="empty">Aucun fichier sauvegardé</li>
    {/foreach}
</ul>