<header class="actions">
    <span class="button compare_backup"><span class="material-symbols-outlined">difference</span>Comparer</span>
</header>
<ul>
    {foreach from=$content.files item="file"}
        <li title="{$file.filename}">{$file.name}</li>
        {foreachelse}
            <li class="empty">Aucun fichier sauvegardé</li>
    {/foreach}
</ul>