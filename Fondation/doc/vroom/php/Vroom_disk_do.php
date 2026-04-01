<?php
declare(strict_types=1);

function vroom_disk_do_write_now(string $type,int $id,string $path,array $header,array $data):void{
 global $VROOM_MARKER;
 if(!vroom_lock_acquire($path))return;
 $dir=dirname($path);
 if(!is_dir($dir))mkdir($dir,0775,true);
 $lines=[];
 foreach($header as $k=>$v)$lines[]=$k.':'.str_replace(["\r","\n"],' ',(string)$v);
 $lines[]='';
 $payload=base64_encode(json_encode($data,JSON_UNESCAPED_UNICODE));
 $lines[]=$payload;
 $lines[]=$VROOM_MARKER;
 $content=implode("\n",$lines)."\n";
 $tmp=$path.'.tmp.'.bin2hex(random_bytes(4));
 file_put_contents($tmp,$content,LOCK_EX);
 rename($tmp,$path);
 chmod($path,0664);
 vroom_lock_release($path);
 vroom_disk_cleanup(dirname($path));
}

function vroom_disk_do_delete_now(string $type,int $id,string $path):void{
 if(!vroom_lock_acquire($path))return;
 if(is_file($path))unlink($path);
 vroom_lock_release($path);
 vroom_disk_cleanup(dirname($path));
}

function vroom_disk_cleanup(string $dir):void{
 global $VROOM_MARKER;
 if(!is_dir($dir))return;
 $h=opendir($dir);
 if($h===false)return;
 while(($f=readdir($h))!==false){
  if($f==='.'||$f==='..')continue;
  $p=$dir.'/'.$f;
  if(is_dir($p))continue;
  if(str_starts_with($f,'.tmp.'))unlink($p);
  elseif(str_ends_with($f,'.lock')){
   if(time()-filemtime($p)>60)unlink($p);
  }elseif(str_ends_with($f,'.vrec')){
   $c=@file_get_contents($p);
   if($c===false||$c===''){unlink($p);continue;}
   $c=str_replace("\r","\n",$c);
   $lines=explode("\n",trim($c,"\n"));
   if(end($lines)!==$VROOM_MARKER)unlink($p);
  }
 }
 closedir($h);
}
