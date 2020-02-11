<?php

/*

endpoint for actions (IFTTT or others...)

call:
http://www.mydomain.com/path/to/action.php?IP=somedyndns:port&action=autoPlay

*/

require($_SERVER['DOCUMENT_ROOT']."/path/to/phpKodi-api.php");

$IP = 'defaultIP'; //if you got several kodi...

if(isset($_GET['IP'])) $IP = $_GET['IP'];
if(isset($_GET['action']))
{
    $action = $_GET['action'];
    $_Kodi = new Kodi($IP);
    if (isset($_Kodi->error)) die($_Kodi->error);

    call_user_func($action); //use same name in url and your function
}

//actions:
function autoPlay()
{
    global $_Kodi;
    $_Kodi->clearPlayList();
    $pl = 'special://profile/playlists/mymusic.xsp';
    $_Kodi->loadPlaylist($pl);
    $_Kodi->play();
    $_Kodi->setVolume(45);
    $_Kodi->setShuffle(true);
    $_Kodi->setRepeat('all');
}
