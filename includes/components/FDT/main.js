(()=>{

    const GIT_CHECK_FILE_COUNT = 125;

    const GIT_CHECK_CONCURRENT_CALLS = 4;

    let project_files;
    let environments = [];

    function init(){
        if(window.envs){
            setEnvironments(window.envs);
        }
        displayModal('start');
        document.querySelector('.go').addEventListener('click', startHandler);
        document.querySelector('.comparison.modal header .button.return').addEventListener('click', ()=>displayModal('file'));
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
        document.querySelector('.modal.start').insertBefore(parent, document.querySelector('.modal.start .steps'));
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
        });
    }

    function startHandler(e){
        let startingModalActions = document.querySelector('.start.modal .actions');
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

                console.log(checkFTP);

                let number_ftp = document.querySelector('.ftp.number');

                c = !checkFTP ? 'close':'done';
                number_ftp.classList.remove('close', 'done');
                number_ftp.classList.add(c);
                number_ftp.innerHTML = '2<span class="material-symbols-outlined">'+c+'</span>';

                startingModalActions.classList.toggle('loading');
                if(checkFolder && checkFTP){
                    document.querySelector('.file header h2').innerHTML = document.querySelector('input[name="local_folder"]').value+'<span>'+checkFolder.branch+'</span>';
                    startingModalActions.innerHTML = '';
                    fileSelectStep();
                }
            });
        });
    }

    function fileSelectStep(){
        setTimeout(()=>{
            displayModal('file');
            listFileHandler();
        }, 500);
    }

    function listFileHandler(){
        serverPromise('list/local-files', ['local_folder']).then(async (pResult)=>{

            project_files = pResult.content.files;

            let body = document.querySelector('.file.modal .body');
            body.innerHTML = `<div class="form"><div class="search"><input type="search" name="search" placeholder="Filtrer"></div><div class="unversioned loading"><input type="checkbox" checked disabled name="unversioned" id="unversioned_files"/><label for="unversioned_files">Par date de modification locale</label></div></div>
<div class="list">
</div>`;

            document.querySelector('#unversioned_files').addEventListener('change', (e)=>renderFiles());

            let to = null;
            const changeSearchHandler = (e)=>{
                if(to){
                    clearTimeout(to);
                }
                let value = e.currentTarget.value;
                to = setTimeout(renderFiles, 100);
            };
            document.querySelector('input[name="search"]').addEventListener('change', changeSearchHandler);
            document.querySelector('input[name="search"]').addEventListener('keyup', changeSearchHandler);

            document.querySelector('.modal.file header .button').addEventListener('click', (e)=>{
                let selectedFiles = Array.from(document.querySelectorAll('.modal.file .list input[type="checkbox"]:checked')).map((pInput)=>pInput.value);

                if(!selectedFiles.length){
                    return;
                }

                displayModal('comparison');
                retrieveFileComparison(selectedFiles);
            });

            renderFiles();

            getGitInfos().then((pFiles)=>{
                document.querySelector('#unversioned_files').removeAttribute('disabled');
                document.querySelector('.unversioned.loading')?.classList.remove('loading');
                project_files = pFiles;
                renderFiles();
            });
        });
    }

    let gitFiles;
    function getGitInfos(){
        gitFiles = [];
        return new Promise(async (pResolve, pFail)=>{
            let files = [];
            let t;
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
                files.push(f);
            }
            Promise.all(promises).then((pResults)=>{
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
        let body = document.querySelector('.file.modal .body');

        let perDays = [];
        let lastDate = null;

        let unversionedFiles = document.querySelector('#unversioned_files')?document.querySelector('#unversioned_files').checked:false;
        let filteredSearch = document.querySelector('input[name="search"]')&&document.querySelector('input[name="search"]').value.length>3?document.querySelector('input[name="search"]').value:false;

        let propTS = unversionedFiles?'last_m_time':'timestamp';

        let versionedFiles = project_files.filter((pFile)=>{

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

        document.querySelector('.file.modal .body .list').innerHTML = perDays.reduce((pHTML, pFiles)=>{
            return pHTML + '<div class="sublist"><div class="title">'+pFiles[0].dateString+'</div>'+pFiles.reduce((pHTMLList, pFile)=>{
                let id = pFile.filename.replace(/(\.|\/)/g, '_');
                return pHTMLList + '<div class="day"><div><input id="'+id+'" type="checkbox" value="'+pFile.filename+'"></div><label for="'+id+'" class="name" title="'+pFile.filename+'">'+pFile.file+'</label><div class="hours">'+pFile.hourString+'</div></div>';
            }, "")+'</div>';
        }, "");
    }

    function retrieveFileComparison(pSelectedFiles){
        document.querySelector('.modal.comparison .body').innerHTML = '<div class="loading_message">Chargement</div>';
        let params = extractParams(['local_folder', 'host', 'user', 'pass', 'folder']);
        params.files = pSelectedFiles;
        serverPromise('retrieve/files-comparison?render=true', params).then((pContent)=>{
            document.querySelector('.modal.comparison .body').innerHTML = pContent.html||"Une erreur est apparue";
        });
        document.querySelector('.modal.comparison .upload_action').addEventListener('click', deployHandler);
    }

    function deployHandler(){
        displayModal('upload');
        let files = Array.from(document.querySelectorAll('.modal.comparison .upload input[type="checkbox"]:checked')).map((pInput)=>pInput.value);
        let params = extractParams(['local_folder', 'host', 'user', 'pass', 'folder', 'domain']);
        params.files = files;

        const setStep = (pSteps)=>{
            for(let i in pSteps){
                if(!pSteps.hasOwnProperty(i)){
                    continue;
                }
                let actions = pSteps[i];
                for(let j in actions){
                    if(!actions.hasOwnProperty(j)){
                        continue;
                    }
                    document.querySelector('.modal.upload .body .steps .'+i).classList[j](actions[j]);
                }
            }
        };

        setStep({upload:{add:"current", remove:"waiting"}});
        serverPromise('upload/files', params).then((pResponse)=>{
            if(!pResponse){
                setStep({upload:{remove:"current", add:"error"}});
                console.log(pResponse);
                return;
            }
            setStep({upload:{remove:"current", add:"done"}, compare:{add:"current", remove:"waiting"}});
            serverPromise('retrieve/files-comparison', params).then((pResponse)=>{
                if(!pResponse || !pResponse.content || !pResponse.content.identicals){
                    setStep({compare:{remove:"current", add:"error"}});
                    console.log(pResponse);
                    return;
                }
                setStep({compare:{remove:"current", add:"done"}, opcache:{add:"current", remove:"waiting"}});
                serverPromise('invalidate/op-cache', params).then((pResponse)=>{
                    if(!pResponse){
                        setStep({opcache:{remove:"current", add:"error"}});
                        console.log(pResponse);
                        return;
                    }
                    setStep({opcache:{remove:"current", add:"done"}});
                    console.log("finished");
                });
            });
        });
    }

    function serverPromise(pUrl, pParams){
        return new Promise((pResolve, pError)=>{
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