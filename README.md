# FTP Deployment Tool

PHP application to be run on a local environment containing the projects to be deployed

 * Project Setup
 * File Selection
 * File Comparison
 * Deployment

## Requirement
 * Apache local webserver
 * PHP 8.0 with ftp module

## Project Setup

First screen:
 * Define the relative local folder of the project
 * The remote URL
 * Declare wether you wish to check git update informations for each file in order to re-order the File Selection list (might be quite slow with an antivirus running)
 * Setup all informations regarding the FTP informations
If you want to save you project setup, you can edit the `includes/resources/envs.json` file and save your infos : 

```json
[
  {
    "name": "Project Name only used in the first screen",
    "ftp": {
      "host": "...",
      "user": "...",
      "pass": "...",
      "folder": "..."
    },
    "local_folder": "../my-local-project",
    "domain": "https://www.my-remote-project.tld/",
    "checkgit": true
  }
]
```

## File Selection

Second screen:
 * Default order : Local last modification date of files
 * If checked in the first screen, a XHR pool will get last git modification date of files. Upon completion, you will be able to change the order

## File Comparison

Third screen:
 * For each selected files, an async script will retrieve the distant file (onto FTP server) and will compare the local version
 * Comparison is quite basic: line to line, empty lines and white spaces ignored
 * If files are identicals, they will not be selected for deployment by default

## Deployment

Last screen and 3 simple steps: 
 * Upload : make sure that the parent folder exists before uploading each files
 * Comparison : Each uploaded files are then compared in order to make sure that modification are deployed
 * OPCache : A script is deployed on the remote host that invalidate each PHP files in the OP Cache. If there is a TPL file, OP Cache si simply resetted 

Once a step fails, deployment is stopped but there is no rollback (for now) and you can open the inspector for more informations.