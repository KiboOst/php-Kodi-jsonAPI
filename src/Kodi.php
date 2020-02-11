<?php

namespace openWebX\phpKodiApi;


/**
 * Class Kodi
 * @package openWebX\phpKodiApi
 */
class Kodi {

    public const KODI_FILTER_NONE = false;
    public const KODI_FILTER_GENRE = 1;
    public const KODI_FILTER_ARTIST = 2;

    public string $version = '0.99';
    public string $ip;

    //user functions======================================================
    //GET
    public ?string $error = null;
    public ?string $playerId = null;
    public ?string $playerType = null;
    public bool $debug = false;
    protected $curl = null;
    protected int $postId = 0;

    public function __construct(string $ip) {
        $this->ip = str_replace('http://', '', $ip);;
        $var = $this->getActivePlayer();
        if (isset($var['error']) ) {
            $this->error = $var['error'];
        }
    }

    public function setDebug(int $level = 0): void {
        $this->debug = (bool) $level;
    }

    public function getAudioSongsList(?int $filter = self::KODI_FILTER_NONE, string $value = ''): ?array {
        $filterStr = '';
        if ($filter === self::KODI_FILTER_GENRE || $filter === self::KODI_FILTER_ARTIST) {
            $filterStr = '"filter": {"field": "';
            switch ($filter) {
                case self::KODI_FILTER_GENRE:
                    $filterStr .= 'genre';
                    break;
                case self::KODI_FILTER_ARTIST:
                    $filterStr .= 'artist';
                    break;
            }
            $filterStr .= '", "operator": "is", "value": "'.$value.'"}';

        }
        $jsonString = '{"jsonrpc": "2.0", "id": "libSongs",
						"method": "AudioLibrary.GetSongs",
						"params": { '.$filterStr.',
									"properties": [ "artist", "album", "genre", "file"],
									"sort": { "order": "ascending", "method": "track", "ignorearticle": true }
								}
						}';
        return $this->_request($jsonString);
    }

    protected function _request($data=null, int $timeout=3) : ?array {
        if ($this->debug) {
            echo '_request | data: ', $data, '<br>';
        }
        if (!isset($this->curl)) {
            $this->curl = curl_init();
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 7);
            curl_setopt($this->curl, CURLOPT_TIMEOUT, 3);
        }
        curl_setopt($this->curl, CURLOPT_TIMEOUT, $timeout);

        //batch request or conform it:
        if ($data[0] !== '[') {
            $data = json_decode($data, true);
            $data['jsonrpc'] = '2.0';
            $data['id'] = $this->postId;
            $this->postId++;
            $payload = json_encode($data);
        } else {
            $payload = $data;
        }

        $url = 'http://'.$this->ip.'/jsonrpc';
        curl_setopt($this->curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload))
        );

        $url = 'http://'.$this->ip.'/jsonrpc';
        curl_setopt($this->curl, CURLOPT_URL, $url);
        if ($this->debug) {
            echo '_request | url: ', $url, '<br>';
        }

        $answer = curl_exec($this->curl);
        if(curl_errno($this->curl)) {
            return ['error'=>curl_error($this->curl)];
        }

        if ($answer == false) {
            return ['error'=>"Couldn't reach Kodi device."];
        }

        $answer = json_decode($answer, true);
        if (isset($answer['error']) ) {
            return ['result'=>null, 'error'=>$answer['error']];
        }
        return ['result'=>$answer['result']];
    }

    public function getAudioArtistsList(): ?array {
        $jsonString = '{"jsonrpc": "2.0", "id": 1,
					"method": "AudioLibrary.GetArtists",
					"params": { "properties": [ "genre" ],
						"sort": { "order": "ascending", "method": "artist",
						"ignorearticle": true }
						}
					}';
        return $this->_request($jsonString);
    }

    public function getAudioAlbumsList(): ?array {
        $jsonString = '{"jsonrpc": "2.0", "id": 1,
					"method": "AudioLibrary.GetAlbums",
					"params": { "properties": [ "artist" ],
						"sort": { "order": "ascending", "method": "artist",
						"ignorearticle": true }
						}
					}';
        return $this->_request($jsonString);
    }

    public function getPlayerItem($playerid=null) {
        if ( !isset($playerid) ) {
            $playerid = $this->getActivePlayer();
        }
        if ( is_array($playerid) ) {
            return $playerid;
        }

        $jsonString = '{
						"method":"Player.GetItem",
						"params":{
									"properties": ["title", "album", "artist", "duration", "file"],
									"playerid": '.$playerid.'
								}
						}';

        return $this->_request($jsonString);
    }

    public function getActivePlayer() {
        $jsonString = '{"method":"Player.GetActivePlayers"}';
        $answer = $this->_request($jsonString);
        if (isset($answer['error']) ) {
            return ['result'=>null, 'error'=>$answer['error']];
        }

        if (count($answer['result'])>0) {
            $this->playerId = $answer['result'][0]['playerid'];
            $this->playerType = $answer['result'][0]['type'];
            return $this->playerId;
        }
        return ['error'=>'No active player.'];
    }

    public function getPlayList($playlistid=null)  {
        if ( !isset($playlistid) ) $playlistid = $this->getActivePlayer();
        if ( is_array($playlistid) ) $playlistid = 0;

        if ($playlistid == 0)
        {
            $jsonString = '{"method":"Playlist.GetItems",
						"params":{
									"properties": ["title", "album", "artist", "duration"],
									"playlistid": 0 }
						}';
        }
        else
        {
            $jsonString = '{"method":"Playlist.GetItems",
						"params":{
									"properties": ["runtime", "showtitle", "season", "title", "artist"],
									"playlistid": 1 }
						}';
        }

        return $this->_request($jsonString);
    }

    public function getDirectory(string $folder, int $typeInt = 0) {
        switch ($typeInt) {
            case 0:
                $type = 'music';
                break;
            case 1:
                $type = 'video';
                break;
            case 2:
                $type = 'picture';
                break;
        }


        $jsonString = '{"method":"Files.GetDirectory",
						"params":{"directory":"'.$folder.'",
						"media":"'.$type.'"
						}}';

        return $this->_request($jsonString);
    }

    public function getTime($playerid=null) {
        return $this->PlayerGetProperties('time', $playerid);
    }

    protected function PlayerGetProperties($prop, $playerid) {
        $currentPlayer = $this->getActivePlayer();
        if ( !isset($playerid) ) $playerid = $currentPlayer;
        if ( is_array($playerid) ) return $playerid;

        if ( $currentPlayer != $playerid ) return array('error'=>"Player ID ".$playerid." isn't active!");

        $jsonString = '{"method":"Player.GetProperties",
						"params":{"properties": ["'.$prop.'"], "playerid": '.$playerid.'}
						}';

        return $this->_request($jsonString);
    }

    public function getShuffle($playerid=null) {
        return $this->PlayerGetProperties('shuffled', $playerid);
    }

    public function getRepeat($playerid=null) {
        return $this->PlayerGetProperties('repeat', $playerid);
    }


    public function play($playlistid=null) {
        if ( !isset($playlistid) ) $playlistid = $this->getActivePlayer();
        if ( is_array($playlistid) ) $playlistid = 0;

        $jsonString = '{
						"method":"Player.Open",
						"params":{ "item": { "playlistid": '.$playlistid.', "position": 0 } }
						}';

        return $this->_request($jsonString);
    }

    public function stop($playerid=null) {
        if ( !isset($playerid) ) $playerid = $this->getActivePlayer();
        if ( is_array($playerid) ) $playerid = 0;

        $jsonString = '{
						"method":"Player.Stop",
						"params":{"playerid":'.$playerid.'}
						}';

        return $this->_request($jsonString);
    }

    public function openFile($file) {
        //$file = urlencode($file);
        $jsonString = '{"method":"Player.Open",
						"params":{"item":{"file":"'.$file.'"}}}';

        return $this->_request($jsonString);
    }

    public function openDirectory($folder) {
        $jsonString = '{"method":"Player.Open",
						"params":{"item":{"directory":"'.$folder.'"}}}';

        return $this->_request($jsonString);
    }

    public function clearPlayList($playlistid=null) {
        if ( !isset($playlistid) ) $playlistid = $this->getActivePlayer();
        if ( is_array($playlistid) ) $playlistid = 0;

        $jsonString = '{"method":"Playlist.Clear",
						"params":{"playlistid":'.$playlistid.'}
						}';

        return $this->_request($jsonString);
    }

    public function loadPlaylist($playlist, $type=0) {
        if ($type == 0) $media = 'music';
        if ($type == 1) $media = 'video';
        if ($type == 2) $media = 'picture';

        $jsonString = '{"method": "Playlist.Add",
						"params":{"playlistid":'.$type.',
								  "item":{"directory": "'.$playlist.'", "media": "'.$media.'"}
								}
						}';

        return $this->_request($jsonString, 30);
    }

    //System

    public function addPlayListDir($folder=null, $playlistid=null) {
        if ( !isset($playlistid) ) $playlistid = $this->getActivePlayer();
        if ( is_array($playlistid) ) $playlistid = 0;

        $jsonString = '{"method":"Playlist.Add",
						"params":{
								"playlistid":'.$playlistid.', "item": {"directory":"'.$folder.'"}
								}
						}';

        return $this->_request($jsonString, 30);
    }

    public function addPlayListFile($file=null, $playlistid=null) {
        if ( !isset($playlistid) ) $playlistid = $this->getActivePlayer();
        if ( is_array($playlistid) ) $playlistid = 0;

        $jsonString = '{"method":"Playlist.Add",
						"params":{
								"playlistid":'.$playlistid.', "item": {"file":"'.$file.'"}
								}
						}';

        return $this->_request($jsonString);
    }

    public function togglePlayPause($playerid=null) {
        if ( !isset($playerid) ) $playerid = $this->getActivePlayer();
        if ( is_array($playerid) ) return $playerid;

        $jsonString = '{"method":"Player.PlayPause",
						"params":{
									"playerid": '.$playerid.'
								}
						}';

        return $this->_request($jsonString);
    }

    public function setShuffle($value=true, $playerid=null) {
        if ( !isset($playerid) ) $playerid = $this->getActivePlayer();
        if ( is_array($playerid) ) return $playerid;

        $set = ( ($value == true) ? 'true' : 'false' );

        $jsonString = '{"method":"Player.SetShuffle",
						"params":{
									"playerid": '.$playerid.',
									"shuffle":'.$set.'
								}
						}';

        return $this->_request($jsonString);
    }


    //internal functions==================================================

    public function setRepeat($value="all", $playerid=null) {
        if ( !isset($playerid) ) $playerid = $this->getActivePlayer();
        if ( is_array($playerid) ) return $playerid;

        $jsonString = '{"method":"Player.SetRepeat",
						"params":{
									"playerid": '.$playerid.',
									"repeat":"'.$value.'"
								}
						}';

        return $this->_request($jsonString);
    }

    //calling functions===================================================



    public function getVolume() : ?array {
        $jsonString = '{
						"method":"Application.GetProperties",
						"params":{"properties": ["volume"]}
						}';

        return $this->_request($jsonString);
    }

    public function setVolume(int $level = 30) : ?array {
        $jsonString = '{"method":"Application.SetVolume",
						"params":{"volume":'.$level.'}
						}';

        return $this->_request($jsonString);
    }

    public function volumeUp(int $delta = 5) : ?array {
        $vol = $this->getVolume();
        if ($vol) {
            $vol = $vol['result']['volume'];
            return $this->setVolume($vol + $delta);
        }
        return null;
    }

    public function volumeDown(int $delta = 5) : ?array {
        $vol = $this->getVolume();
        if ($vol) {
            $vol = $vol['result']['volume'];
            return $this->setVolume($vol - abs($delta));
        }
        return null;
    }

    public function setMute(bool $mute=false)  {
        $jsonString = '{"method":"Application.SetMute",
						"params":{"mute":"toggle"}
						}';
        $answer = $this->_request($jsonString);
        $state = $answer['result'];
        if ($state != $mute) {
            $this->setMute($mute);
        }
        else return $answer;
    }

    public function reboot() {
        return $this->_request('{"method":"System.Reboot"}');
    }

    public function hibernate() {
        return $this->_request('{"method":"System.Hibernate"}');
    }

    public function shutdown() {
        return $this->_request('{"method":"System.Shutdown"}');
    }

    public function suspend() {
        return $this->_request('{"method":"System.Suspend"}');
    }

    public function sendJson($jsonString, $timeout=3) {
        return $this->_request($jsonString, $timeout);
    }

    /*
    playerid 0: music
    palyerid 1: video
    palyerid 2: picture

    playlist 0: current music playlist
    playlist 1: current video playlist

    http://kodi.wiki/view/JSON-RPC_API/v8

    */

//Kodi end
}
