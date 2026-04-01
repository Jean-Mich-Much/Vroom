<?php
declare(strict_types=1);

function vroom_disk_read_to_ram(string $type,int $id,string $path):void{
 global $VROOM_MARKER;
 if(!is_file($path))return;
 $c=file_get_contents($path);
 if($c===false)return;
 $c=str_replace("\r","\n",$c);
 $lines=explode("\n",trim($c,"\n"));
 if(end($lines)!==$VROOM_MARKER)return;
 array_pop($lines);
 $header=[];
 $data=[];
 $mode='header';
 foreach($lines as $line){
  if($mode==='header'){
   if($line===''){ $mode='data'; continue; }
   $pos=strpos($line,':');
   if($pos===false)continue;
   $k=substr($line,0,$pos);
   $v=substr($line,$pos+1);
   $header[$k]=$v;
  }else{
   $json=base64_decode($line,true);
   if($json===false)$data=[];
   else{
    $arr=json_decode($json,true);
    if(is_array($arr))$data=$arr;
    else $data=[];
   }
   break;
  }
 }
 vroom_ram_set_header($type,$id,$header);
 vroom_ram_set_data($type,$id,$data);
}
