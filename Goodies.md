
# php Kodi json API -- Goodies

Here are some scripts based on [Kodi-jsonAPI](https://github.com/KiboOst/php-Kodi-jsonAPI), that I use for home automation (Jeedom, SNIPS etc).

### Starting playing a particular audio album

Kodi RPC interface doesn't provide a way to start an album by its title (or label), because this is not a unique identifier. You could have tons of albums with exact same name. So we need to get its unique id because asking to play it.
Of course this it a simple example, it would be better to make a function with it.

```php
$albumList = $_Kodi->getAudioAlbumsList();
$albumList = $albumList['result']['albums'];
$playId = false;
foreach ($albumList as $album)
{
	//echo 'album:', $album['label'], $album['albumid'], "<br>";
	if ($album['label'] == 'Abbey Road')
	{
		$playId = $album['albumid'];
		break;
	}
}
if ($playId)
{
	$jsonString = '{"jsonrpc":"2.0", "id": 1, "method":"Player.Open","params":{"item": {"albumid": '.intval($playId).'}}}';
	$req = $_Kodi->sendJson($jsonString, 20);
	echo "<pre>req:<br>".json_encode($req, JSON_PRETTY_PRINT)."</pre><br>";
}
```



### Launching albums by music genre:

```php
playGenreSongs('Jazz');

function playGenreSongs($genre='Jazz')
{
	global $_Kodi;
	//batch request have some limits...
	$maxConcurrentRequests = 120;

	//get all songs with genre:
	$songsList = $_Kodi->getAudioSongsList(1, $genre);

	//get array of songs file path:
	$songsFiles = array();
	foreach ($songsList['result']['songs'] as $song) {
		array_push($songsFiles, $song['file']);
	}
	$songsCount = count($songsFiles) - 1;
	if ($songsCount == 0) return False;

	//clean player and send the first song fast!
	$_Kodi->clearPlayList();
	$_Kodi->addPlayListFile($songsFiles[0], 0);
	$_Kodi->play();

	array_shift($songsFiles); //first song ever sent
	shuffle($songsFiles);
	if ($songsCount < $maxConcurrentRequests) $maxConcurrentRequests = $songsCount;

	//build batch request string:
	$jsonString = '{"jsonrpc":"2.0","method":"Playlist.Add","params":{"playlistid":0,"item":{"file":"__FILE__"}}}';

	//write not too long batch request:
	$fullString = '[';

	for ($i = 0; $i <= $maxConcurrentRequests; $i++)
	{
		echo $i, $songsFiles[$i], "<br>";
		$thisJsonString = str_replace('__FILE__', urlencode($songsFiles[$i]), $jsonString);
		$fullString .= $thisJsonString.',';
	}
	$fullString = rtrim($fullString, ',');
	$fullString .= ']';

	//send that to Kodi:
	$req = $_Kodi->sendJson($fullString, 20);
	//echo "<pre>req:<br>".json_encode($req, JSON_PRETTY_PRINT)."</pre><br>";

	return true;
}
```

### Get actual audio playing:
Once more, this should be wrap up in a function, but I will let you adapt it to yours needs.

```php
$jsonString = '{"jsonrpc":"2.0",
				"method":"Player.GetItem",
				"params":{"properties": ["title","album","artist"], "playerid": 0}
				}';

$req = $_Kodi->sendJson($jsonString);
echo "<pre>req:<br>".json_encode($req, JSON_PRETTY_PRINT)."</pre><br>";

$artist = (isset($req['result']['item']['artist']) ? $req['result']['item']['artist'] : 'aucun');
$title = (isset($req['result']['item']['title']) ? $req['result']['item']['title'] : 'aucun');
$album = (isset($req['result']['item']['album']) ? $req['result']['item']['album'] : 'aucun');

if ($artist != 'aucun') $sayTTS = "Vous écoutez ".$req['result']['item']['title'].", de ".$req['result']['item']['artist'][0];
else $sayTTS = "Désolé, mais il n'y a pas de lecture en cours.";

echo 'sayTTS: ', $sayTTS, "<br>";
```


## License

The MIT License (MIT)

Copyright (c) 2018 KiboOst

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation  files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT  NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NON  INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,  DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
