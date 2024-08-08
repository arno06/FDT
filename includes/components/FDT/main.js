(()=>{

    const GIT_CHECK_FILE_COUNT = 125;

    const GIT_CHECK_CONCURRENT_CALLS = 4;

    let checkGitStatus = false;
    let project_files;
    let environments = [];
    let selected_files = [];

    function init(){
        if(window.envs){
            setEnvironments(window.envs);
        }
        displayModal('project');
        document.querySelector('.go').addEventListener('click', startHandler);
        document.querySelector('.comparison.modal header .button.return').addEventListener('click', ()=>displayModal('selection'));
        document.querySelectorAll('.breadcrumb li').forEach((pElement)=>{
            pElement.addEventListener('click', (e)=>{
                if(!e.currentTarget.getAttribute("rel")){
                    return;
                }
                displayModal(e.currentTarget.getAttribute("rel"));
            });
        });

        document.querySelector('.modal.selection header .button.select_files').addEventListener('click', ()=>{
            if(!selected_files.length){
                return;
            }

            displayModal('comparison');
            retrieveFileComparison();
        });
        document.querySelector('.modal.selection header .button.refresh_files').addEventListener('click', listFileHandler);

        document.querySelector('.modal.comparison .upload_action').addEventListener('click', deployHandler);
        document.querySelector('.modal.comparison .refresh_comparisons').addEventListener('click', retrieveFileComparison);

        document.querySelector('.modal.backups .reload_backups').addEventListener('click', reloadBackups);
        reloadBackups();
    }

    function setEnvironments(pEnvs){
        environments = pEnvs;
        if(!environments.length){
            return;
        }
        let parent = document.createElement('div');
        parent.classList.add('envs');
        let select = document.createElement('select');
        parent.appendChild(select);
        document.querySelector('.modal.project').insertBefore(parent, document.querySelector('.modal.project .steps'));
        let empty = document.createElement('option');
        empty.value = "none";
        select.appendChild(empty);
        environments.forEach((pEnv, pIdx)=>{
            let option = document.createElement('option');
            option.value = pIdx;
            option.innerHTML = pEnv.name;
            select.appendChild(option);
        });
        select.addEventListener('change', (e)=>{
            if(e.currentTarget.value === "none"){
                return;
            }
            let env = environments[Number(e.currentTarget.value)];
            if(!env){
                return;
            }
            project_files = [];
            document.querySelector('input[name="local_folder"]').value = env.local_folder;
            document.querySelector('input[name="domain"]').value = env.domain;
            document.querySelector('input[name="host"]').value = env.ftp.host;
            document.querySelector('input[name="user"]').value = env.ftp.user;
            document.querySelector('input[name="pass"]').value = env.ftp.pass;
            document.querySelector('input[name="folder"]').value = env.ftp.folder;
            document.querySelector('input[name="name"]').value = env.name;
            checkGitStatus = env.checkgit||false;
            if(checkGitStatus){
                document.querySelector('input[name="checkgit"]').setAttribute("checked", "checked");
            }else{
                document.querySelector('input[name="checkgit"]').removeAttribute("checked");
            }
        });
    }

    function startHandler(){
        let startingModalActions = document.querySelector('.modal.project .actions');
        startingModalActions.classList.toggle('loading');

        serverPromise('check/local-folder', ['local_folder']).then((pResult)=>{
            let checkFolder = pResult?.content;

            let number_local = document.querySelector('.local_folder.number');
            let c = !checkFolder? 'close':'done';
            number_local.classList.remove('close', 'done');
            number_local.classList.add(c);
            number_local.innerHTML = '1<span class="material-symbols-outlined">'+c+'</span>';

            serverPromise('check/ftp', ['host', 'user', 'pass', 'folder']).then((pResult)=>{
                let checkFTP = pResult?.content;

                let number_ftp = document.querySelector('.ftp.number');

                c = !checkFTP ? 'close':'done';
                number_ftp.classList.remove('close', 'done');
                number_ftp.classList.add(c);
                number_ftp.innerHTML = '2<span class="material-symbols-outlined">'+c+'</span>';

                if(checkFolder && checkFTP){
                    document.querySelector('.modal.selection header h2').innerHTML = document.querySelector('input[name="local_folder"]').value+'<span>'+checkFolder.branch+'</span>';
                    selected_files = [];
                    project_files = [];
                    checkGitStatus = !!document.querySelector('input[name="checkgit"]').checked;
                    listFileHandler();
                    setTimeout(()=>{
                        displayModal('selection');
                        startingModalActions.classList.toggle('loading');
                    }, 500);
                }
            });
        });
    }

    function listFileHandler(){
        let body = document.querySelector('.modal.selection .body');
        body.innerHTML = '<div class="loading_message">Chargement</div>';
        serverPromise('list/local-files', ['local_folder']).then(async (pResult)=>{

            project_files = pResult.content.files;

            body.innerHTML = `<div class="form"><div class="search"><input type="search" name="search" placeholder="Filtrer" autocomplete="off" spellcheck="false"></div><div class="unversioned"><input type="checkbox" checked disabled name="unversioned" id="unversioned_files"/><label for="unversioned_files">Par date de modification locale</label></div></div>
<div class="list">
</div>`;

            document.querySelector('#unversioned_files').addEventListener('change', ()=>renderFiles());

            let to = null;
            const changeSearchHandler = ()=>{
                if(to){
                    clearTimeout(to);
                }
                to = setTimeout(renderFiles, 100);
            };
            document.querySelector('input[name="search"]').addEventListener('change', changeSearchHandler);
            document.querySelector('input[name="search"]').addEventListener('search', changeSearchHandler);
            document.querySelector('input[name="search"]').addEventListener('keyup', changeSearchHandler);

            renderFiles();

            if(!checkGitStatus){
                return;
            }
            getGitInfos().then((pFiles)=>{
                project_files = pFiles;
                renderFiles();
            });
        });
    }

    let gitFiles;
    function getGitInfos(){
        document.querySelector('.unversioned')?.classList.add('loading');
        gitFiles = [];
        return new Promise(async (pResolve)=>{
            const fileCount = project_files.length;
            const maxCalls = Math.ceil(fileCount / GIT_CHECK_FILE_COUNT);
            const maxPool = Math.ceil(maxCalls / GIT_CHECK_CONCURRENT_CALLS);
            let promises = [];
            for(let p = 0; p<GIT_CHECK_CONCURRENT_CALLS; p++){
                let f = [];
                for(let c = 0; c<maxPool; c++){
                    let idx = p + (c * GIT_CHECK_CONCURRENT_CALLS);
                    f.push(project_files.slice(idx * GIT_CHECK_FILE_COUNT, Math.min((idx * GIT_CHECK_FILE_COUNT)+GIT_CHECK_FILE_COUNT, fileCount)));
                }
                promises.push(poolHandler(f));
            }
            Promise.all(promises).then(()=>{
                document.querySelector('#unversioned_files').removeAttribute('disabled');
                document.querySelector('.unversioned.loading')?.classList.remove('loading');
                pResolve(gitFiles);
            });
        });
    }

    function poolHandler(pCallsParams){
        let local_folder = document.querySelector('input[name="local_folder"]').value;
        let params = pCallsParams.shift();
        if(!params.length){
            return true;
        }
        return serverPromise('retrieve/updated-git-info', {files:params, local_folder:local_folder}).then((pResult)=>{
            if(pResult?.content?.files){
                gitFiles = gitFiles.concat(pResult.content.files);
            }
            if(pCallsParams.length === 0){
                return true;
            }
            return poolHandler(pCallsParams);
        });
    }

    function renderFiles(){
        let body = document.querySelector('.modal.selection .body');

        let perDays = [];
        let lastDate = null;

        let unversionedFiles = document.querySelector('#unversioned_files')?document.querySelector('#unversioned_files').checked:false;
        let filteredSearch = document.querySelector('input[name="search"]')&&document.querySelector('input[name="search"]').value.length>3?document.querySelector('input[name="search"]').value:false;

        let propTS = unversionedFiles?'last_m_time':'timestamp';

        project_files.filter((pFile)=>{

            if (filteredSearch !== false && pFile.filename.indexOf(filteredSearch) === -1){
                return false;
            }

            if(unversionedFiles){
                return true;
            }
            return pFile.timestamp&&pFile.timestamp !== 'false';
        }).sort((pA, pB)=>{
            const a = Number(pA[propTS]);
            const b = Number(pB[propTS]);
            if(a === b){
                return pA.filename.localeCompare(pB.filename);
            }
            return a>b?-1:1;
        }).map((pFile)=>{
            pFile.date = new Date();
            pFile.date.setTime(Number(pFile[propTS]) * 1000);
            let h = pFile.date.getHours();
            let m = pFile.date.getMinutes();
            if(h<10){
                h = "0"+h;
            }
            if(m<10){
                m = "0"+m;
            }
            pFile.hourString = h+":"+m;
            pFile.dateString = pFile.date.toLocaleDateString('FR-fr', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            });
            pFile.file = pFile.filename.split('/').pop();
            if(!lastDate || lastDate !== pFile.dateString){
                perDays.push([]);
            }
            lastDate = pFile.dateString;
            perDays[perDays.length-1].push(pFile);
            return pFile;
        });

        body.querySelector('.list').innerHTML = perDays.reduce((pHTML, pFiles)=>{
            return pHTML + '<div class="sublist"><div class="title">'+pFiles[0].dateString+'</div>'+pFiles.reduce((pHTMLList, pFile)=>{
                let id = pFile.filename.replace(/([.\/])/g, '_');
                return pHTMLList + '<div class="day"><div><input id="'+id+'" type="checkbox" value="'+pFile.filename+'"'+(selected_files.includes(pFile.filename)?' checked':'')+'></div><label for="'+id+'" class="name" title="'+pFile.filename+'">'+pFile.file+'</label><div class="hours">'+pFile.hourString+'</div></div>';
            }, "")+'</div>';
        }, "");

        body.querySelectorAll('.list .sublist input[type="checkbox"]').forEach((pInput)=>{
            pInput.addEventListener('change', (e)=>{
                if(e.currentTarget.checked){
                    selected_files.push(e.currentTarget.value);
                }else{
                    selected_files = selected_files.filter((pVal)=>pVal !== e.currentTarget.value);
                }
                document.querySelector('.modal.selection header .button .count').innerHTML = '('+selected_files.length+')';
            });
        });
    }

    function retrieveFileComparison(){
        document.querySelector('.modal.comparison .body').innerHTML = '<div class="loading_message">Chargement</div>';
        let params = extractParams(['local_folder', 'host', 'user', 'pass', 'folder']);
        params.files = selected_files;
        serverPromise('retrieve/files-comparison?render=true', params).then((pContent)=>{
            document.querySelector('.modal.comparison .body').innerHTML = pContent.html||"Une erreur est apparue";
        });
    }

    function deployHandler(){
        let files = Array.from(document.querySelectorAll('.modal.comparison .upload input[type="checkbox"]:checked')).map((pInput)=>pInput.value);

        if(!files.length){
            return;
        }

        displayModal('deployment');

        let params = extractParams(['local_folder', 'host', 'user', 'pass', 'folder', 'domain', 'name']);
        params.files = files;

        setStep({backup:{remove:"done"},upload:{remove:"done"},compare:{remove:"done"},opcache:{remove:"done"}});
        setStep({backup:{remove:"error"},upload:{remove:"error"},compare:{remove:"error"},opcache:{remove:"error"}});
        setStep({backup:{add:"waiting"},upload:{add:"waiting"},compare:{add:"waiting"},opcache:{add:"waiting"}});

        currentStep = -1;
        nextStep(params);
    }

    function setStep(pSteps){
        for(let i in pSteps){
            if(!pSteps.hasOwnProperty(i)){
                continue;
            }
            let actions = pSteps[i];
            for(let j in actions){
                if(!actions.hasOwnProperty(j)){
                    continue;
                }
                document.querySelector('.modal.deployment .body .steps .'+i).classList[j](actions[j]);
            }
        }
    }

    let deploymentSteps = [
        {
            name:"backup",
            url:"ftp/backup",
            checkError:(pResponse)=>!!pResponse.content.failed_files?.length
        },
        {
            name:"upload",
            url:"upload/files",
            checkError:(pResponse)=>!!pResponse.content.failed_files?.length
        },
        {
            name:"compare",
            url:"retrieve/files-comparison",
            checkError:(pResponse)=>pResponse.content.identicals!=="1"
        },
        {
            name:"opcache",
            url:"invalidate/op-cache",
            checkError:(pResponse)=>false
        }
    ];

    function reloadBackups(){
        document.querySelector('.modal.backups .body .backups').innerHTML = "";
        document.querySelector('.modal.backups .body .files').innerHTML = "";
        serverPromise('retrieve/saved-projects?render=true').then((pContent)=>{
            document.querySelector('.modal.backups .body .projects').innerHTML = pContent.html||"Une erreur est apparue";
            document.querySelectorAll('.modal.backups .body .projects ul li').forEach((pEl)=>{
                pEl.addEventListener('click', loadBackupList);
            });
        });
    }

    function loadBackupList(e){
        e.currentTarget.parentNode.querySelector('.current')?.classList.remove("current");
        e.currentTarget.classList.add('current');
        document.querySelector('.modal.backups .body .files').innerHTML = "";
        serverPromise('retrieve/backups?render=true&project='+e.currentTarget.getAttribute('data-name')).then((pContent)=>{
            document.querySelector('.modal.backups .body .backups').innerHTML = pContent.html||"Une erreur est apparue";
            document.querySelectorAll('.modal.backups .body .backups ul li').forEach((pEl)=>{
                pEl.addEventListener('click', loadBackup);
            });
        });
    }

    function loadBackup(e){
        e.currentTarget.parentNode.querySelector('.current')?.classList.remove("current");
        e.currentTarget.classList.add('current');
        serverPromise('retrieve/backup?render=true&backup='+e.currentTarget.getAttribute('data-name')+'&project='+e.currentTarget.getAttribute('data-project')).then((pContent)=>{
            document.querySelector('.modal.backups .body .files').innerHTML = pContent.html||"Une erreur est apparue";
        });
    }

    let currentStep = -1;
    function nextStep(pParams){
        let classes = {};
        if(currentStep > -1){
            let previous = deploymentSteps[currentStep];
            classes[previous.name] = {remove:"current",add:"done"};
        }
        let current = deploymentSteps[++currentStep];
        if(current){
            classes[current.name] = {add:"current",remove:"waiting"};
        }
        setStep(classes);

        if(!current){
            reloadBackups();
            console.log("finished");
            return;
        }
        serverPromise(current.url, pParams).then((pResponse)=>{
            if(!pResponse || current.checkError(pResponse)){
                classes = {};
                classes[current.name] = {remove:"current", add:"error"};
                setStep(classes);
                console.log(pResponse);
                return;
            }
            nextStep(pParams);
        });
    }

    function serverPromise(pUrl, pParams){
        return new Promise((pResolve)=>{
            Request.load(pUrl, extractParams(pParams)).onComplete((e)=>{
                if(!e.currentTarget.responseJSON || e.currentTarget.responseJSON.content.error){
                    pResolve(null);
                    return;
                }
                pResolve(e.currentTarget.responseJSON);
            });
        });
    }

    function displayModal(pModal){
        document.querySelector('.modal:not(.hidden)')?.classList.add('hidden');
        document.querySelector('.modal.'+pModal).classList.remove('hidden');
        document.querySelector('.breadcrumb li.current')?.classList.remove('current');
        document.querySelector('.breadcrumb li[rel="'+pModal+'"]')?.classList.add('current');
    }

    function extractParams(pParamsName){
        if(!Array.isArray(pParamsName)){
            return pParamsName;
        }
        let selectors = pParamsName.map((pName)=>'input[name="'+pName+'"]').join(',');
        return Array.from(document.querySelectorAll(selectors)).reduce((pVal, pInput)=>{
            pVal[pInput.name] = pInput.value;
            return pVal;
        }, {});
    }

    window.addEventListener('DOMContentLoaded', init);
})();