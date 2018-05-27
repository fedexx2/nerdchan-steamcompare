<?php
define('DIRECTACCESS', true);
require_once "apikey.php";
require_once "funcs.php";


$u = Input::post("user");
if(!$u) $u = Input::get("openid_identity");
if(!$u) return_ERR("Empty user value");

if(endsWith($u, "/")) $u = substr($u, 0, -1);

if(startsWith($u, "http://"))             $u=substr($u,7);
if(startsWith($u, "steamcommunity.com/")) $u=substr($u,19);
if(startsWith($u, "openid/id/") )         $u = substr($u,10);
if(startsWith($u, "id/") )                $u = substr($u,3);
if(startsWith($u, "profiles/"))           $u = substr($u,9);

if (preg_match('/^[0-9]{17}$/', $u))
    return_OK($u);

$o = askSteam("http://api.steampowered.com/ISteamUser/ResolveVanityURL/v0001/?key=".APIKEY."&vanityurl={$u}&format=json");

if(!isset($o->response)) return_ERR("Missing response from steam");
if($o->response->success != 1 && isset($o->response->message)) return_ERR($o->response->message);
if($o->response->success != 1) return_ERR("Response code from steam: {$o->response->success}");

session_start();
$_SESSION['steamid'] = $o->response->steamid;

return_OK($o->response->steamid);


