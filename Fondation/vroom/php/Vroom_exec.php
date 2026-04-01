<?php
declare(strict_types=1);

function vroom_exec(string $cmd):array{
 global $VROOM_STATE;
 $output=[];
 $return_var=0;
 exec($cmd.' 2>&1',$output,$return_var);
 $pid=getmypid();
 $VROOM_STATE['processes'][$pid]=[
  'cmd'=>$cmd,
  'time'=>microtime(true),
  'status'=>$return_var
 ];
 return ['output'=>$output,'status'=>$return_var,'pid'=>$pid];
}
