<div class="comparisons">
{foreach from=$content.comparisons item="entry"}
    <div class="filename">{$entry.file}<div>{if !$entry.isBinary}<span class="additions">+{$entry.comparison->getTotalAdditions()}</span><span class="deletions">-{$entry.comparison->getTotalDeletions()}</span>{/if}<span class="upload"><input type="checkbox" {if !$entry.comparison->identicals}checked{/if} id="file_{$key}" value="{$entry.file}"><label for="file_{$key}">Uploader</label></span></div></div>
    {if $entry.comparison->identicals}
        <div class="identicals">
            Fichiers identiques
        </div>
    {else}
        {if !$entry.isBinary}
            <div class="comparison">
                <div class="file">
                    {$entry.comparison->formatOriginal()}
                </div>
                <div class="file">
                    {$entry.comparison->formatNew()}
                </div>
            </div>
        {else}
            <div class="binary">
                Fichiers binaires différents
            </div>
        {/if}
    {/if}
{/foreach}
</div>