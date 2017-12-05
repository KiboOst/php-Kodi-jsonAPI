# php Kodi json API

## Easy control of Kodi device(s) through http json-rpc interface.
(C) 2017, KiboOst

This API is a php class with easy functions for your Kodi device.

- This API use json-rpc http interface and require enabling it:
- In System->Settings->Network->Services activate Allow control of Kodi via HTTP

Feel free to submit an issue or pull request to add more.

## How-to

- Download the class/phpKodi-api.php on your server.
- Include it in your script.
- Start it with your device IP.

- You can use a local IP ('192.168.1.120'), dyndns ('mydynname.ddns.net'), and include pass/port ('user:pass@IP:Port'), regarding if you use your script locally or on a web server.

```php
require($_SERVER['DOCUMENT_ROOT']."/path/to/phpKodi-api.php");
$_Kodi = new Kodi($IP);
if (isset($_Kodi->error)) die($_Kodi->error);
```

Players functions:

```php
//get current active player:
$getActivePlayer = $_Kodi->getActivePlayer();
echo "<pre>getActivePlayer:<br>".json_encode($getActivePlayer, JSON_PRETTY_PRINT)."</pre><br>";

//start current player, or provide argument 0 for music, 1 for video, 2 for pictures
$_Kodi->play();

//stop current player:
$_Kodi->stop();

//get or set volume:
$getVolume = $_Kodi->getVolume();
echo "<pre>getVolume:<br>".json_encode($getVolume, JSON_PRETTY_PRINT)."</pre><br>";

$_Kodi->setVolume(40);

//set mute (true/false):
$_Kodi->setMute(true)

//get or set shuffle playing true/false
$dev = $_Kodi->getShuffle();
echo "<pre>dev:<br>".json_encode($dev, JSON_PRETTY_PRINT)."</pre><br>";

$_Kodi->setShuffle(true);

//get repeat:
$dev = $_Kodi->getRepeat();
echo "<pre>dev:<br>".json_encode($dev, JSON_PRETTY_PRINT)."</pre><br>";

//set repeat one, all, off
$_Kodi->setRepeat('all');

//get playing time:
$getTime = $_Kodi->getTime();
echo "<pre>getTime:<br>".json_encode($getTime, JSON_PRETTY_PRINT)."</pre><br>";
```

Playlists functions:

```php
//get current playlist items, or provide argument 0 for music, 1 for video, 2 for pictures:
$getPlayerItem = $_Kodi->getPlayerItem();
echo "<pre>getPlayerItem:<br>".json_encode($getPlayerItem, JSON_PRETTY_PRINT)."</pre><br>";

//clear current playlist, or provide argument 0 for music, 1 for video, 2 for pictures:
$_Kodi->clearPlayList();

//add a playlist file to current playlist:
//if the playlist contains tons of files, it can return a timeout error but playlist will load.
$_Kodi->loadPlaylist('special://profile/playlists/music/iJazz.xsp');

//open a file. This will play it automatically.
$_Kodi->openFile("smb://NAS/hollidays2017/brittany_01_1080p.mkv");

//get items by type in a folder. 0 for music, 1 for video, 2 for pictures:
$getDirectory = $_Kodi->getDirectory("smb://NAS/videos/", 1);
echo "<pre>getDirectory:<br>".json_encode($getDirectory, JSON_PRETTY_PRINT)."</pre><br>";

//add a file or directory to current playlist:
$_Kodi->addPlayListDir("smb://NAS/videos/");
$_Kodi->addPlayListFile("smb://NAS/videos/myVideo.mkv");
```

System functions:

```php
$_Kodi->reboot();
$_Kodi->hibernate();
$_Kodi->shutdown();
$_Kodi->suspend();
```

Special:

```php
$jsonString = '{"jsonrpc":"2.0","id":1,
		"method":"Player.GetProperties",
		"params":{"properties": ["canshuffle"], "playerid": 0}
		}';

$dev = $_Kodi->sendJson($jsonString);
echo "<pre>dev:<br>".json_encode($dev, JSON_PRETTY_PRINT)."</pre><br>";
```


## IFTTT

You can create an endpoint url for triggering stuff from IFTTT. See IFTTTactions.php example.

## Changes

#### v0.22 (2017-11-05)
- New: $_Kodi->setMute()

#### v0.2 (2017-03-22)
- New: $_Kodi->reboot()
- New: $_Kodi->hibernate()
- New: $_Kodi->shutdown()
- New: $_Kodi->suspend()
- New: Send custom json command with $_Kodi->sendJson($jsonString, $timeout);

#### v0.1 (2017-03-21)
- First public version.

## License

The MIT License (MIT)

Copyright (c) 2017 KiboOst

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
