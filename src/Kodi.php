<?php

namespace openWebX\phpKodiApi;


/**
 * Class Kodi
 * @package openWebX\phpKodiApi
 */
class Kodi {

    /**
     * @var string
     */
    public $_version = "0.5";

    /**
     * @var mixed
     */
    public $_IP;
    /**
     * @var mixed
     */
    public $_error;
    /**
     * @var
     */
    public $_playerid;
    /**
     * @var
     */
    public $_playerType;

    /**
     * @var null
     */
    protected $_curlHdl = null;
    /**
     * @var int
     */
    protected $_POSTid = 0;
    /**
     * @var bool
     */
    protected $_debug = false;

    /**
     * @var array
     */
    private $typeArray = [
        0 => 'music',
        1 => 'video',
        2 => 'picture'
    ];


    /**
     * Kodi constructor.
     * @param string $IP
     */
    public function __construct(string $IP, string $PORT = '80') {
        $IP = str_replace('http://', '', $IP);
        $this->_IP = $IP . ':' . $PORT;
        $var = $this->getActivePlayer();
        if (isset($var['error'])) {
            $this->_error = $var['error'];
        }
    }


    public function setDebug(bool $dbg = true) {
        $this->_debug = $dbg;
    }

    /**
     * @return array
     */
    public function getActivePlayer() {
        $jsonString = '{
            "jsonrpc": "2.0", 
            "method":"Player.GetActivePlayers",
            "id" : 1
         }';
        $answer = $this->_request($jsonString);

        if ($this->_debug) {
            var_dump($answer);
        }

        if (isset($answer['error'])) {
            return ['result' => null, 'error' => $answer['error']];
        }

        if (count($answer['result']) > 0) {
            $this->_playerid = $answer['result'][0]['playerid'];
            $this->_playerType = $answer['result'][0]['type'];
            return $this->_playerid;
        }
        return ['error' => 'No active player.'];
    }

    /**
     * @param bool $filter
     * @param string $value
     * @return array
     */
    public function getAudioSongsList($filter = false, $value = '') {
        $filterStr = '';
        if ($filter) {
            $filterStr = '"filter": {"field": "';
            $filterStr .= (($filter == 1) ? 'genre' : 'artist');
            $filterStr .= '", "operator": "is", "value": "' . $value . '"}';
        }
        $jsonString = '{
            "jsonrpc": "2.0", 
            "id": "libSongs",
            "method": "AudioLibrary.GetSongs",
            "params":   { 
                ' . $filterStr . ',
                "properties": [ 
                    "artist", 
                    "album", 
                    "genre", 
                    "file"
                ],
                "sort": { 
                    "order": "ascending", 
                    "method": "track", 
                    "ignorearticle": true 
                }
            }
        }';
        return $this->_request($jsonString);
    }

    /**
     * @return array
     */
    public function getAudioArtistsList() {
        $jsonString = '{
            "jsonrpc": "2.0", 
            "id": 1,
            "method": "AudioLibrary.GetArtists",
            "params": { 
                "properties": [ "genre" ],
                "sort": { 
                    "order": "ascending", 
                    "method": "artist",
                    "ignorearticle": true 
                }
            }
        }';
        return $this->_request($jsonString);
    }

    /**
     * @return array
     */
    public function getAudioAlbumsList() {
        $jsonString = '{
            "jsonrpc": "2.0", 
            "id": 1,
            "method": "AudioLibrary.GetAlbums",
            "params": { 
                "properties": [ "artist" ],
                "sort": { 
                    "order": "ascending", 
                    "method": "artist",
                    "ignorearticle": true 
                }
            }
        }';
        return $this->_request($jsonString);
    }

    /**
     * @param null $playerid
     * @return array|null
     */
    public function getPlayerItem($playerid = null) {
        if (!isset($playerid)) {
            $playerid = $this->getActivePlayer();
        }
        if (is_array($playerid)) {
            return $playerid;
        }

        $jsonString = '{
            "method":"Player.GetItem",
            "params":{
                "properties": [
                    "title", 
                    "album", 
                    "artist", 
                    "duration", 
                    "file"
                ],
                "playerid": ' . $playerid . '
            }
        }';

        return $this->_request($jsonString);
    }

    /**
     * @param null $playlistid
     * @return array
     */
    public function getPlayList($playlistid = null) //0: music, 1: video, 2:picture
    {
        if (!isset($playlistid)) {
            $playlistid = $this->getActivePlayer();
        }
        if (is_array($playlistid)) {
            $playlistid = 0;
        }

        if ($playlistid == 0) {
            $jsonString = '{
                "method":"Playlist.GetItems",
                "params":{
                    "properties": [
                        "title", 
                        "album", 
                        "artist", 
                        "duration"
                    ],
                    "playlistid": 0 }
                }';
        } else {
            $jsonString = '{
                "method":"Playlist.GetItems",
                "params":{
                    "properties": [
                        "runtime", 
                        "showtitle", 
                        "season", 
                        "title", 
                        "artist"
                    ],
                    "playlistid": 1 
                }
            }';
        }

        return $this->_request($jsonString);
    }

    /**
     * @param $folder
     * @param null $type
     * @return array
     */
    public function getDirectory($folder, $type = null) {
        $folder = urlencode($folder);

        if (isset($this->typeArray[$type])) {

            $jsonString = '{
                "method":"Files.GetDirectory",
                "params":{
                    "directory":"' . $folder . '",
                    "media":"' . $type . '"
                }
            }';

            return $this->_request($jsonString);
        }
        return [];
    }

    /**
     * @return array
     */
    public function getVolume() {
        $jsonString = '{
            "method":"Application.GetProperties",
            "params":{
                "properties": ["volume"]
            }
        }';
        return $this->_request($jsonString);
    }

    /**
     * @param null $playerid
     * @return array
     */
    public function getTime($playerid = null) {
        return $this->PlayerGetProperties('time', $playerid);
    }

    /**
     * @param null $playerid
     * @return array
     */
    public function getShuffle($playerid = null) {
        return $this->PlayerGetProperties('shuffled', $playerid);
    }

    /**
     * @param null $playerid
     * @return array
     */
    public function getRepeat($playerid = null) {
        return $this->PlayerGetProperties('repeat', $playerid);
    }

    //SET

    /**
     * @param null $playlistid
     * @return array
     */
    public function play($playlistid = null) {
        if (!isset($playlistid)) {
            $playlistid = $this->getActivePlayer();
        }
        if (is_array($playlistid)) {
            $playlistid = 0;
        }

        $jsonString = '{
            "method":"Player.Open",
            "params":{ 
                "item": { 
                    "playlistid": ' . $playlistid . ', 
                    "position": 0 
                } 
            }
        }';

        return $this->_request($jsonString);
    }

    /**
     * @param null $playerid
     * @return array
     */
    public function stop($playerid = null) {
        if (!isset($playerid)) {
            $playerid = $this->getActivePlayer();
        }
        if (is_array($playerid)) {
            $playerid = 0;
        }

        $jsonString = '{
            "method":"Player.Stop",
            "params":{
                "playerid":' . $playerid . '
            }
        }';

        return $this->_request($jsonString);
    }

    /**
     * @param $file
     * @return array
     */
    public function openFile($file) {
        $file = urlencode($file);
        $jsonString = '{
            "method":"Player.Open",
            "params":{
                "item":{
                    "file":"' . $file . '"
                }
            }
        }';

        return $this->_request($jsonString);
    }

    /**
     * @param $folder
     * @return array
     */
    public function openDirectory($folder) {
        $folder = urlencode($folder);
        $jsonString = '{
            "method":"Player.Open",
            "params":{
                "item":{
                    "directory":"' . $folder . '"
                }
            }
        }';
        return $this->_request($jsonString);
    }

    /**
     * @param null $playlistid
     * @return array
     */
    public function clearPlayList($playlistid = null) {
        if (!isset($playlistid)) {
            $playlistid = $this->getActivePlayer();
        }
        if (is_array($playlistid)) {
            $playlistid = 0;
        }

        $jsonString = '{
            "method":"Playlist.Clear",
            "params":{
                "playlistid":' . $playlistid . '
            }
        }';

        return $this->_request($jsonString);
    }

    /**
     * @param $playlist
     * @param int $type
     * @return array
     */
    public function loadPlaylist($playlist, $type = 0) {
        $playlist = urlencode($playlist);

        $media = $this->typeArray[$type];

        $jsonString = '{
            "method": "Playlist.Add",
            "params":{
                "playlistid":' . $type . ',
                "item":{
                    "directory": "' . $playlist . '", 
                    "media": "' . $media . '"
                }
            }
        }';

        return $this->_request($jsonString, 30);
    }

    /**
     * @param null $folder
     * @param null $playlistid
     * @return array
     */
    public function addPlayListDir($folder = null, $playlistid = null) {
        if (!isset($playlistid)) {
            $playlistid = $this->getActivePlayer();
        }
        if (is_array($playlistid)) {
            $playlistid = 0;
        }

        $folder = urlencode($folder);

        $jsonString = '{
            "method":"Playlist.Add",
            "params":{
                "playlistid":' . $playlistid . ', 
                "item": {
                    "directory":"' . $folder . '"
                }
            }
        }';
        return $this->_request($jsonString, 30);
    }

    /**
     * @param null $file
     * @param null $playlistid
     * @return array
     */
    public function addPlayListFile($file = null, $playlistid = null) {
        if (!isset($playlistid)) {
            $playlistid = $this->getActivePlayer();
        }
        if (is_array($playlistid)) {
            $playlistid = 0;
        }

        $file = urlencode($file);

        $jsonString = '{
            "method":"Playlist.Add",
            "params":{
                "playlistid":' . $playlistid . ', 
                "item": {
                    "file":"' . $file . '"
                }
            }
        }';

        return $this->_request($jsonString);
    }

    /**
     * @param null $playerid
     * @return array|null
     */
    public function togglePlayPause($playerid = null) {
        if (!isset($playerid)) {
            $playerid = $this->getActivePlayer();
        }
        if (is_array($playerid)) {
            return $playerid;
        }

        $jsonString = '{
            "method":"Player.PlayPause",
            "params":{
                "playerid": ' . $playerid . '
            }
        }';

        return $this->_request($jsonString);
    }

    /**
     * @param bool $value
     * @param null $playerid
     * @return array|null
     */
    public function setShuffle($value = true, $playerid = null) {
        if (!isset($playerid)) {
            $playerid = $this->getActivePlayer();
        }
        if (is_array($playerid)) {
            return $playerid;
        }

        $set = (($value == true) ? 'true' : 'false');

        $jsonString = '{
            "method":"Player.SetShuffle",
            "params":{
                "playerid": ' . $playerid . ',
                "shuffle":' . $set . '
            }
        }';

        return $this->_request($jsonString);
    }

    /**
     * @param string $value
     * @param null $playerid
     * @return array|null
     */
    public function setRepeat($value = "all", $playerid = null) {
        if (!isset($playerid)) {
            $playerid = $this->getActivePlayer();
        }
        if (is_array($playerid)) {
            return $playerid;
        }

        $jsonString = '{
            "method":"Player.SetRepeat",
            "params":{
                "playerid": ' . $playerid . ',
                "repeat":"' . $value . '"
            }
        }';

        return $this->_request($jsonString);
    }

    /**
     * @param int $level
     * @return array
     */
    public function setVolume($level = 30) {
        $jsonString = '{
            "method":"Application.SetVolume",
            "params":{
                "volume":' . $level . '
            }
        }';

        return $this->_request($jsonString);
    }

    /**
     * @param int $delta
     * @return array
     */
    public function volumeUp($delta = 5) {
        $vol = $this->getVolume();
        $vol = $vol['result']['volume'];
        $result = $this->setVolume($vol + $delta);
        return $result;
    }

    /**
     * @param int $delta
     * @return array
     */
    public function volumeDown($delta = 5) {
        $vol = $this->getVolume();
        $vol = $vol['result']['volume'];
        $result = $this->setVolume($vol - abs($delta));
        return $result;
    }

    /**
     * @param bool $mute
     * @return array
     */
    public function setMute($mute = false) {
        $jsonString = '{
            "method":"Application.SetMute",
            "params":{
                "mute":"toggle"
            }
        }';
        $answer = $this->_request($jsonString);
        $state = $answer['result'];
        if ($state != $mute) {
            $this->setMute($mute);
        } else {
            return $answer;
        }
    }

    //System

    /**
     * @return array
     */
    public function reboot() {
        return $this->_request('{"method":"System.Reboot"}');
    }

    /**
     * @return array
     */
    public function hibernate() {
        return $this->_request('{"method":"System.Hibernate"}');
    }

    /**
     * @return array
     */
    public function shutdown() {
        return $this->_request('{"method":"System.Shutdown"}');
    }

    /**
     * @return array
     */
    public function suspend() {
        return $this->_request('{"method":"System.Suspend"}');
    }


    //internal functions==================================================

    /**
     * @param $prop
     * @param $playerid
     * @return array
     */
    protected function PlayerGetProperties($prop, $playerid) {
        $currentPlayer = $this->getActivePlayer();
        if (!isset($playerid)) {
            $playerid = $currentPlayer;
        }
        if (is_array($playerid)) {
            return $playerid;
        }

        if ($currentPlayer != $playerid) {
            return [
                'error' => "Player ID " . $playerid . " isn't active!"
            ];
        }

        $jsonString = '{
            "method":"Player.GetProperties",
            "params":{
                "properties": ["' . $prop . '"], 
                "playerid": ' . $playerid . '
            }
        }';

        return $this->_request($jsonString);
    }

    //calling functions===================================================

    /**
     * @param $jsonString
     * @param int $timeout
     * @return array
     */
    public function sendJson($jsonString, $timeout = 3) {
        return $this->_request($jsonString, $timeout);
    }

    /**
     * @param $jsonString
     * @param int $timeout
     * @return array
     */
    protected function _request($jsonString, $timeout = 3) {
        if (!isset($this->_curlHdl)) {
            $this->_curlHdl = curl_init();
            curl_setopt($this->_curlHdl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->_curlHdl, CURLOPT_FOLLOWLOCATION, true);

            curl_setopt($this->_curlHdl, CURLOPT_CONNECTTIMEOUT, 7);
            curl_setopt($this->_curlHdl, CURLOPT_TIMEOUT, 3);
        }

        curl_setopt($this->_curlHdl, CURLOPT_TIMEOUT, $timeout);

        //not for batch requests:
        if ($jsonString[0] == '[') {
            if ($this->_debug) {
                echo '_request | Batch request detected', "<br>";
            }
            $url = "http://" . $this->_IP . "/jsonrpc?request=" . $jsonString;
        } else {
            $json = json_decode($jsonString, true);
            $json['jsonrpc'] = '2.0';
            $json['id'] = $this->_POSTid;
            $this->_POSTid++;
            $url = "http://" . $this->_IP . "/jsonrpc?request=" . json_encode($json);
        }

        if ($this->_debug) {
            echo '_request | url:', $url, "<br>";
        }

        curl_setopt($this->_curlHdl, CURLOPT_URL, $url);

        $answer = curl_exec($this->_curlHdl);
        if (curl_errno($this->_curlHdl)) {
            return [
                'error' => curl_error($this->_curlHdl)
            ];
        }

        if ($answer == false) {
            return [
                'error' => "Couldn't reach Kodi device."
            ];
        }

        $answer = json_decode($answer, true);
        if (isset($answer['error'])) {
            return [
                'result' => null,
                'error' => $answer['error']
            ];
        }
        return [
            'result' => $answer['result']
        ];
    }

}
