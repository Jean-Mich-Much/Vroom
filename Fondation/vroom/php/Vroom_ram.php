<?php
declare(strict_types=1);

function vroom_ram_load_empty(string $type,int $id,string $path):void{
 global $VROOM_STATE;
 if(!isset($VROOM_STATE['buffers'][$type]))$VROOM_STATE['buffers'][$type]=[];
 if(!isset($VROOM_STATE['headers'][$type]))$VROOM_STATE['headers'][$type]=[];
 $VROOM_STATE['buffers'][$type][$id]=[];
 $VROOM_STATE['headers'][$type][$id]=[];
}

function vroom_ram_set_data(string $type,int $id,array $data):void{
 global $VROOM_STATE;
 if(!isset($VROOM_STATE['buffers'][$type]))$VROOM_STATE['buffers'][$type]=[];
 $VROOM_STATE['buffers'][$type][$id]=$data;
}

function vroom_ram_get_data(string $type,int $id):array{
 global $VROOM_STATE;
 if(!isset($VROOM_STATE['buffers'][$type][$id]))return [];
 return $VROOM_STATE['buffers'][$type][$id];
}

function vroom_ram_set_header(string $type,int $id,array $header):void{
 global $VROOM_STATE;
 if(!isset($VROOM_STATE['headers'][$type]))$VROOM_STATE['headers'][$type]=[];
 $VROOM_STATE['headers'][$type][$id]=$header;
}

function vroom_ram_get_header(string $type,int $id):array{
 global $VROOM_STATE;
 if(!isset($VROOM_STATE['headers'][$type][$id]))return [];
 return $VROOM_STATE['headers'][$type][$id];
}

function vroom_ram_commit_record(string $type,int $id,string $path):void{
 global $VROOM_STATE;
 $header=vroom_ram_get_header($type,$id);
 $data=vroom_ram_get_data($type,$id);
 vroom_state_add_write($type,$id,$path,$header,$data);
}

function vroom_ram_forget(string $type,int $id):void{
 global $VROOM_STATE;
 unset($VROOM_STATE['buffers'][$type][$id]);
 unset($VROOM_STATE['headers'][$type][$id]);
}
