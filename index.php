<?php
define('DIRECTACCESS', true);

define('SITEPATH', "www.nerdchan.net/steamcompare/"); //no http, slash finale

require_once "funcs.php";
require_once "logger.php";

session_start();

$id = Input::get("steamid");
if ($id) {
    $_SESSION['steamid'] = $id;
}
else {
	$id = Input::get("openid_identity");
	if ($id) {
		$id = str_replace("http://steamcommunity.com/openid/id/", "", $id);
		$_SESSION['steamid'] = $id;
		header("location: http://" . SITEPATH);
		die();
	}
}

$id = isset($_SESSION['steamid']) ? $_SESSION['steamid'] : 0;

?><!DOCTYPE html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Steam Compare (beta)</title>

    <link href="css/style.css" rel="stylesheet" type="text/css" />
    <link href="css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="css/datatables.min.css" rel="stylesheet" type="text/css" />
    <link href="css/jquery.dataTables.min.css" rel="stylesheet" type="text/css" />
    <link href="css/responsive.bootstrap.css" rel="stylesheet" type="text/css" />
	<link rel="icon" href="favicon.ico" type="image/x-icon"/>

    <script type="text/javascript" src="js/jquery-2.2.4.min.js" ></script>
	<script type="text/javascript" src="js/jquery.balloon.min.js" ></script>
    <script type="text/javascript" src="js/bootstrap.min.js" ></script>
    <script type="text/javascript" src="js/jquery.dataTables.min.js" ></script>
    <script type="text/javascript" src="js/dataTables.bootstrap.min.js" ></script>
    <script type="text/javascript" src="js/dataTables.responsive.min" ></script>
    <script type="text/javascript" src="js/script.js" ></script>

</head>
<body>
    <script type="text/javascript">
        window.steamid = "<?= $id ?>";
    </script>


    <div class="container">
        <div class="page-header">
            <a href="http://steamcommunity.com/id/fedexx2" id="btnAbout" class="btn btn-primary">Author</a>
            <a href="#" id="btnReset" class="btn btn-primary hidd">Reset\Logout</a>
            <h1>Steam Compare (beta)</h1>
        </div>

        <div class="row <?= $id ? 'hidd' : '' ?>" id="boxLogin">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">Login</h3>
                </div>
                <div class="panel-body">
                    <div class="row" >
                        <div class="col-md-6 text-center">
                            <h5>With Steam name, Steam ID or Steam URL</h5>
                            <div class="input-group" id="grpSteamId">
                                <input class="form-control" id="txtSteamId" value="" type="text">
                                <span class="input-group-btn">
                                    <a href="#" id="btnSearch" class="btn btn-primary">Search</a>
                                </span>                                
                            </div>
                            <p id="lblError"></p>
                        </div>
                        <div class="col-md-6 text-center">
                            <h5>With Steam OpenID</h5>
                            <a href="https://steamcommunity.com/openid/login?openid.ns=http://specs.openid.net/auth/2.0&amp;openid.mode=checkid_setup&amp;openid.return_to=http://<?= SITEPATH ?>index.php&amp;openid.realm=http://<?= SITEPATH ?>&amp;openid.ns.sreg=http://openid.net/extensions/sreg/1.1&amp;openid.claimed_id=http://specs.openid.net/auth/2.0/identifier_select&amp;openid.identity=http://specs.openid.net/auth/2.0/identifier_select">
                                <img src ="http://cdn.steamcommunity.com/public/images/signinthroughsteam/sits_large_border.png" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row hidd" id="boxUser">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">Steam User</h3>
                </div>
                <div class="panel-body">
                    <div class="row" >
                        <div class="col-md-6">
                            <div class="media">
                                <div class="media-left media-middle">
                                    <a id="lnkUserProfile" href="#">
                                        <img id="imgUser" class="media-object img-rounded" src="...">
                                    </a>
                                </div>
                                <div class="media-body">
                                    <a href="#" id="lnkUserProfile"><img src="images/steam.png"/> <span class="lead" id="lblUsername">...</span></a>
                                    <p>Owned games: <span id="lblUserGames">...</span></p>
                                    <p>Wishlist: <span id="lblUserWishlist">...</span></p>
									<p>Link: <a id="lnkPage" href="http://www.nerdchan.net/steamcompare?steamid=<?=$id?>">http://www.nerdchan.net/steamcompare?steamid=<?=$id?></a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 text-center">
                            <span class="lead" id="lblLoading">Loading...</span>
                            <img id="imgLoading" src="images/loading.gif" class="img-responsive" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row hidd" id="boxSearch">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">Insert games list</h3>
                </div>
                <div class="panel-body">
                    <textarea class="form-control" rows="10" id="txtSearch"></textarea>

                    <div class="row">
                        <div class="col-md-6">
                            <label id="lblRowExclude" for="txtRowExclude" >Exclude row if contains (?): </label>
                              <input class="form-control" id="txtRowExclude" value="" type="text">
                        </div>
                        <div class="col-md-6">
                            <label id="lblSearchFilter" for="txtSearchFilter" >Wordfilter (?): </label>

                            <div class="input-group" id="grpWordfilter">
                                <input class="form-control" id="txtWordfilter" value="" type="text">
                                <span class="input-group-btn">
                                    <a href="#" id="btnPreview" class="btn btn-primary">Preview</a>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <a href="#" id="btnCompare" class="btn btn-primary btn-block">Compare</a>
                        </div>
                        <div class="col-md-4">
                            <a href="#" id="btnClear" class="btn btn-primary btn-block">Clear</a>
                        </div>
                    </div>                    
                </div>
            </div>
        </div>

        <div class="row hidd" id="boxProgress">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">Searching...</h3>
                </div>
                <div class="panel-body">
                    <div class="progress progress-striped active" >
                        <div class="progress-bar" id="prgBar" style="width: 0%"></div>
                    </div>
                    <span id="txtSearching">Searching...</span>
                </div>
            </div>
        </div>

        <div class="row hidd" id="boxResults">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">Results</h3>
                </div>
                <div class="panel-body">

                    <table id="tblOwnedExact" class="display" width="100%" cellspacing="0">
                        <thead>
                            <tr><th colspan="4">Owned Games (Exact Match)</th></tr>
                            <tr>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Cards</th>
                                <th>Link</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>

                    <table id="tblNotOwnedExact" class="display" width="100%" cellspacing="0">
                        <thead>
                            <tr><th colspan="5">Not Owned Games (Exact Match)</th></tr>
                            <tr>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Cards</th>
                                <th>Wishlist</th>
                                <th>Link</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>

                    <table id="tblPartial" class="display" width="100%" cellspacing="0">
                        <thead>
                            <tr><th colspan="7">Partial Matches</th></tr>
                            <tr>
                                <th>String</th>
                                <th>Match</th>
                                <th>Accuracy</th>
                                <th>Type</th>
                                <th>Cards</th>
                                <th>Wishlist</th>
                                <th>Link</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>

                    <table id="tblNomatch" class="display" width="100%" cellspacing="0">
                        <thead>
                            <tr><th colspan="2">No Matches</th></tr>
                            <tr>
                                <th>String</th>
                                <th>Search</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>

                    <p id="lblElap"></p>

                </div>
            </div>
        </div>

    </div>

</body>
</html>