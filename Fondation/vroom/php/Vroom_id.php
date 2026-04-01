<?php
declare(strict_types=1);

function vroom_next_id(string $type):int{
 global $VROOM_STATE,$VROOM_BASE_DIR;
 if(!isset($VROOM_STATE['ids'][$type])){
  $file=$VROOM_BASE_DIR.'/ids_'.$type.'.idx';
  $last=0;
  if(is_file($file)){
   $c=trim((string)file_get_contents($file));
   if($c!=='')$last=(int)$c;
  }
  $VROOM_STATE['ids'][$type]=$last;
 }
 $VROOM_STATE['ids'][$type]++;
 file_put_contents($VROOM_BASE_DIR.'/ids_'.$type.'.idx',(string)$VROOM_STATE['ids'][$type],LOCK_EX);
 return $VROOM_STATE['ids'][$type];
}
