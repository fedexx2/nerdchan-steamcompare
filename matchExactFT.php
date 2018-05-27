<?php

define('DIRECTACCESS', true);
require_once 'funcs.php';
require_once 'Str.php';
require_once 'dbConfig.php';

session_start();

$steamid = isset($_SESSION['steamid']) ? $_SESSION['steamid'] : 0;
if (!$steamid)
    return_RST();

$t0 = microtime(true);

$rows = Input::post("rows");
$rows = Explode("\n", $rows);
if (!$rows)
    return_ERR("No rows data");

$clean = [];                                            // array[titleclean] = rows
foreach ($rows as $r) {
    $r = urldecode($r);
    if (!trim($r))
        continue;
    $c = Str::Clean($r, " ");
    $clean[$c] = $r;
}

$allClean = implode("','", array_keys($clean));
$allClean = str_replace(" ", "", $allClean);
$res = Db::queryArray("SELECT appid, title, titlematch, type, hasCards, isFree FROM games WHERE titlematch IN ('{$allClean}');");

$found = [];
foreach ($res as $r)                              // array[titlematch] = games
    $found[$r->titlematch] = $r;



$result = new stdClass();
$result->exact = [];
$result->partial = [];
$result->missing = [];

foreach ($clean as $c => $r)             // $c = titlematch, $r = row
{
    $cm = str_replace(" ", "", $c);

    if (isset($found[$cm])) {
        $g = $found[$cm];
        $game = new stdClass();
        $game->a = $g->appid;
        $game->t = $g->title;
        $game->y = ($g->type == "GAME") ? "G" : (($g->type == "DLC") ? "D" : "A");
        $game->c = $g->hasCards;
        //$game->f = $g->isFree;
        //$game->r = $r;
        $result->exact[] = $game;
        continue;
    }

    $q = "SELECT appid, title, type, titleclean, titlematch, hasCards, isfree, MATCH(titleclean) AGAINST('{$c}') as score FROM games WHERE MATCH(titleclean) AGAINST('{$c}') ORDER BY score DESC LIMIT 20";
    $res = Db::queryArray($q);

    if (count($res) == 0 || $res[0]->score == 0) {
        $result->missing[] = $r;
        continue;
    }

    $res = array_filter($res, function($a) { return $a->score > 0; });
    
    $result->partial[$r] = [];

    foreach ($res as $g) {
        $game = new stdClass();
        $game->a = $g->appid;
        $game->t = $g->title;
        $game->y = ($g->type == "GAME") ? "G" : (($g->type == "DLC") ? "D" : "A");
        $game->c = $g->hasCards;

        
        if (str_contains_both($g->titlematch, $cm)) {
            $game->d = -min(strlen($cm), strlen($g->titlematch));
        }
        else {
            $game->d = levenshtein($g->titleclean, $c, 1, 1, 1);
        }
        $game->d = round($game->d/$g->score,3);

        $result->partial[$r][] = $game;
    }
    usort($result->partial[$r], function($a, $b) { return floatval($a->d) - floatval($b->d); });
    array_splice($result->partial[$r], 4);
}

$result->elap = microtime(true) - $t0;

return_OK($result);
