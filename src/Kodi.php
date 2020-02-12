<?php declare(strict_types=1);

namespace openWebX\phpKodiApi;


/**
 * Class Kodi
 * @package openWebX\phpKodiApi
 */
class Kodi {

    /**
     *
     */
    public const KODI_FILTER_NONE = false;
    /**
     *
     */
    public const KODI_FILTER_GENRE = 1;
    /**
     *
     */
    public const KODI_FILTER_ARTIST = 2;
    /**
     *
     */
    public const VERSION = '1.0';

    /**
     * @var string|string[]
     */
    public string $ip;

    /**
     * @var mixed|string|null
     */
    public ?string $error = null;
    /**
     * @var int|null
     */
    public ?int $playerId = null;
    /**
     * @var string|null
     */
    public ?string $playerType = null;
    /**
     * @var bool
     */
    public bool $debug = false;

    /**
     * @var
     */
    protected $curl;
    /**
     * @var int
     */
    protected int $postId = 0;

    /**
     * Kodi constructor.
     *
     * @param string $ip
     */
    public function __construct(string $ip) {
        $this->ip = str_replace('http://', '', $ip);
        $var = $this->getActivePlayer();
        if (isset($var['error']) ) {
            $this->error = $var['error'];
        }
    }

    /**
     * @param int|null $filter
     * @param string $value
     * @return array|null
     */
    public function getAudioSongsList(?int $filter = self::KODI_FILTER_NONE, string $value = ''): ?array {
        $filterStr = '';
        if ($filter === self::KODI_FILTER_GENRE || $filter === self::KODI_FILTER_ARTIST) {
            $filterStr = '
            "filter": {
                "field": "';
            switch ($filter) {
                case self::KODI_FILTER_GENRE:
                    $filterStr .= 'genre';
                    break;
                case self::KODI_FILTER_ARTIST:
                    $filterStr .= 'artist';
                    break;
            }
            $filterStr .= '", 
                "operator": "is", 
                "value": "' . $value . '"
            }';

        }
        $jsonString = '
        {
            "jsonrpc": "2.0", 
            "id": "libSongs",
			"method": "AudioLibrary.GetSongs",
			"params": { 
			    ' . $filterStr . ',
				"properties": [ "artist", "album", "genre", "file"],
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
     * @return array|null
     */
    public function getAudioArtistsList(): ?array {
        $jsonString = '
        {
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
     * @return array|null
     */
    public function getAudioAlbumsList(): ?array {
        $jsonString = '
        {
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
     * @param int|null $playerid
     * @return array|null
     */
    public function getPlayerItem(?int $playerid = null): ?array {
        if (is_array($id = $this->checkId($playerid, true))) {
            return $id;
        }
        $jsonString = '
        {
            "method":"Player.GetItem",
            "params":{
                "properties": ["title", "album", "artist", "duration", "file"],
                "playerid": ' . $id . '
            }
        }';
        return $this->_request($jsonString);
    }

    /**
     * @return array|int|null
     */
    public function getActivePlayer() {
        $jsonString = '
        {
            "method":"Player.GetActivePlayers"
        }';
        $answer = $this->_request($jsonString);
        if (isset($answer['error']) ) {
            return [
                'result' => null,
                'error' => $answer['error']
            ];
        }

        if (count($answer['result'])>0) {
            $this->playerId = (int) $answer['result'][0]['playerid'];
            $this->playerType = $answer['result'][0]['type'];
            return $this->playerId;
        }
        return [
            'error' => 'No active player.'
        ];
    }

    /**
     * @param int|null $playlistid
     * @return array|null
     */
    public function getPlayList(?int $playlistid = null): ?array {
        $id = $this->checkId($playlistid);
        $params = ($id === 0) ?
            '
                    "properties": ["title", "album", "artist", "duration"],
					"playlistid": 0
            '
            :
            '
                    "properties": ["runtime", "showtitle", "season", "title", "artist"],
					"playlistid": 1
            ';

        $jsonString = '
            {
                "method":"Playlist.GetItems",
				"params":{
					' . $params . ' 
				}
			}';
        return $this->_request($jsonString);
    }

    /**
     * @param string $folder
     * @param int $typeInt
     * @return array|null
     */
    public function getDirectory(string $folder, int $typeInt = 0): ?array {
        $type = $this->typeToString($typeInt);
        $jsonString = '
        {
            "method":"Files.GetDirectory",
			"params":{
			    "directory":"' . $folder . '",
				"media":"' . $type . '"
			}
		}';
        return $this->_request($jsonString);
    }

    /**
     * @param int|null $playerid
     * @return array|int|null
     */
    public function getTime(?int $playerid = null) {
        return $this->playerGetProperties('time', $playerid);
    }

    /**
     * @param string $prop
     * @param $playerid
     * @return array|int|null
     */
    protected function playerGetProperties(string $prop, ?int $playerid = null) {
        $currentPlayer = $this->getActivePlayer();
        if (is_array($id = $this->checkId($playerid, true))) {
            return $id;
        }

        if ( $currentPlayer !== $id ) {
            return [
                'error' => 'Player ID ' . $id . ' is not active!'
            ];
        }

        $jsonString = '
        {
            "method":"Player.GetProperties",
			"params":{
			    "properties": ["' . $prop . '"], 
			    "playerid": ' . $id . '
			}
		}';

        return $this->_request($jsonString);
    }

    /**
     * @param int|null $playerid
     * @return array|int|null
     */
    public function getShuffle(?int $playerid = null) {
        return $this->playerGetProperties('shuffled', $playerid);
    }

    /**
     * @param int|null $playerid
     * @return array|int|null
     */
    public function getRepeat(?int $playerid = null) {
        return $this->playerGetProperties('repeat', $playerid);
    }


    /**
     * @param int|null $playlistid
     * @return array|null
     */
    public function play(?int $playlistid = null): ?array {
        $jsonString = '
        {
			"method":"Player.Open",
			"params":{ 
			    "item": { 
			        "playlistid": ' . $this->checkId($playlistid) . ', 
			        "position": 0 
			    } 
			}
		}';

        return $this->_request($jsonString);
    }

    /**
     * @param int|null $playerid
     * @return array|null
     */
    public function stop(?int $playerid = null): ?array {
        $jsonString = '
        {
			"method":"Player.Stop",
			"params":{
			    "playerid":' . $this->checkId($playerid) . '
			}
		}';

        return $this->_request($jsonString);
    }

    /**
     * @param string $file
     * @return array|null
     */
    public function openFile(string $file): ?array {
        $jsonString = '
        {
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
     * @param string $folder
     * @return array|null
     */
    public function openDirectory(string $folder): ?array {
        $jsonString = '
        {
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
     * @param int|null $playlistid
     * @return array|null
     */
    public function playlistClear(?int $playlistid=null): ?array {
        $jsonString = '
        {
            "method":"Playlist.Clear",
			"params":{
			    "playlistid":' . $this->checkId($playlistid) . '
			}
		}';

        return $this->_request($jsonString);
    }

    /**
     * @param string $playlist
     * @param int $type
     * @return array|null
     */
    public function playlistLoad(string $playlist, int $type = 0): ?array {
        $media = $this->typeToString($type);

        $jsonString = '
        {
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

    //System

    /**
     * @param string|null $folder
     * @param int|null $playlistid
     * @return array|null
     */
    public function playlistAddDir(?string $folder = null, ?int $playlistid = null): ?array {
        $jsonString = '
        {
            "method":"Playlist.Add",
			"params":{
				"playlistid":' . $this->checkId($playlistid) . ', 
				"item": {
				    "directory":"' . $folder . '"
				}
			}
		}';
        return $this->_request($jsonString, 30);
    }

    /**
     * @param string|null $file
     * @param null $playlistid
     * @return array|null
     */
    public function playlistAddFile(?string $file = null, $playlistid = null): ?array {
        $jsonString = '
        {
            "method":"Playlist.Add",
			"params":{
				"playlistid":' . $this->checkId($playlistid) . ', 
				"item": {
				    "file":"' . $file . '"
				}
			}
		}';
        return $this->_request($jsonString);
    }

    /**
     * @param int|null $playerid
     * @return array|int|null
     */
    public function togglePlayPause(?int $playerid = null) {
        if (is_array($id = $this->checkId($playerid, true))) {
            return $id;
        }

        $jsonString = '{
            "method":"Player.PlayPause",
			"params":{
				"playerid": ' . $id . '
			}
		}';

        return $this->_request($jsonString);
    }

    /**
     * @param bool $value
     * @param int|null $playerid
     * @return array|int|null
     */
    public function setShuffle(bool $value = true, ?int $playerid = null) {
        if (is_array($id = $this->checkId($playerid, true))) {
            return $id;
        }

        $jsonString = '
        {
            "method":"Player.SetShuffle",
			"params":{
				"playerid": ' . $id . ',
				"shuffle":' . ($value ? 'true' : 'false') . '
			}
		}';
        return $this->_request($jsonString);
    }


    //internal functions==================================================

    /**
     * @param string $value
     * @param int|null $playerid
     * @return array|int|null
     */
    public function setRepeat(string $value = 'all', ?int $playerid = null) {
        if (is_array($id = $this->checkId($playerid, true))) {
            return $id;
        }

        $jsonString = '
        {
            "method":"Player.SetRepeat",
			"params":{
				"playerid": ' . $id . ',
				"repeat":"' . $value . '"
			}
		}';

        return $this->_request($jsonString);
    }

    //calling functions===================================================


    /**
     * @return array|null
     */
    public function getVolume() : ?array {
        $jsonString = '
        {
		    "method":"Application.GetProperties",
			"params":{
			    "properties": ["volume"]
			}
		}';
        return $this->_request($jsonString);
    }

    /**
     * @param int $level
     * @return array|null
     */
    public function setVolume(int $level = 30) : ?array {
        $jsonString = '
        {
            "method":"Application.SetVolume",
			"params":{
			    "volume":' . $level . '
			}
		}';
        return $this->_request($jsonString);
    }

    /**
     * @param int $delta
     * @return array|null
     */
    public function volumeUp(int $delta = 5) : ?array {
        $vol = $this->getVolume();
        if ($vol) {
            $vol = $vol['result']['volume'];
            return $this->setVolume($vol + $delta);
        }
        return null;
    }

    /**
     * @param int $delta
     * @return array|null
     */
    public function volumeDown(int $delta = 5) : ?array {
        $vol = $this->getVolume();
        if ($vol) {
            $vol = $vol['result']['volume'];
            return $this->setVolume($vol - abs($delta));
        }
        return null;
    }

    /**
     * @param bool $mute
     * @return array|null
     */
    public function setMute(bool $mute=false): ?array {
        $jsonString = '
        {
            "method":"Application.SetMute",
		    "params":{
			    "mute":"toggle"
			}
		}';
        $answer = $this->_request($jsonString);
        $state = $answer['result'];
        if ($state !== $mute) {
            $this->setMute($mute);
        }
        return $answer;
    }

    /**
     * @return array|null
     */
    public function reboot(): ?array {
        return $this->_request('{"method":"System.Reboot"}');
    }

    /**
     * @return array|null
     */
    public function hibernate(): ?array {
        return $this->_request('{"method":"System.Hibernate"}');
    }

    /**
     * @return array|null
     */
    public function shutdown(): ?array {
        return $this->_request('{"method":"System.Shutdown"}');
    }

    /**
     * @return array|null
     */
    public function suspend(): ?array {
        return $this->_request('{"method":"System.Suspend"}');
    }

    /**
     * @param $jsonString
     * @param int $timeout
     * @return array|null
     */
    public function sendJson($jsonString, $timeout=3): ?array {
        return $this->_request($jsonString, $timeout);
    }

    /**
     * @param string $data
     * @param int $timeout
     * @return array|null
     */
    private function _request(string $data, int $timeout=3) : ?array {
        if ($this->debug) {
            echo '_request | data: ' . $data . PHP_EOL;
        }
        if (!isset($this->curl)) {
            $this->curl = curl_init();
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($this->curl, CURLOPT_TIMEOUT, $timeout);
        }
        curl_setopt($this->curl, CURLOPT_TIMEOUT, $timeout);

        //batch request or conform it:
        if ($data[0] !== '[') {
            $data = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
            $data['jsonrpc'] = '2.0';
            $data['id'] = $this->postId;
            $this->postId++;
            $payload = json_encode($data, JSON_THROW_ON_ERROR, 512);
        } else {
            $payload = $data;
        }

        curl_setopt($this->curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload))
        );

        $url = 'http://' . $this->ip . '/jsonrpc';
        curl_setopt($this->curl, CURLOPT_URL, $url);
        if ($this->debug) {
            echo '_request | url: ' .  $url . PHP_EOL;
        }

        $answer = curl_exec($this->curl);
        if(curl_errno($this->curl)) {
            return [
                'error' => curl_error($this->curl)
            ];
        }

        if ($answer === false) {
            return [
                'error' => "Couldn't reach Kodi device."
            ];
        }

        $answer = json_decode($answer, true, 512, JSON_THROW_ON_ERROR);
        if (isset($answer['error']) ) {
            return [
                'result' => null,
                'error' => $answer['error']
            ];
        }
        return [
            'result' => $answer['result']
        ];
    }

    /**
     * @param int $typeInt
     * @return string|null
     */
    private function typeToString(int $typeInt) : ?string {
        switch ($typeInt) {
            case 0:
                return 'music';
            case 1:
                return 'video';
            case 2:
                return 'picture';
        }
        return null;
    }

    /**
     * @param int|null $givenId
     * @param bool $keepArray
     * @return array|int|null
     */
    private function checkId(?int $givenId = null, bool $keepArray = false)  {
        $id = null;
        if (!isset($givenId)) {
            $id = $this->getActivePlayer();
        }
        if (is_array($id)) {
            if ($keepArray === true) {
                return $id;
            }
            $id = 0;

        }
        return $id;
    }

}
