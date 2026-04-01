<?php
declare(strict_types=1);

$VROOM_STATE=[
 'buffers'=>[],
 'headers'=>[],
 'locks'=>[],
 'ids'=>[],
 'processes'=>[],
 'workers'=>[],
 'transactions'=>[
  'active'=>false,
  'writes'=>[],
  'deletes'=>[]
 ],
 'cache'=>[
  'limit'=>536870912,
  'used'=>0,
  'meta'=>[]
 ],
 'queue'=>[
  'jobs'=>[],
  'next_id'=>1
 ]
];

function vroom_state_begin():void{
 global $VROOM_STATE;
 $VROOM_STATE['transactions']['active']=true;
 $VROOM_STATE['transactions']['writes']=[];
 $VROOM_STATE['transactions']['deletes']=[];
}

function vroom_state_commit():void{
 global $VROOM_STATE;
 if(!$VROOM_STATE['transactions']['active'])return;
 foreach($VROOM_STATE['transactions']['writes'] as $op){
  vroom_disk_do_write_now($op['type'],$op['id'],$op['path'],$op['header'],$op['data']);
 }
 foreach($VROOM_STATE['transactions']['deletes'] as $op){
  vroom_disk_do_delete_now($op['type'],$op['id'],$op['path']);
 }
 $VROOM_STATE['transactions']['active']=false;
 $VROOM_STATE['transactions']['writes']=[];
 $VROOM_STATE['transactions']['deletes']=[];
}

function vroom_state_rollback():void{
 global $VROOM_STATE;
 if(!$VROOM_STATE['transactions']['active'])return;
 $VROOM_STATE['transactions']['active']=false;
 $VROOM_STATE['transactions']['writes']=[];
 $VROOM_STATE['transactions']['deletes']=[];
}

function vroom_state_add_write(string $type,int $id,string $path,array $header,array $data):void{
 global $VROOM_STATE;
 if(!$VROOM_STATE['transactions']['active']){
  vroom_disk_do_write_now($type,$id,$path,$header,$data);
  return;
 }
 $VROOM_STATE['transactions']['writes'][]=[
  'type'=>$type,
  'id'=>$id,
  'path'=>$path,
  'header'=>$header,
  'data'=>$data
 ];
}

function vroom_state_add_delete(string $type,int $id,string $path):void{
 global $VROOM_STATE;
 if(!$VROOM_STATE['transactions']['active']){
  vroom_disk_do_delete_now($type,$id,$path);
  return;
 }
 $VROOM_STATE['transactions']['deletes'][]=[
  'type'=>$type,
  'id'=>$id,
  'path'=>$path
 ];
}

function vroom_state_cache_recalc(string $type,int $id):void{
 global $VROOM_STATE;
 $b=null;
 $h=null;
 if(isset($VROOM_STATE['buffers'][$type][$id]))$b=$VROOM_STATE['buffers'][$type][$id];
 if(isset($VROOM_STATE['headers'][$type][$id]))$h=$VROOM_STATE['headers'][$type][$id];
 if($b===null&&$h===null){
  vroom_state_cache_forget($type,$id);
  return;
 }
 $size=0;
 if($b!==null)$size+=strlen(json_encode($b,JSON_UNESCAPED_UNICODE));
 if($h!==null)$size+=strlen(json_encode($h,JSON_UNESCAPED_UNICODE));
 $key=$type.':'.$id;
 $prev=0;
 if(isset($VROOM_STATE['cache']['meta'][$key]['size']))$prev=$VROOM_STATE['cache']['meta'][$key]['size'];
 $VROOM_STATE['cache']['used']+=$size-$prev;
 $VROOM_STATE['cache']['meta'][$key]=[
  'size'=>$size,
  'last_access'=>microtime(true)
 ];
 vroom_state_cache_evict();
}

function vroom_state_cache_touch(string $type,int $id):void{
 global $VROOM_STATE;
 $key=$type.':'.$id;
 if(isset($VROOM_STATE['cache']['meta'][$key])){
  $VROOM_STATE['cache']['meta'][$key]['last_access']=microtime(true);
 }
}

function vroom_state_cache_forget(string $type,int $id):void{
 global $VROOM_STATE;
 $key=$type.':'.$id;
 if(isset($VROOM_STATE['cache']['meta'][$key])){
  $VROOM_STATE['cache']['used']-=$VROOM_STATE['cache']['meta'][$key]['size'];
  unset($VROOM_STATE['cache']['meta'][$key]);
 }
}

function vroom_state_cache_evict():void{
 global $VROOM_STATE;
 $limit=$VROOM_STATE['cache']['limit'];
 if($VROOM_STATE['cache']['used']<=$limit)return;
 if($VROOM_STATE['cache']['meta']===[])return;
 $meta=$VROOM_STATE['cache']['meta'];
 uasort($meta,function(array $a,array $b):int{
  if($a['last_access']===$b['last_access'])return 0;
  return $a['last_access']<$b['last_access']?-1:1;
 });
 foreach($meta as $key=>$info){
  $parts=explode(':',$key,2);
  if(count($parts)!==2)continue;
  $type=$parts[0];
  $id=(int)$parts[1];
  if(isset($VROOM_STATE['buffers'][$type][$id]))unset($VROOM_STATE['buffers'][$type][$id]);
  if(isset($VROOM_STATE['headers'][$type][$id]))unset($VROOM_STATE['headers'][$type][$id]);
  if(isset($VROOM_STATE['cache']['meta'][$key])){
   $VROOM_STATE['cache']['used']-=$VROOM_STATE['cache']['meta'][$key]['size'];
   unset($VROOM_STATE['cache']['meta'][$key]);
  }
  if($VROOM_STATE['cache']['used']<=$limit)break;
 }
}

function vroom_queue_push_php(callable $fn,int $timeout):int{
 global $VROOM_STATE;
 $id=$VROOM_STATE['queue']['next_id']++;
 $VROOM_STATE['queue']['jobs'][$id]=[
  'id'=>$id,
  'type'=>'php',
  'fn'=>$fn,
  'timeout'=>$timeout,
  'status'=>'pending',
  'created_at'=>microtime(true),
  'started_at'=>0,
  'ended_at'=>0,
  'retries'=>0,
  'last_error'=>''
 ];
 return $id;
}

function vroom_queue_get_pending():array{
 global $VROOM_STATE;
 $out=[];
 foreach($VROOM_STATE['queue']['jobs'] as $id=>$job){
  if($job['status']==='pending')$out[$id]=$job;
 }
 return $out;
}

function vroom_queue_mark_running(int $id):void{
 global $VROOM_STATE;
 if(!isset($VROOM_STATE['queue']['jobs'][$id]))return;
 $VROOM_STATE['queue']['jobs'][$id]['status']='running';
 $VROOM_STATE['queue']['jobs'][$id]['started_at']=microtime(true);
}

function vroom_queue_mark_done(int $id):void{
 global $VROOM_STATE;
 if(!isset($VROOM_STATE['queue']['jobs'][$id]))return;
 $VROOM_STATE['queue']['jobs'][$id]['status']='done';
 $VROOM_STATE['queue']['jobs'][$id]['ended_at']=microtime(true);
}

function vroom_queue_mark_error(int $id,string $error):void{
 global $VROOM_STATE;
 if(!isset($VROOM_STATE['queue']['jobs'][$id]))return;
 $VROOM_STATE['queue']['jobs'][$id]['status']='error';
 $VROOM_STATE['queue']['jobs'][$id]['ended_at']=microtime(true);
 $VROOM_STATE['queue']['jobs'][$id]['last_error']=$error;
 $VROOM_STATE['queue']['jobs'][$id]['retries']++;
}

function vroom_queue_mark_timeout(int $id):void{
 global $VROOM_STATE;
 if(!isset($VROOM_STATE['queue']['jobs'][$id]))return;
 $VROOM_STATE['queue']['jobs'][$id]['status']='timeout';
 $VROOM_STATE['queue']['jobs'][$id]['ended_at']=microtime(true);
 $VROOM_STATE['queue']['jobs'][$id]['retries']++;
}

function vroom_queue_reset_stuck(int $max_runtime):void{
 global $VROOM_STATE;
 $now=microtime(true);
 foreach($VROOM_STATE['queue']['jobs'] as $id=>$job){
  if($job['status']==='running'){
   if($job['started_at']>0&&($now-$job['started_at'])>$max_runtime){
    $VROOM_STATE['queue']['jobs'][$id]['status']='pending';
   }
  }
 }
}

function vroom_state_info():array{
 global $VROOM_STATE;
 return [
  'buffers'=>array_map('count',$VROOM_STATE['buffers']),
  'headers'=>array_map('count',$VROOM_STATE['headers']),
  'locks'=>$VROOM_STATE['locks'],
  'ids'=>$VROOM_STATE['ids'],
  'processes'=>$VROOM_STATE['processes'],
  'workers'=>$VROOM_STATE['workers'],
  'transaction_active'=>$VROOM_STATE['transactions']['active'],
  'cache_used'=>$VROOM_STATE['cache']['used'],
  'cache_limit'=>$VROOM_STATE['cache']['limit'],
  'queue_jobs'=>count($VROOM_STATE['queue']['jobs'])
 ];
}
