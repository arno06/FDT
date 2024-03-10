(()=>{

    const FILE_POOL_DATE_CHECK = 100;

    let project_files;

    function init(){
        document.querySelector('.start.modal').classList.remove('hidden');
        document.querySelector('.go').addEventListener('click', startHandler);
        document.querySelector('.comparison.modal header .button.return').addEventListener('click', backToSelection);
    }

    function backToSelection(){
        let fileSelectionModal = document.querySelector('.file.modal');
        let comparisonModal = document.querySelector('.comparison.modal');
        fileSelectionModal.classList.remove('hidden');
        comparisonModal.classList.add('hidden');
    }

    function startHandler(e){
        let startingModalActions = document.querySelector('.start.modal .actions');
        startingModalActions.classList.toggle('loading');

        Promise.all([checkLocalFolder(), checkFTP()]).then((pResults)=>{
            startingModalActions.classList.toggle('loading');
            let checkFolder = pResults[0].content;
            let checkFTP = pResults[1].content;

            let number_local = document.querySelector('.local_folder.number');
            let number_ftp = document.querySelector('.ftp.number');

            let c = checkFolder === null ? 'close':'done';
            number_local.classList.remove('close', 'done');
            number_local.classList.add(c);
            number_local.innerHTML = '1<span class="material-symbols-outlined">'+c+'</span>';

            c = checkFTP === null ? 'close':'done';
            number_ftp.classList.remove('close', 'done');
            number_ftp.classList.add(c);
            number_ftp.innerHTML = '2<span class="material-symbols-outlined">'+c+'</span>';

            if(pResults.filter((pR)=>pR.content!==null)){
                document.querySelector('.file header h2').innerHTML = document.querySelector('input[name="local_folder"]').value+'<span>'+checkFolder.branch+'</span>';
                startingModalActions.innerHTML = '';
                fileSelectStep();
            }
        });
    }

    function fileSelectStep(){
        let fileSelectionModal = document.querySelector('.file.modal');
        let startingModalActions = document.querySelector('.start.modal .actions');
        setTimeout(()=>{
            startingModalActions.parentNode.classList.add('hidden');
            fileSelectionModal.classList.toggle('hidden');
            listFileHandler();
        }, 500);
    }

    function listFileHandler(){
        let local_folder = document.querySelector('input[name="local_folder"]').value;
        serverPromise('list/local-files', ['local_folder']).then(async (pResult)=>{
            let dummy = {value:0};
            let files = [];
            let t;
            const max = Math.ceil(pResult.content.files.length / FILE_POOL_DATE_CHECK);
            for(let i = 0; i<max; i++){
                let f = pResult.content.files.slice(i * FILE_POOL_DATE_CHECK, (i * FILE_POOL_DATE_CHECK)+FILE_POOL_DATE_CHECK);
                let final_files = await serverPromise('retrieve/updated-git-info', {files:f, local_folder:local_folder}).then((pContent)=>pContent.content.files);
                files = files.concat(final_files);

                let toVal = Math.round((files.length/pResult.content.files.length) * 100);
                t = M4Tween.from(dummy.value).to(toVal).start(3).onUpdate((pVal)=>{
                    dummy.value = Math.round(pVal.value);
                    document.querySelector('.loading_message span').innerHTML = dummy.value.toString();
                });
            }
            t.onComplete(()=>{
                project_files = files;
                let body = document.querySelector('.file.modal .body');
                body.innerHTML = `<div class="form"><div class="search"><input type="search" name="search" placeholder="Filtrer"></div><div class="unversioned"><input type="checkbox" name="unversioned" id="unversioned_files"/><label for="unversioned_files">Par date de modification locale</label></div></div>
<div class="list">
</div>`;

                document.querySelector('#unversioned_files').addEventListener('change', (e)=>{
                    refreshFiles();
                });

                let to = null;
                const changeSearchHandler = (e)=>{
                    if(to){
                        clearTimeout(to);
                    }
                    let value = e.currentTarget.value;
                    to = setTimeout(refreshFiles, 100);
                };
                document.querySelector('input[name="search"]').addEventListener('change', changeSearchHandler);
                document.querySelector('input[name="search"]').addEventListener('keyup', changeSearchHandler);

                document.querySelector('.modal.file header .button').addEventListener('click', (e)=>{
                    let selectedFiles = Array.from(document.querySelectorAll('.modal.file .list input[type="checkbox"]:checked')).map((pInput)=>pInput.value);

                    if(!selectedFiles.length){
                        return;
                    }

                    let fileSelectionModal = document.querySelector('.file.modal');
                    let comparisonModal = document.querySelector('.comparison.modal');
                    fileSelectionModal.classList.add('hidden');
                    comparisonModal.classList.toggle('hidden');
                    retrieveFileComparison(selectedFiles);
                });

                refreshFiles();
            });
        });
    }

    function refreshFiles(){
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
        document.querySelector('.modal.comparison').classList.add('hidden');
        document.querySelector('.modal.upload').classList.remove('hidden');
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

    function checkLocalFolder(){
        return serverPromise('check/local-folder', ['local_folder']);
    }

    function checkFTP(){
        return serverPromise('check/ftp', ['host', 'user', 'pass', 'folder']);
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