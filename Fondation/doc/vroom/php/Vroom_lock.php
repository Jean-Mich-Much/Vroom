<?php
declare(strict_types=1);

function vroom_lock_path(string $path):string{
 return $path.'.lock';
}

function vroom_lock_acquire(string $path):bool{
 global $VROOM_STATE;
 $lock=vroom_lock_path($path);
 $t=microtime(true);
 if(is_file($lock)){
  if($t-filemtime($lock)>60)unlink($lock);
  else return false;
 }
 $h=@fopen($lock,'x');
 if($h===false)return false;
 fclose($h);
 $VROOM_STATE['locks'][$path]=['acquired_at'=>$t];
 return true;
}

function vroom_lock_release(string $path):void{
 global $VROOM_STATE;
 $lock=vroom_lock_path($path);
 if(is_file($lock))unlink($lock);
 unset($VROOM_STATE['locks'][$path]);
}
