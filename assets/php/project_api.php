<?php

require __DIR__ . '/config.php';

header('Content-Type: application/json');

if (!is_user_logged_in()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error'   => 'Unauthorized: login required.',
    ]);
    exit;
}

// Current logged-in username from the session
$username = $_SESSION['user'] ?? null;

// Build a canonical screen session name for a project, incorporating the user
// so that session names do not conflict across different users.
function build_project_screen_name(string $safeUser, string $projectId): string {
    $safeUser = preg_replace('/[^a-zA-Z0-9_-]/', '_', $safeUser);
    $projectId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $projectId);
    return 'project_' . $safeUser . '_' . $projectId;
}

// Recursively delete a directory tree (project folder and all contents).
function delete_project_directory(string $dir): void {
    if (!is_dir($dir)) {
        return;
    }
    $items = @scandir($dir);
    if ($items === false) {
        return;
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            delete_project_directory($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}

// Placeholder for project API logic
// We will implement specific actions (list/create/update/delete)
// in the next step based on your requirements.

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = strtolower(trim($_REQUEST['action'] ?? ''));

$allowed_actions = [
    'create_user',
    'create',
    'get',
    'update',
    'delete',
    'upload',
    'status',
    'start',
    'stop',
    'pull',
];

if ($action === '' || !in_array($action, $allowed_actions, true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'Invalid or missing action.',
        'allowed' => $allowed_actions,
    ]);
    exit;
}

// Dispatch to specific action handler. For now, each
// action returns a placeholder until implemented.
$response = [
    'success' => false,
    'action'  => $action,
    'user'    => $username,
    'method'  => $method,
    'message' => 'Not implemented yet.',
];

switch ($action) {
    case 'create_user':
        if ($username === null || $username === '') {
            http_response_code(500);
            $response['error'] = 'No username found in session.';
            break;
        }
        $projectsDir = __DIR__ . '/../../data/projects';
        if (!is_dir($projectsDir) && !mkdir($projectsDir,0770,true) && !is_dir($projectsDir)) {
            http_response_code(500);
            $response['error'] = 'Failed to create projects directory.';
            break;
        }
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/','_',$username);
        $userDir = $projectsDir . '/' . $safeName;
        if (!is_dir($userDir) && !mkdir($userDir,0770,true) && !is_dir($userDir)) {
            http_response_code(500);
            $response['error'] = 'Failed to create user project directory.';
            break;
        }
        $filePath = $projectsDir . '/' . $safeName . '.json';
        if (file_exists($filePath)) {
            $response['success'] = true;
            $response['message'] = 'Project file already exists.';
            $response['path'] = basename($filePath);
            $response['dir'] = $safeName;
            break;
        }
        $data = ['projects'=>[]];
        if (file_put_contents($filePath,json_encode($data,JSON_PRETTY_PRINT)) === false) {
            http_response_code(500);
            $response['error'] = 'Failed to write project file.';
            break;
        }
        $response['success'] = true;
        $response['message'] = 'Project file and directory created.';
        $response['path'] = basename($filePath);
        $response['dir'] = $safeName;
        break;
    case 'create':
        if ($username===null||$username===''){http_response_code(500);$response['error']='No username found in session.';break;}
        $projectsDir=__DIR__.'/../../data/projects';
        if(!is_dir($projectsDir)&&!mkdir($projectsDir,0770,true)&&!is_dir($projectsDir)){http_response_code(500);$response['error']='Failed to create projects directory.';break;}
        $safeName=preg_replace('/[^a-zA-Z0-9_-]/','_',$username);
        $userDir=$projectsDir.'/'.$safeName;
        if(!is_dir($userDir)&&!mkdir($userDir,0770,true)&&!is_dir($userDir)){http_response_code(500);$response['error']='Failed to create user project directory.';break;}
        $filePath=$projectsDir.'/'.$safeName.'.json';
        if(!file_exists($filePath)){$data=['projects'=>[]];}else{$raw=file_get_contents($filePath);$data=json_decode($raw,true);if(!is_array($data)||!isset($data['projects'])||!is_array($data['projects'])){$data=['projects'=>[]];}}
        $name=trim($_REQUEST['name']??'');
        $modeRaw=isset($_REQUEST['mode'])?strtolower(trim($_REQUEST['mode'])):'';
        $mode=($modeRaw==='project'||$modeRaw==='git')?$modeRaw:null;
        $projectTarget=isset($_REQUEST['project'])?trim($_REQUEST['project']):null;
        $screenId=isset($_REQUEST['screen'])?trim($_REQUEST['screen']):null;
        $startFile=trim($_REQUEST['start']??'');
        $status=trim($_REQUEST['status']??'offline');
        if($name===''||$startFile===''){http_response_code(400);$response['error']='Missing required fields: name and start.';break;}
        if($mode!=='git'){$mode='project';}
        $chars='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $id='';
        do{
            $id='';
            for($i=0;$i<16;$i++){$id.=$chars[random_int(0,strlen($chars)-1)];}
            $exists=false;
            foreach($data['projects'] as $p){if(isset($p['id'])&&$p['id']===$id){$exists=true;break;}}
        }while($exists);
        $projDir=$userDir.'/'.$id;
        if(!is_dir($projDir)&&!mkdir($projDir,0770,true)&&!is_dir($projDir)){http_response_code(500);$response['error']='Failed to create project directory.';break;}
        $project=['id'=>$id,'name'=>$name,'mode'=>$mode,'project'=>$projectTarget,'screen'=>$screenId,'start_file'=>$startFile,'status'=>$status,'created_at'=>date('c')];
        $data['projects'][]=$project;
        if(file_put_contents($filePath,json_encode($data,JSON_PRETTY_PRINT))===false){http_response_code(500);$response['error']='Failed to write project file.';break;}
        $response['success']=true;
        $response['message']='Project created.';
        $response['project']=$project;
        break;
    case 'get':
        if($username===null||$username===''){http_response_code(500);$response['error']='No username found in session.';break;}
        $projectsDir=__DIR__.'/../../data/projects';
        if(!is_dir($projectsDir)&&!mkdir($projectsDir,0770,true)&&!is_dir($projectsDir)){http_response_code(500);$response['error']='Failed to create projects directory.';break;}
        $safeName=preg_replace('/[^a-zA-Z0-9_-]/','_',$username);
        $userDir=$projectsDir.'/'.$safeName;
        if(!is_dir($userDir)&&!mkdir($userDir,0770,true)&&!is_dir($userDir)){http_response_code(500);$response['error']='Failed to create user project directory.';break;}
        $filePath=$projectsDir.'/'.$safeName.'.json';
        if(!file_exists($filePath)){$data=['projects'=>[]];if(file_put_contents($filePath,json_encode($data,JSON_PRETTY_PRINT))===false){http_response_code(500);$response['error']='Failed to write project file.';break;}}
        else{$raw=file_get_contents($filePath);$data=json_decode($raw,true);if(!is_array($data)||!isset($data['projects'])||!is_array($data['projects'])){$data=['projects'=>[]];}}
        $nameFilter=isset($_REQUEST['name'])?trim($_REQUEST['name']):'';
        if($nameFilter!==''){$list=[];foreach($data['projects'] as $p){if(isset($p['name'])&&$p['name']===$nameFilter){$list[]=$p;}}}else{$list=$data['projects'];}
        $response['success']=true;
        $response['message']='Projects retrieved.';
        $response['projects']=$list;
        break;
    case 'update':
        if($username===null||$username===''){http_response_code(500);$response['error']='No username found in session.';break;}
        $id=trim($_REQUEST['id']??'');
        $targetName=trim($_REQUEST['name']??'');
        if($id===''&&$targetName===''){http_response_code(400);$response['error']='Missing project id or name.';break;}
        $projectsDir=__DIR__.'/../../data/projects';
        $safeName=preg_replace('/[^a-zA-Z0-9_-]/','_',$username);
        $filePath=$projectsDir.'/'.$safeName.'.json';
        if(!file_exists($filePath)){http_response_code(404);$response['error']='Project file not found.';break;}
        $raw=file_get_contents($filePath);$data=json_decode($raw,true);
        if(!is_array($data)||!isset($data['projects'])||!is_array($data['projects'])){http_response_code(500);$response['error']='Corrupt project file.';break;}
        $found=false;$updated=null;
        foreach($data['projects'] as &$p){
            if($id!==''){
                if(!isset($p['id'])||$p['id']!==$id)continue;
            }else{
                if(!isset($p['name'])||$p['name']!==$targetName)continue;
            }
            $found=true;
            foreach($_REQUEST as $k=>$v){if($k==='action'||$k==='id')continue;$k=trim($k);if($k==='')continue;$v=is_string($v)?trim($v):$v;if($k==='start'){$p['start_file']=$v;continue;}$p[$k]=$v;}
            $p['updated_at']=date('c');
            $updated=$p;
            break;}
        unset($p);
        if(!$found){http_response_code(404);$response['error']='Project not found.';break;}
        if(file_put_contents($filePath,json_encode($data,JSON_PRETTY_PRINT))===false){http_response_code(500);$response['error']='Failed to write project file.';break;}
        $response['success']=true;
        $response['message']='Project updated.';
        $response['project']=$updated;
        break;
    case 'upload':
        if($username===null||$username===''){http_response_code(500);$response['error']='No username found in session.';break;}
        $id=trim($_REQUEST['id']??'');
        if($id===''){http_response_code(400);$response['error']='Missing project id.';break;}
        if(!isset($_FILES['archive'])||!is_uploaded_file($_FILES['archive']['tmp_name'])){http_response_code(400);$response['error']='Missing or invalid archive upload.';break;}
        $projectsDir=__DIR__.'/../../data/projects';
        $safeName=preg_replace('/[^a-zA-Z0-9_-]/','_',$username);
        $userDir=$projectsDir.'/'.$safeName;
        if(!is_dir($userDir)&&!mkdir($userDir,0770,true)&&!is_dir($userDir)){http_response_code(500);$response['error']='Failed to create user project directory.';break;}
        $projDir=$userDir.'/'.$id;
        if(!is_dir($projDir)&&!mkdir($projDir,0770,true)&&!is_dir($projDir)){http_response_code(500);$response['error']='Failed to create project directory.';break;}
        $tmp=$_FILES['archive']['tmp_name'];
        $zip=new ZipArchive();
        if($zip->open($tmp)!==true){http_response_code(500);$response['error']='Failed to open zip archive.';break;}
        if(!$zip->extractTo($projDir)){$zip->close();http_response_code(500);$response['error']='Failed to extract archive.';break;}
        $zip->close();
        $response['success']=true;
        $response['message']='Archive uploaded and extracted.';
        $response['project_id']=$id;
        break;
    case 'delete':
        if($username===null||$username===''){http_response_code(500);$response['error']='No username found in session.';break;}
        $id=trim($_REQUEST['id']??'');
        if($id===''){http_response_code(400);$response['error']='Missing project id.';break;}
        $projectsDir=__DIR__.'/../../data/projects';
        $safeName=preg_replace('/[^a-zA-Z0-9_-]/','_',$username);
        $filePath=$projectsDir.'/'.$safeName.'.json';
        if(!file_exists($filePath)){http_response_code(404);$response['error']='Project file not found.';break;}
        $raw=file_get_contents($filePath);$data=json_decode($raw,true);
        if(!is_array($data)||!isset($data['projects'])||!is_array($data['projects'])){http_response_code(500);$response['error']='Corrupt project file.';break;}
        $found=false;$newProjects=[];$folderName=null;
        foreach($data['projects'] as $p){if(isset($p['id'])&&$p['id']===$id){$found=true;$folderName=$p['id'];continue;}$newProjects[]=$p;}
        if(!$found){http_response_code(404);$response['error']='Project not found.';break;}
        $data['projects']=$newProjects;
        if(file_put_contents($filePath,json_encode($data,JSON_PRETTY_PRINT))===false){http_response_code(500);$response['error']='Failed to update project file.';break;}
        if($folderName){
            $userDir=$projectsDir.'/'.$safeName.'/'.$folderName;
            delete_project_directory($userDir);
        }
        $response['success']=true;
        $response['message']='Project deleted.';
        $response['deleted_id']=$id;
        break;
    case 'status':
        if($username===null||$username===''){http_response_code(500);$response['error']='No username found in session.';break;}
        $id=trim($_REQUEST['id']??'');
        if($id===''){http_response_code(400);$response['error']='Missing project id.';break;}
        $projectsDir=__DIR__.'/../../data/projects';
        $safeName=preg_replace('/[^a-zA-Z0-9_-]/','_',$username);
        $filePath=$projectsDir.'/'.$safeName.'.json';
        if(!file_exists($filePath)){http_response_code(404);$response['error']='Project file not found.';break;}
        $raw=file_get_contents($filePath);$data=json_decode($raw,true);
        if(!is_array($data)||!isset($data['projects'])||!is_array($data['projects'])){http_response_code(500);$response['error']='Corrupt project file.';break;}
        $project=null;$index=null;
        foreach($data['projects'] as $i=>$p){if(isset($p['id'])&&$p['id']===$id){$project=$p;$index=$i;break;}}
        if($project===null){http_response_code(404);$response['error']='Project not found.';break;}
        $sessionName = isset($project['screen']) && $project['screen']!==''
            ? $project['screen']
            : build_project_screen_name($safeName,$id);
        if($sessionName===''){http_response_code(400);$response['error']='No screen session associated with this project.';break;}
        $output=[];$ret=null;
        @exec('/usr/bin/screen -ls 2>&1',$output,$ret);
        $joined=implode("\n",$output);
        $active=(strpos($joined,$sessionName)!==false);
        $data['projects'][$index]['screen']=$sessionName;
        $data['projects'][$index]['status']=$active?'running':'offline';
        $data['projects'][$index]['last_status_check_at']=date('c');
        file_put_contents($filePath,json_encode($data,JSON_PRETTY_PRINT));
        $response['success']=true;
        $response['message']='Status checked.';
        $response['project_id']=$id;
        $response['screen']=$sessionName;
        $response['active']=$active;
        $response['status']=$data['projects'][$index]['status'];
        break;
    case 'start':
        if($username===null||$username===''){http_response_code(500);$response['error']='No username found in session.';break;}
        $id=trim($_REQUEST['id']??'');
        if($id===''){http_response_code(400);$response['error']='Missing project id.';break;}
        $projectsDir=__DIR__.'/../../data/projects';
        $safeName=preg_replace('/[^a-zA-Z0-9_-]/','_',$username);
        $filePath=$projectsDir.'/'.$safeName.'.json';
        if(!file_exists($filePath)){http_response_code(404);$response['error']='Project file not found.';break;}
        $raw=file_get_contents($filePath);$data=json_decode($raw,true);
        if(!is_array($data)||!isset($data['projects'])||!is_array($data['projects'])){http_response_code(500);$response['error']='Corrupt project file.';break;}
        $project=null;$index=null;
        foreach($data['projects'] as $i=>$p){if(isset($p['id'])&&$p['id']===$id){$project=$p;$index=$i;break;}}
        if($project===null){http_response_code(404);$response['error']='Project not found.';break;}
        $startFile=$project['start_file']??'';
        if($startFile===''){http_response_code(400);$response['error']='Project has no start_file configured.';break;}
        $userDir=$projectsDir.'/'.$safeName;
        $projDir=$userDir.'/'.$id;
        if(!is_dir($projDir)){http_response_code(500);$response['error']='Project directory does not exist.';break;}
        $sessionName=build_project_screen_name($safeName,$id);
        $requirements=$projDir.'/requirements.txt';
        $python='/usr/bin/python3';
        // Build the inner command to run *inside* the screen session so that
        // dependency installation and bot output are both captured in screen.log.
        // When requirements.txt exists, we use a per-project virtualenv (.venv)
        // so pip installs do not touch system or user-wide locations like
        // /var/www/.local.
        $innerCmd=$python.' '.escapeshellarg($startFile);
        if(is_file($requirements)){
            $venvSetup='[ -d .venv ] || '.$python.' -m venv .venv';
            // Use a per-project cache directory and disable the default pip cache
            // to avoid permission warnings on /var/www/.cache.
            $activateAndInstall='source .venv/bin/activate; mkdir -p .cache >/dev/null 2>&1; XDG_CACHE_HOME=.cache PIP_NO_CACHE_DIR=1 pip install -r requirements.txt';
            $runBot='python '.escapeshellarg($startFile);
            $innerCmd=$venvSetup.'; '.$activateAndInstall.' && '.$runBot;
        }
        // Use screen's built-in logging so all console output goes to screen.log in the project directory
        $cmd='cd '.escapeshellarg($projDir).' && /usr/bin/screen -L -Logfile screen.log -dmS '.escapeshellarg($sessionName).' /bin/bash -lc '.escapeshellarg($innerCmd);
        $output=[];$ret=null;
        @exec($cmd.' > /dev/null 2>&1 &',$output,$ret);
        // We cannot reliably detect failure here, so we optimistically set running
        $data['projects'][$index]['status']='running';
        $data['projects'][$index]['screen']=$sessionName;
        $data['projects'][$index]['last_started_at']=date('c');
        file_put_contents($filePath,json_encode($data,JSON_PRETTY_PRINT));
        $response['success']=true;
        $response['message']='Start command issued.';
        $response['project_id']=$id;
        $response['screen']=$sessionName;
        break;
    case 'stop':
        if($username===null||$username===''){http_response_code(500);$response['error']='No username found in session.';break;}
        $id=trim($_REQUEST['id']??'');
        if($id===''){http_response_code(400);$response['error']='Missing project id.';break;}
        $projectsDir=__DIR__.'/../../data/projects';
        $safeName=preg_replace('/[^a-zA-Z0-9_-]/','_',$username);
        $filePath=$projectsDir.'/'.$safeName.'.json';
        if(!file_exists($filePath)){http_response_code(404);$response['error']='Project file not found.';break;}
        $raw=file_get_contents($filePath);$data=json_decode($raw,true);
        if(!is_array($data)||!isset($data['projects'])||!is_array($data['projects'])){http_response_code(500);$response['error']='Corrupt project file.';break;}
        $project=null;$index=null;
        foreach($data['projects'] as $i=>$p){if(isset($p['id'])&&$p['id']===$id){$project=$p;$index=$i;break;}}
        if($project===null){http_response_code(404);$response['error']='Project not found.';break;}
        $sessionName = isset($project['screen']) && $project['screen']!==''
            ? $project['screen']
            : build_project_screen_name($safeName,$id);
        if($sessionName===''){http_response_code(400);$response['error']='No screen session associated with this project.';break;}
        $cmd='/usr/bin/screen -S '.escapeshellarg($sessionName).' -X quit';
        $output=[];$ret=null;
        @exec($cmd.' > /dev/null 2>&1 &',$output,$ret);
        $data['projects'][$index]['status']='offline';
        $data['projects'][$index]['last_stopped_at']=date('c');
        file_put_contents($filePath,json_encode($data,JSON_PRETTY_PRINT));
        $response['success']=true;
        $response['message']='Stop command issued.';
        $response['project_id']=$id;
        $response['screen']=$sessionName;
        break;
    case 'pull':
        if($username===null||$username===''){http_response_code(500);$response['error']='No username found in session.';break;}
        $id=trim($_REQUEST['id']??'');
        if($id===''){http_response_code(400);$response['error']='Missing project id.';break;}

        $projectsDir=__DIR__.'/../../data/projects';
        $safeName=preg_replace('/[^a-zA-Z0-9_-]/','_',$username);
        $filePath=$projectsDir.'/'.$safeName.'.json';
        if(!file_exists($filePath)){http_response_code(404);$response['error']='Project file not found.';break;}

        $raw=file_get_contents($filePath);$data=json_decode($raw,true);
        if(!is_array($data)||!isset($data['projects'])||!is_array($data['projects'])){http_response_code(500);$response['error']='Corrupt project file.';break;}

        $project=null;$index=null;
        foreach($data['projects'] as $i=>$p){if(isset($p['id'])&&$p['id']===$id){$project=$p;$index=$i;break;}}
        if($project===null){http_response_code(404);$response['error']='Project not found.';break;}

        $mode=$project['mode']??'project';
        if($mode!=='git'){http_response_code(400);$response['error']='Project is not configured for git mode.';break;}

        $repoUrl=trim($project['project']??'');
        if($repoUrl===''){http_response_code(400);$response['error']='Git repository URL is not set for this project.';break;}

        $userDir=$projectsDir.'/'.$safeName;
        if(!is_dir($userDir)&&!mkdir($userDir,0770,true)&&!is_dir($userDir)){http_response_code(500);$response['error']='Failed to create user project directory.';break;}

        $projDir=$userDir.'/'.$id;
        if(!is_dir($projDir)&&!mkdir($projDir,0770,true)&&!is_dir($projDir)){http_response_code(500);$response['error']='Failed to create project directory.';break;}

        $gitDir=$projDir.'/.git';
        if(!is_dir($gitDir)){
            // Use git from PATH rather than a hardcoded binary path for portability
            $cmd='cd '.escapeshellarg($projDir).' && git clone --depth 1 '.escapeshellarg($repoUrl).' .';
        }else{
            $cmd='cd '.escapeshellarg($projDir).' && git pull --rebase';
        }

        $output=[];$ret=null;
        @exec($cmd.' 2>&1',$output,$ret);
        if($ret!==0){
            http_response_code(500);
            $response['error']='Git operation failed.';
            $response['git_output']=$output;
            break;
        }

        $data['projects'][$index]['last_git_pull_at']=date('c');
        file_put_contents($filePath,json_encode($data,JSON_PRETTY_PRINT));

        $response['success']=true;
        $response['message']='Git repository synchronized.';
        $response['project_id']=$id;
        $response['git_output']=$output;
        break;
}

echo json_encode($response);
