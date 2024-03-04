{if !$request_async}{include file="includes/head.tpl"}{/if}

<div class="backdrop">
</div>
<div class="modal start hidden">
    <div class="steps">
        <div class="step">
            <span class="number local_folder">1</span>
            <label>Dossier de travail</label>
            <div class="button">
                <label for="file_select" class="material-symbols-outlined">folder_open</label>
                <input type="file" id="file_select" name="file_select"/>
                <input name="local_folder" placeholder="Dossier" type="text" autocomplete="off" value="{$content.local_folder}">
            </div>
        </div>
        <div class="step">
            <span class="number ftp">2</span>
            <label>Serveur FTP</label>
            <div class="form">
                <input name="host" placeholder="Host" type="text" autocomplete="off" value="{$content.ftp.host}">
                <input name="folder" placeholder="Dossier distant" type="text" autocomplete="off" value="{$content.ftp.folder}">
                <input name="user" placeholder="User" type="text" autocomplete="off" value="{$content.ftp.user}">
                <input name="pass" placeholder="Password" type="password" autocomplete="off" value="{$content.ftp.pass}">
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

<div class="modal file hidden">
    <header><h2>../vidal-fr<span>master</span></h2> <span class="button"><span class="material-symbols-outlined">check</span>Valider la sélection</span></header>
    <div class="body">
        <div class="loading_message">
            <div>Chargement</div>
            <div><span>0</span> %</div>
        </div>
    </div>
</div>

<div class="modal comparison hidden">
    <header>
        <span class="button return"><span class="material-symbols-outlined">arrow_back_ios</span>Retour à la sélection</span>
        <span class="button"><span class="material-symbols-outlined">cloud_upload</span>Uploader</span>
    </header>
    <div class="body">

    </div>
</div>

{if !$request_async}{include file="includes/footer.tpl"}{/if}