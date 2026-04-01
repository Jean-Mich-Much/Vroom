<?php
declare(strict_types=1);

function vroom_job_enqueue(callable $fn,int $timeout=90):int{
 return vroom_queue_push_php($fn,$timeout);
}

function vroom_job_run(callable $fn,int $workers=2,int $timeout=90):array{
 $id=vroom_job_enqueue($fn,$timeout);
 return vroom_job_run_queue($workers);
}

function vroom_job_run_queue(int $workers=2):array{
 global $VROOM_STATE;
 $results=[];
 if(!function_exists('pcntl_fork')){
  $pending=vroom_queue_get_pending();
  foreach($pending as $id=>$job){
   vroom_queue_mark_running($id);
   try{
    $r=$job['fn']();
    $results[$id]=$r;
    vroom_queue_mark_done($id);
   }catch(Throwable $e){
    vroom_queue_mark_error($id,$e->getMessage());
   }
  }
  return $results;
 }
 $pending=vroom_queue_get_pending();
 if($pending===[])return $results;
 $running=[];
 foreach($pending as $id=>$job){
  if(count($running)>=$workers)break;
  vroom_queue_mark_running($id);
  $pid=pcntl_fork();
  if($pid===-1){
   vroom_queue_mark_error($id,'fork_failed');
   continue;
  }
  if($pid===0){
   $out=null;
   $err='';
   try{
    $out=$job['fn']();
   }catch(Throwable $e){
    $err=$e->getMessage();
   }
   $payload=[
    'job_id'=>$id,
    'error'=>$err,
    'result'=>$out
   ];
   echo json_encode($payload,JSON_UNESCAPED_UNICODE)."\n";
   exit(0);
  }else{
   $VROOM_STATE['workers'][$pid]=[
    'job_id'=>$id,
    'started_at'=>microtime(true),
    'timeout'=>$job['timeout']
   ];
   $running[$pid]=$id;
  }
 }
 while(count($running)>0){
  $ended=pcntl_wait($status,WNOHANG);
  $now=microtime(true);
  if($ended>0){
   if(isset($running[$ended])){
    $jid=$running[$ended];
    unset($running[$ended]);
    unset($VROOM_STATE['workers'][$ended]);
    $exit_code=pcntl_wexitstatus($status);
    if($exit_code===0){
     vroom_queue_mark_done($jid);
    }else{
     vroom_queue_mark_error($jid,'exit_'.$exit_code);
    }
   }
  }else{
   foreach($running as $pid=>$jid){
    if(!isset($VROOM_STATE['workers'][$pid]))continue;
    $w=$VROOM_STATE['workers'][$pid];
    $t=$w['timeout'];
    if($t<=0)$t=90;
    if(($now-$w['started_at'])>$t){
     if(function_exists('posix_kill'))@posix_kill($pid,9);
     vroom_queue_mark_timeout($jid);
     unset($running[$pid]);
     unset($VROOM_STATE['workers'][$pid]);
    }
   }
   usleep(50000);
  }
 }
 return $results;
}
