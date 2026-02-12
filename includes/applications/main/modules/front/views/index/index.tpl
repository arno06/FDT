{if !$request_async}{include file="includes/head.tpl"}{/if}

<header>
    <div>
        <img src="includes/components/FDT/imgs/icon-192.png" alt="FDT" width="48">
        <span>FTP Deployment Tool</span>
    </div>

    <ul class="breadcrumb">
        <li rel="project" class="current">Projet</li>
        <li><span class="material-symbols-outlined">chevron_right</span></li>
        <li rel="selection">Sélection</li>
        <li><span class="material-symbols-outlined">chevron_right</span></li>
        <li rel="comparison">Comparaison</li>
        <li><span class="material-symbols-outlined">chevron_right</span></li>
        <li rel="deployment">Déploiement</li>
        <li><span class="material-symbols-outlined">more_horiz</span></li>
        <li rel="backups">Backups</li>
    </ul>

    <span class="selected_project"></span>
</header>

<div class="content">
    <div class="modal project hidden">
        <div class="projects">
            {foreach from=$content.environments item="env"}
                <div data-id="{$key}"><label>{$env.name}</label>
                    {if $env.tags}
                        <div class="tags">
                            {foreach from=$env.tags item="tag"}
                                <span class="{$tag}">{$tag}</span>
                            {/foreach}
                        </div>
                    {/if}
                </div>
            {/foreach}
        </div>
        <div class="steps">
            <div class="step">
                <span class="number local_folder">1</span>
                <label>Dossier de travail</label>
                <div class="form">
                    <input name="name" type="hidden">
                    <div class="button">
                        <label for="file_select" class="material-symbols-outlined">folder_open</label>
                        <input type="file" id="file_select" name="file_select"/>
                        <input name="local_folder" placeholder="Dossier" type="text" autocomplete="off">
                    </div>
                    <div class="domain">
                        <input name="domain" placeholder="https://..." type="text" autocomplete="off">
                    </div>
                    <div class="checkgit">
                        <input name="checkgit" type="checkbox" id="checkgit"><label for="checkgit">Vérifier la date de MAJ Git</label>
                    </div>
                </div>
            </div>
            <div class="step">
                <span class="number ftp">2</span>
                <label>Serveur FTP</label>
                <div class="form">
                    <input name="host" placeholder="Host" type="text" autocomplete="off">
                    <input name="folder" placeholder="Dossier distant" type="text" autocomplete="off">
                    <input name="user" placeholder="User" type="text" autocomplete="off">
                    <input name="pass" placeholder="Password" type="password" autocomplete="off">
                </div>
            </div>
        </div>
        <div class="actions">
        <span class="go">
            <span class="material-symbols-outlined">cloud_done</span>
            Commencer
        </span>
        </div>
    </div>

    <div class="modal selection hidden">
        <header><h2>folder<span>branch</span></h2> <div class="actions"><span class="button material-symbols-outlined refresh_files" title="Recharger la liste des fichiers">refresh</span><span class="button select_files"><span class="material-symbols-outlined">check</span>Valider la sélection<span class="count"></span></span></div></header>
        <div class="body">
            <div class="loading_message">
                <div>Chargement des fichiers</div>
            </div>
        </div>
    </div>

    <div class="modal comparison hidden">
        <header>
            <span class="button return"><span class="material-symbols-outlined">arrow_back_ios</span>Retour à la sélection</span>
            <div class="actions">
                <span class="button material-symbols-outlined refresh_comparisons" title="Recharger les comparaisons">refresh</span>
                <span class="button upload_action"><span class="material-symbols-outlined">cloud_upload</span>Déployer</span>
            </div>
        </header>
        <div class="body">

        </div>
    </div>

    <div class="modal deployment hidden">
        <div class="body">
            <div class="loading_message">
                <div class="steps">
                    <div class="backup waiting"><span class="error material-symbols-outlined">close</span><span class="done material-symbols-outlined">done</span><span class="current material-symbols-outlined">chevron_right</span>Sauvegarde</div>
                    <div class="upload waiting"><span class="error material-symbols-outlined">close</span><span class="done material-symbols-outlined">done</span><span class="current material-symbols-outlined">chevron_right</span>Upload</div>
                    <div class="compare waiting"><span class="error material-symbols-outlined">close</span><span class="done material-symbols-outlined">done</span><span class="current material-symbols-outlined">chevron_right</span>Comparaison</div>
                    <div class="opcache waiting"><span class="error material-symbols-outlined">close</span><span class="done material-symbols-outlined">done</span><span class="current material-symbols-outlined">chevron_right</span>OPCache</div>
                </div>
            </div>
        </div>
        <div class="actions">
            <span class="button"><span class="material-symbols-outlined">undo</span>Rollback</span>
        </div>
    </div>

    <div class="modal backups hidden">
        <div class="body">
            <div class="projects">

            </div>
            <div class="backups">

            </div>
            <div class="files">

            </div>
        </div>
    </div>
</div>
{if $content.envs}
    <script>
        window.envs = {$content.envs};
    </script>
{/if}
{if !$request_async}{include file="includes/footer.tpl"}{/if}