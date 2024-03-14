<div class="comparisons">
{foreach from=$content.comparisons item="entry"}
    <div class="filename">{$entry.file}<div><span class="additions">+{$entry.comparison->getTotalAdditions()}</span><span class="deletions">-{$entry.comparison->getTotalDeletions()}</span><span class="upload"><input type="checkbox" {if $entry.comparison->getTotalAdditions()!=0||$entry.comparison->getTotalDeletions()!=0}checked{/if} id="file_{$key}" value="{$entry.file}"><label for="file_{$key}">Uploader</label></span></div></div>
    {if $entry.comparison->getTotalAdditions()==0&&$entry.comparison->getTotalDeletions()==0}
        <div class="identicals">
            Fichiers identiques
        </div>
    {else}
        <div class="comparison">
            <div class="file">
                {$entry.comparison->formatOriginal()}
            </div>
            <div class="file">
                {$entry.comparison->formatNew()}
            </div>
        </div>
    {/if}
{/foreach}
</div>