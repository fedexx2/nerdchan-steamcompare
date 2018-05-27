window.onerror = function (message, file, line) {
    alert(file + ':' + line + '\n\n' + message);
};


$().ready(function () {


    var wf, ex;
    if (wf = Cookie.get('wordfilter')) $('#txtWordfilter').val(wf);
    if (ex = Cookie.get('rowexclude')) $('#txtRowExclude').val(ex);
    $('#txtSearch').val('');

    if (window.steamid > 0)
        loadUser();

    var tblOwnedExact = $('#tblOwnedExact').DataTable({paging: false, bFilter: false, bInfo: false});
    var tblNotOwnedExact = $('#tblNotOwnedExact').DataTable({paging: false, bFilter: false, bInfo: false});
    var tblPartial = $('#tblPartial').DataTable({paging: false, bFilter: false, bInfo: false, bSort:false});
    var tblNomatch = $('#tblNomatch').DataTable({paging: false, bFilter: false, bInfo: false, bSort:false});

    var userGames = null;
    var userWishlist = null;

	$('#lblRowExclude').balloon({css: {  fontSize: "1em"}, html: true, contents: "You can write a list of strings separated by a semicolon (;)<br/> Every row that include one on more strings will be excluded.<br/> You can use Regex too with Javascript syntax!",minLifetime: 2500});
	$('#lblSearchFilter').balloon({css: {  fontSize: "1em"}, html: true, contents: "You can write a list of strings separated by a semicolon (;)<br/> Every occurrence of each string will be removed from the row.<br/> You can use Regex too with Javascript syntax!<br/>For Example, removing all prices in 0.00$ format will be: /[0-9]\\.[0-9]{2}\\$/",minLifetime: 2500});

    $('#btnSearch').bind('click', function () {
        var user = $('#txtSteamId').val()
        Ajax("getId.php", {user: user},
                function (data) {
                    window.steamid = data;
                    $('#boxLogin').fadeOut(function () {
                        loadUser();
                    });
                },
                function (e) {
                    $('#lblError').text(e);
                }
        );
    });

    $("#btnReset").click(function () {
        Cookie.del("PHPSESSID");
        $('#btnReset').fadeOut();
        $('#txtSteamId').val('');
        $('#txtSearch').val('');
        $('#boxUser, #boxSearch').fadeOut(function () {
            $('#boxLogin').fadeIn();

            $('#imgUser').attr('src', "");
            $('#lblUsername').text("...");
            $('#lnkUserProfile').attr('href', "");
            $('#lblUserGames').text("...");
            $('#lblUserWishlist').text("...");
            userGames = null;
            userWishlist = null;

            $('#imgLoading').show();
            $('#lblLoading').text('Loading...');

        });
        $('#boxSearch, #boxProgress, #boxResults').fadeOut();
        window.steamid = 0;
    });

    function loadUser()
    {
        Ajax("getUser.php", {},
                function (data) {
                    $('#imgUser').attr('src', data.avatar);
                    $('#lblUsername').text(data.personaName);
                    $('#lnkUserProfile').attr('href', data.profileurl);
					$('#lnkPage').text('http://www.nerdchan.net/steamcompare?steamid='+window.steamid);
					$('#lnkPage').attr('href', 'http://www.nerdchan.net/steamcompare?steamid='+window.steamid);

                    if (data.public == false) {
                        alert("This profile is not public!");
                        $('#btnReset').trigger("click");
                        return;
                    }

                    $('#lblUserGames').text(data.gamesCount);
                    $('#lblUserWishlist').text(data.wishlistCount);
                    userGames = data.games;
                    userWishlist = data.wishlist;

                    $('#imgLoading').fadeOut();
                    $('#lblLoading').text('Ready!');
                    $('#boxSearch').fadeIn();
                    $('#btnReset').fadeIn();
                },
                function (e) {
                    alert(e);
                    $('#btnReset').trigger("click");
                });
        $('#boxUser').fadeIn();
    }

    $('#txtWordfilter').blur(function () {
        var wf = $(this).val();
        Cookie.set('wordfilter', wf, 999);
    });
    $('#txtRowExclude').blur(function () {
        var ex = $(this).val();
        Cookie.set('rowexclude', ex, 999);
    });

    var txtor = '';
    $('#txtSearch').blur(function () {
        if ($('#txtSearch').prop('readonly') == false)
            txtor = $('#txtSearch').val();
    });

    $('#btnPreview').click(function () {

        if ($(this).hasClass('btn-primary'))    //mode edit
        {
			if($('#txtSearch').val().trim()=='') return false;
            var rows = doWordfilter(txtor);
            $('#txtSearch').val(rows).prop('readonly', true);
            $('#txtWordfilter, #txtRowExclude').prop('readonly', true)
            $('#btnPreview').text('Edit').addClass('btn-warning').removeClass('btn-primary');
        } else                                    //mode preview
        {
            $('#txtSearch').val(txtor).prop('readonly', false);
            $('#txtWordfilter, #txtRowExclude').prop('readonly', false);
            $('#btnPreview').text('Preview').removeClass('btn-warning').addClass('btn-primary');
        }
        return false;
    });

    function doWordfilter(data)
    {
        var wf = $('#txtWordfilter').val().split(';').map(v => v.trim()).filter(v => v != '');
        var ex = $('#txtRowExclude').val().split(';').map(v => v.trim()).filter(v => v != '');
        var ro = data.split("\n").map(v => v.trim()).filter(v => v != '');
        
        var rows = [];
        $.each(ro, function(i,r) {
            var toex = ex.some(function(e) {
                return r.toLowerCase().indexOf(e.toLowerCase()) !== -1;
            })
            if(!toex) rows.push(r);
        });
        
        $.each(wf, function (j, w) 
        {
            if (w.startsWith('/') && w.endsWith('/'))
                w = new RegExp(w.slice(1, -1));
            for(var i=0; i<rows.length; i++)            
                rows[i] = rows[i].replace(w, '').trim();
        });
        return rows.join('\n');
    }

    $('#btnClear').click(function () {
        txtor = '';
        $('#txtSearch').val('').prop('readonly', false);
        $('#btnPreview').text('Preview').removeClass('btn-warning').addClass('btn-primary');
        $('#txtWordfilter, #txtRowExclude').prop('readonly', false);
        $('#boxResults').fadeOut();
        return false;
    });

    $('#btnCompare').click(function () {
        if($('#txtSearch').val().trim()=='') return false;
        $('#boxResults').fadeOut();
        
        var rows = doWordfilter(txtor);
        $('#prgBar').width('1%');        
       
        $('#boxProgress').fadeIn(function() {

            Ajax('matchExactFT.php', {rows: rows}, function (d) {
                $('#prgBar').animate({width:'100%'}, {complete:function(){

                    $('#boxProgress').fadeOut(function(){
                        buildTables(d);
                     });  
                }});
            });
        });
        return false;
    });


    function buildTables(data) 
    {
        $('#lblElap').text('Data generated in ' + data.elap.toFixed(3) + " s");
        
        
        tblNotOwnedExact.clear();
        tblOwnedExact.clear();
        tblPartial.clear();
        tblNomatch.clear();

        $.each(data.exact, function (i, m)
        {
            var type = (m.y == "G") ? "Game" : ((m.y == "D") ? "DLC" : "App");
            var wish = userWishlist.hasOwnProperty(m.a) ? " wish " : "";
            var own = userGames.hasOwnProperty(m.a) ? " owned " : "";

            var card = (m.y=="G" && m.c==1) ? "<img src='images/card.png' />" : "";
            var star = (wish) ? "<img src='images/wish.png' />" : "";
            var link = "<a href='http://store.steampowered.com/app/" + m.a + "' target='_blank' >link</a>";

            if (own)
                tblOwnedExact.row.add([m.t, type, card, link]).nodes().to$().addClass(own + wish);
            else
                tblNotOwnedExact.row.add([m.t, type, card, star, link]).nodes().to$().addClass(own + wish);
        });        
        
        $.each(Object.keys(data.partial), function(i,row) {
            var mmm = data.partial[row];
            
            $.each(mmm, function(j,m) {
                var cl = (j==0) ? " first " : ((j==mmm.length-1) ? " last " : "middle");
                
                var type = (m.y == "G") ? "Game" : ((m.y == "D") ? "DLC" : "App");
                var wish = userWishlist.hasOwnProperty(m.a) ? " wish " : "";
                var own = userGames.hasOwnProperty(m.a) ? " owned " : "";                
                
                var card = (m.y=="G" && m.c==1) ? "<img src='images/card.png' />" : "";
                var star = (wish) ? "<img src='images/wish.png' />" : "";
                var link = "<a href='http://store.steampowered.com/app/" + m.a + "' target='_blank' >link</a>";
                
                var cd = (m.d <= -0.75)? 'accu1' :
                    (-0.75 < m.d && m.d < 0) ? 'accu2' :
                    (0 <= m.d && m.d < 1) ? 'accu3' : 'accu4';
                var accu = "<span class='glyphicon glyphicon-asterisk "+cd+"'></span>";
                              
                tblPartial.row.add([row, m.t, accu, type, card, star, link]).nodes().to$().addClass(cl+wish+own);
            });
        });
        
        $.each(data.missing, function(i,m) {
            var link = "<a href='http://store.steampowered.com/search/?term="+m+"' >search</a>";
            tblNomatch.row.add([m, link]);
        });        
        
        
    
        
        $('#tblOwnedExact').toggle(tblOwnedExact.data().count()>0);
        $('#tblNotOwnedExact').toggle(tblNotOwnedExact.data().count()>0);
        $('#tblPartial').toggle(tblPartial.data().count()>0);
        $('#tblNomatch').toggle(tblNomatch.data().count()>0);
        
        
        
        
        tblOwnedExact.draw();
        tblNotOwnedExact.draw();
        tblPartial.draw();
        tblNomatch.draw();
       
         $('#boxResults').fadeTo(1, 0.001, function() {
              
            tblOwnedExact.columns.adjust();
            tblNotOwnedExact.columns.adjust();
            tblPartial.columns.adjust();
            tblNomatch.columns.adjust();
             
             $('#boxResults').fadeTo(1000, 1);
             
         });
             
        
        
    }

    function Ajax(url, data, success, error)
    {
        $.post({
            url: url,
            dataType: 'json',
            data: data
        }).done(function (o, s) {
            if (o.result == 'OK')
                success(o.data);
            else if (o.result == 'RST')
                $('#btnReset').trigger("click");
            else {
                if (error)
                    error(o.data);
                else
                    alert(JSON.stringify(o));
            }
        }).fail(function (o, s) {
            alert("Error in Ajax request.");
        });
    }


});


var Cookie =
        {
            set: function (name, value, days)
            {
                value = encodeURIComponent(value);
                if (days) {
                    var date = new Date();
                    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                    var expires = "; expires=" + date.toGMTString();
                } else
                    var expires = "";
                document.cookie = name + "=" + value + expires + "; path=/";
            },
            get: function (name)
            {
                var nameEQ = name + "=";
                var ca = document.cookie.split(';');
                for (var i = 0; i < ca.length; i++) {
                    var c = ca[i];
                    while (c.charAt(0) == ' ')
                        c = c.substring(1, c.length);
                    if (c.indexOf(nameEQ) == 0)
                        return decodeURIComponent(c.substring(nameEQ.length, c.length));
                }
                return null;
            },
            del: function (name)
            {
                this.set(name, "", -1);
            }
        }
