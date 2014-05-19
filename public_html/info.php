<?php

#phpinfo();

$pid = pcntl_fork();
if ($pid == -1) {
     echo 'could not fork';
} 
elseif ($pid) {
     pcntl_wait($status);
     echo "wait";
} 
else {
    echo "we are child";
}
