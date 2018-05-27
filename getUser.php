<?php
define('DIRECTACCESS', true);
require_once "apikey.php";
require_once "funcs.php";

session_start();

$steamid = isset($_SESSION['steamid'])? $_SESSION['steamid'] : 0;
if(!$steamid)
    return_RST ();


if(!preg_match('/^[0-9]{17}$/', $steamid)) return_ERR("Steamid invalid format");

$o = askSteam("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=".APIKEY."&steamids={$steamid}&format=json");
if(!isset($o->response) && !isset($o->response->players)) return_ERR("Missing response from steam");

$o = $o->response->players[0];
$u = new stdClass();
$u->personaName = $o->personaname;
$u->avatar = $o->avatarfull;
$u->profileurl = $o->profileurl;
$u->public = ($o->communityvisibilitystate == 3) ? true : false;

if($u->public) 
{
    $g = askSteam("http://api.steampowered.com/IPlayerService/GetOwnedGames/v0001/?key=".APIKEY."&steamid={$steamid}&format=json");
    if(!isset($g->response) && !isset($o->response->games)) return_ERR("Missing response from steam");

    $u->games = [];
	
	if(empty($g->response->games)) {
		return_ERR("Can't read games from user profile");
	}
	
    foreach($g->response->games as $g)
        $u->games[(int)$g->appid] = 1;
    
    $u->gamesCount = count($u->games);
    $u->games = (object)$u->games;
    
    
//WISHLIST    
    $html = @file_get_contents("http://steamcommunity.com/profiles/{$steamid}/wishlist");
   
    $wish = [];
    $pos = 0;

    while(true) 
    {   
        $id = strextract($html,"id=\"game_", "\"", $pos);
        if(!$id) break;
        $wish[$id] = 1;
    }
    
    $u->wishlistCount = count($wish);
    $u->wishlist = (object)$wish;
    
}
return_OK($u);
