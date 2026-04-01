<?php
declare(strict_types=1);

$VROOM_RELATIONS_BUSY=false;

function vroom_relations_after_save(string $type,int $id):void{
 global $VROOM_RELATIONS_BUSY;
 if($VROOM_RELATIONS_BUSY)return;
 $VROOM_RELATIONS_BUSY=true;
 $header=vroom_ram_get_header($type,$id);
 if($header===[]){
  $path=vroom_build_path($type,$id);
  if(is_file($path)){
   vroom_disk_read_to_ram($type,$id,$path);
   $header=vroom_ram_get_header($type,$id);
  }
 }
 if($header!==[]){
  if(isset($header['parent_id'])&&$header['parent_id']!==''){
   $pid=(int)$header['parent_id'];
   vroom_relations_set_field($type,$pid,'child_id',(string)$id);
  }
  if(isset($header['child_id'])&&$header['child_id']!==''){
   $cid=(int)$header['child_id'];
   vroom_relations_set_field($type,$cid,'parent_id',(string)$id);
  }
 }
 $VROOM_RELATIONS_BUSY=false;
}

function vroom_relations_before_delete(string $type,int $id):void{
 global $VROOM_RELATIONS_BUSY;
 if($VROOM_RELATIONS_BUSY)return;
 $VROOM_RELATIONS_BUSY=true;
 $path=vroom_build_path($type,$id);
 if(!is_file($path)){
  $VROOM_RELATIONS_BUSY=false;
  return;
 }
 vroom_disk_read_to_ram($type,$id,$path);
 $header=vroom_ram_get_header($type,$id);
 if($header!==[]){
  if(isset($header['parent_id'])&&$header['parent_id']!==''){
   $pid=(int)$header['parent_id'];
   vroom_relations_set_field($type,$pid,'child_id','');
  }
  if(isset($header['child_id'])&&$header['child_id']!==''){
   $cid=(int)$header['child_id'];
   vroom_relations_set_field($type,$cid,'parent_id','');
  }
 }
 $VROOM_RELATIONS_BUSY=false;
}

function vroom_relations_preload(string $type,int $id):void{
 $header=vroom_ram_get_header($type,$id);
 if($header===[]){
  $path=vroom_build_path($type,$id);
  if(is_file($path)){
   vroom_disk_read_to_ram($type,$id,$path);
   $header=vroom_ram_get_header($type,$id);
  }
 }
 if($header===[])return;
 if(isset($header['parent_id'])&&$header['parent_id']!==''){
  $pid=(int)$header['parent_id'];
  $ppath=vroom_build_path($type,$pid);
  if(is_file($ppath))vroom_disk_read_to_ram($type,$pid,$ppath);
 }
 if(isset($header['child_id'])&&$header['child_id']!==''){
  $cid=(int)$header['child_id'];
  $cpath=vroom_build_path($type,$cid);
  if(is_file($cpath))vroom_disk_read_to_ram($type,$cid,$cpath);
 }
 if(isset($header['user_id'])&&$header['user_id']!==''){
  $uid=(int)$header['user_id'];
  $upath=vroom_build_path('user',$uid);
  if(is_file($upath))vroom_disk_read_to_ram('user',$uid,$upath);
 }
}

function vroom_relations_set_field(string $type,int $id,string $field,string $value):void{
 $path=vroom_build_path($type,$id);
 if(!is_file($path))return;
 vroom_disk_read_to_ram($type,$id,$path);
 $header=vroom_ram_get_header($type,$id);
 if($header===[])return;
 $header[$field]=$value;
 vroom_ram_set_header($type,$id,$header);
 vroom_ram_commit_record($type,$id,$path);
}
