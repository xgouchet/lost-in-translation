<!DOCTYPE html>
<html>
<head>

        <title>Lost in Translation</title>
        <link rel="stylesheet" href="default.css?v=<?php echo rand(); ?>" />
        <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
        <meta charset="UTF-8">

        <!-- TODO metadata, SEO, ... -->

        <!-- Loading the Deezer SDK -->
        <script type="text/javascript" src="http://cdn-files.deezer.com/js/min/dz.js"></script>
</head>


<body>

        <!-- Div needed by Deezer -->
        <div id="dz-root"></div>

        <div id="lit-background">
        </div>

        <!-- The content -->
        <div id="lit-content">

                <div id="lit-search-bar">
                        <form action="javascript:selectLocation();">
                                <input type="text" name="lit-custom-location"  id="lit-custom-location" placeholder="Type your location, eg : San Francisco, CA, US" size="48">

                                <span id="lit-login-deezer" style="display:none">
                                    <a onclick="onLogInDeezer()">Log in with your Deezer Account</a>
                                </span>
                                <span id="lit-user-deezer" style="display:none"></span>
                                <span id="lit-logout-deezer" style="display:none">
                                    <a onclick="onLogOutDeezer()">Log out</a>
                                </span>
                        </form>
                </div>

                <!-- Title -->
                <h1>Keep calm and welcome to... <span id="location"></span></h1>
                
                <table>
                    <tr>
                        <td>
                            <div id="lit-information" style="display:none">
                            <h2 id="lit-information-title"></h2>
                            <div id="lit-information-content"></div> 
                             <p class="information-source"><span id="lit-information-source"></span></p>
                             </div>
                        </td>
                        <td >
                            <!-- the current artist biography -->
                            <div id="lit-biography" style="display:none">
                                <h2 id="lit-biography-name"></h2>
                                <p id="lit-biography-content"></p>
                                <p class="biography-source">Biography from <span id="lit-biography-source"></span></p>
                            </div>
                        </td>
                        <td>
                            <h2 id="lit-save-playlist" style="display:none">
                                <a onclick="onSaveCurrentTrackList()">Save in my playlists</a>
                            </h2>
                            <!-- The deezer Player -->
                            <div id="lit-player" style="display:none">
                            </div>
                            

                        </td>
                    </tr>
                </table>

                </div>

                <div style="clear : both"></div>


                <!-- Background image credit -->
                <div id="lit-background-credits"  class="credit" style="display:none">
                        <p>
                                <span>Background image, courtesy of Panoramio <img src="panoramio.png" width="24" height="24"/></span>
                        </p>
                        <p>
                                <span id="lit-background-title"></span> by <span id="lit-background-author"></span> - <span id="lit-background-date"></span>
                        </p>
                </div>
                <div id="lit-api-credits" class="credit">
                    <p>
                        App powered by <a href="http://www.deezer.com/">Deezer</a>, <a href="http://echonest.com/">Echonest</a>, <a href="https://developers.google.com/maps/documentation/geocoding/">Google Maps</a> and <a href="http://www.mediawiki.org/wiki/API:Main_page">Wikipedia</a>
                    </p>
                    <p>
                        Fork me on <a href="https://github.com/xgouchet/lost-in-translation">Github</a> !
                    </p>
                </div>
        </div>





        <!-- Debug box -->
        <div id="debug"></div>
        <div id="error"></div>


    <script type="text/javascript">

        // Debug method
        function debug(message) {
            console.log(message);
            document.getElementById("debug").innerHTML = message;
        }

        function error(message) {
            console.error(message);
            document.getElementById("error").innerHTML = message;
        }

        /*
        * Clears the content of an HTML node
        */
        function clearContents (parent) {
            while (parent.firstChild) {
            parent.removeChild(parent.firstChild);
            }
        }


        //////////////////////////////////////////////////////////////////////////////////////////

        var currentLocationName = null; 
        var currentLocationCoordinates = null; 

        /*
        * Geo Location (pretty standard stuff)
        */
        function getLocation() {
            if (navigator.geolocation) {
                currentLocationCoordinates = null;
                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        debug("Latitude: " + position.coords.latitude + "<br>Longitude: " + position.coords.longitude);
                        currentLocationCoordinates = position.coords; 
                        convertCoordsToLocation(position.coords.longitude, position.coords.latitude);
                    }, 
                    function(status) {
                        displayMessage("Unable to get current location. Nevermind, you can type one in the search bar above. ");
                    });
                return true;
            } else {
                displayMessage("You're browser can't tell me your current position. Nevermind, you can type one in the search bar above. ");
                return false;
            }
        }


        /*
        * Geocoding  = convert long / lat into a town and country 
        * Feat. Google Geocoding API
        * API key : AIzaSyDGsKmD3P0sddPROHKgUkG0VikKJiBNaV0
        */
        function convertCoordsToLocation(longitude, latitude) {
            var requestUrl = "https://maps.googleapis.com/maps/api/geocode/json"
                                        + "?" + "latlng=" + latitude + ","  + longitude 
                                        + "&" + "result_type=locality" 
                                        + "&" + "key=" + "AIzaSyDGsKmD3P0sddPROHKgUkG0VikKJiBNaV0"; 
            debug(requestUrl);

            var httpRequest = new XMLHttpRequest();
            httpRequest.open("GET", requestUrl, true);
            httpRequest.onload = function (e) {
                if (httpRequest.readyState === 4) {
                    if (httpRequest.status === 200) {
                        var responseJSON = JSON.parse(httpRequest.responseText); 
                        if (responseJSON.results.length == 0) {
                            displayMessage("Seems you're in No Man's land. No one has ever done any music here. Try typing another location in the search bar.");
                        } else if (responseJSON.results.length == 1 ){
                            displayLocation(responseJSON.results[0].formatted_address);
                        } else {
                            var locations = new Array();
                            responseJSON.results.forEach(function(result){
                                    if (locations.indexOf(result.formatted_address) == -1) {
                                      locations.push(result.formatted_address);
                                    }
                                });
                            displayMessage("We can't pinpoint your location. Try typing your location in the search bar.");
                        }
                    } else {
                        error(httpRequest.statusText);
                    }
                }
            }
            httpRequest.send();
        }

        /*
        * Geocoding  = convert place name to long/lat
        * Feat. Google Geocoding API
        * API key : AIzaSyDGsKmD3P0sddPROHKgUkG0VikKJiBNaV0
        */
        function convertLocationToCoords(userLocation) {
            var requestUrl = "https://maps.googleapis.com/maps/api/geocode/json"
                                        + "?" + "address=" + encodeURIComponent(userLocation)
                                        + "&" + "result_type=locality" 
                                        + "&" + "key=" + "AIzaSyDGsKmD3P0sddPROHKgUkG0VikKJiBNaV0"; 
            debug(requestUrl);

            var httpRequest = new XMLHttpRequest();
            httpRequest.open("GET", requestUrl, true);
            httpRequest.onload = function (e) {
                if (httpRequest.readyState === 4) {
                    if (httpRequest.status === 200) {
                        var responseJSON = JSON.parse(httpRequest.responseText); 
                        debug("Found LOCATION");
                        debug(responseJSON);
                        if (responseJSON.results.length == 0) {
                          // ignore
                        } else {
                            currentLocationCoordinates = {
                                latitude : responseJSON.results[0].geometry.location.lat,
                                longitude : responseJSON.results[0].geometry.location.lng
                            };
                            changeBackground();
                        }
                    } else {
                      error(httpRequest.statusText);
                    }
                }
            }
            httpRequest.send();
        }

        /*
        * Displays a message 
        */
        function displayMessage(message) {

        } 

        /*
        * Read the selected location and display it
        */
        function selectLocation() {
            var customLocation = document.getElementById("lit-custom-location").value;
            currentLocationCoordinates = null; 

            if ((customLocation != null) && (customLocation.length > 0)) {
                displayLocation(customLocation); 
            }
        }

        /*
        * Display the selected location
        */
        function displayLocation(userLocation) {
            currentLocationName = userLocation; 
            debug("displayLocation("+userLocation+")");
            document.getElementById("location").innerHTML = userLocation;

            if (currentLocationCoordinates === null) {
                convertLocationToCoords(userLocation);
            } else {
                changeBackground(userLocation);
            }

            searchArtists(userLocation);

            searchLocationInfo(userLocation);
        }

        //////////////////////////////////////////////////////////////////////////////////////////

        /* 
        * Changes the page background 
        * Feat. Panoramio API
        */
        function changeBackground(userLocation) {
            var requestUrl = "http://www.panoramio.com/map/get_panoramas.php"
                                        + "?" + "set=" + "public"
                                        + "&" + "size=" + "original"
                                        + "&"  + "minx=" + (currentLocationCoordinates.longitude - 0.001)
                                        + "&"  + "maxx=" + (currentLocationCoordinates.longitude + 0.001)
                                        + "&"  + "miny=" + (currentLocationCoordinates.latitude - 0.001)
                                        + "&"  + "maxy=" + (currentLocationCoordinates.latitude + 0.001)
                                        + "&" + "from=0" 
                                        + "&" + "to=20" 
                                        + "&" + "callback=" + "onBackgroundAvailable";
            debug(requestUrl);

            // Instead of an XMLHttpRequest, we need to use a hack to work with jsonp
            var scriptElement = document.createElement('script');
            scriptElement.src = requestUrl; 
            document.getElementsByTagName('head')[0].appendChild(scriptElement);
        }

        function onBackgroundAvailable(data){
            if (data.count == 0) {
                // no background, TODO find an alternative ?
                return; 
            }
            debug(data);

            var index = Math.floor(Math.random() * data.photos.length);
            var photo = data.photos[index];

            var parentElement; 

            // photo title
            parentElement = document.getElementById("lit-background-title");
            var photoLinkElement = document.createElement('A');
            photoLinkElement.href = photo.photo_url;
            photoLinkElement.innerHTML = photo.photo_title;
            clearContents(parentElement);
            parentElement.appendChild(photoLinkElement);

            // photo author
            parentElement = document.getElementById("lit-background-author");
            var authorLinkElement = document.createElement('A');
            authorLinkElement.href = photo.owner_url;
            authorLinkElement.innerHTML = photo.owner_name;
            clearContents(parentElement);
            parentElement.appendChild(authorLinkElement);

            // photo upload date
            document.getElementById("lit-background-date").innerHTML = photo.upload_date; 

            // set the page background
            document.getElementById('lit-background').setAttribute("style", 'background : url("' + photo.photo_file_url + '") no-repeat center center fixed; background-size: cover;');


            // make sure the locations div is not hidden
            document.getElementById("lit-background-credits").removeAttribute("style");
        }


        //////////////////////////////////////////////////////////////////////////////////////////

        /*
        * request a search for artists based on the location
        * Feat. Echonest
        * API key : EDCIVASYSJCBWGTMN 
        */
        function searchArtists(userLocation) {
            debug("searchArtists("+userLocation+")");

            // sanytize userLocation (- is interpreted as keyword by echonest... damn )
            userLocation = encodeURIComponent(userLocation).replace("-", " ");

            var requestUrl = "http://developer.echonest.com/api/v4/artist/search" 
                                        + "?" +  "api_key=" + "EDCIVASYSJCBWGTMN"
                                        + "&" + "format=jsonp"
                                        + "&" +  "artist_location=" + userLocation
                                        + "&" + "bucket=" + "id:deezer"
                                        + "&" + "sort=" + "hotttnesss-desc"
                                        + "&" + "bucket=" + "genre"
                                        + "&" + "bucket=" + "artist_location"
                                        + "&" + "bucket=" + "biographies"
                                        + "&" + "bucket=" + "years_active"
                                        + "&" + "callback=" + "onArtistRetrieved"; 
            debug(requestUrl);

            // Instead of an XMLHttpRequest, we need to use a hack to work with jsonp
            var scriptElement = document.createElement('script');
            scriptElement.src = requestUrl; 
            document.getElementsByTagName('head')[0].appendChild(scriptElement);

        }

        /*
        * Callback method for the Echonest request
        */
        function onArtistRetrieved(data) {
            debug("onArtistRetrieved");
            debug(data);
            if (data.response.status.code ===0) {
                extractArtists(data.response.artists); 
            } else {
                error (data.response.status.message);
            }
        }

        /*
        * Global array of artists 
        */
        var currentLocationArtists = new Array();
        var artistsToFetch = new Array();

        /*
        * Extract the artist from Echonest's response
        */
        function extractArtists(echonestArtists){
            debug(echonestArtists);

            var retrievedArtists = new Array();
            var retrievedArtistsIds = new Array();

            echonestArtists.forEach(function (echonestArtist) {
                var artist = {}; 
                artist.deezerId = extractDeezerId(echonestArtist); 
                artist.biography = extractBiographies(echonestArtist);
                artist.genres = extractGenres(echonestArtist);
                artist.name = echonestArtist.name; 

                if (artist.deezerId === null) {
                    debug ("Ignoring " + artist.name + " since we don't have any Deezer ID for him");
                } else {
                    retrievedArtists.push(artist);
                    retrievedArtistsIds.push(artist.deezerId);
                }                                
            });

            // make sure that we can have some music... 
            if (retrievedArtists.length == 0){
                displayMessage("No music was ever written here... You can try these locations instead !");
            } else {
                currentLocationArtists = retrievedArtists;
                artistsToFetch = retrievedArtistsIds; 
                findNextTracks();
            }

        }

        /*
        * Extract the Deezer ID from an artist's echonest object
        */
        function extractDeezerId(echonestArtist) {
            if (echonestArtist.foreign_ids.length == 0){
                return null; 
            }

            var deezerId = null; 
            echonestArtist.foreign_ids.forEach(function (foreign_id) {
                if (foreign_id.catalog == "deezer") {
                    deezerId = foreign_id.foreign_id;
                }
            });

            if (deezerId === null) {
                return null; 
            }

            // id is now in the form "deezer:artist:12345"
            return deezerId.substring(14);
        }

        /*
        * Extract the Genres from an artist's echonest object
        */
        function extractGenres(echonestArtist){
            var genres = new Array();
            echonestArtist.genres.forEach(function (echonestGenre) {
                genres.push(echonestGenre.name);
            });
            return genres; 
        }

        /*
        * Extract the Genres from an artist's echonest object
        */
        function extractBiographies(echonestArtist){
            if (echonestArtist.biographies.length > 0) {
                return echonestArtist.biographies[0];
            } else {
                return null; 
            }
        }


        var currentLocationTracks = new Array();

        /*
        * Clear any previous tracklist
        */
        function clearTrackList() {
            while (currentLocationTracks.length > 0) {
                currentLocationTracks.pop();
            }
        }

        /*
        * Shuffles the tracklist 
        */
        function shuffleTrackList() {
            for (var j, x, i = currentLocationTracks.length; 
                    i; 
                    j = Math.floor(Math.random() * i), 
                    x = currentLocationTracks[--i], 
                    currentLocationTracks[i] = currentLocationTracks[j], 
                    currentLocationTracks[j] = x);
        }

        /*
        * Try to find the next tracks to include in the tracklist
        */
        function findNextTracks(){
            if (artistsToFetch.length == 0){
                shuffleTrackList();
                DZ.player.playTracks(currentLocationTracks);
                document.getElementById('lit-player').removeAttribute("style");

                
                document.getElementById('lit-save-playlist').removeAttribute("style");
                
                return; 
            }

            var artist = artistsToFetch.pop();
            debug("Fetching tracks for artist " + artist);

            // request the top tracks for the artist 
            DZ.api('/artist/' + artist + '/top', 
                function (response) {
                    debug(response);

                    if (response.error) {
                        findNextTracks();
                    } else {
                        response.data.forEach(function (track) {
                                if (track.readable) {
                                    debug ("Adding track " + track.title + " ("  + track.id + ")");
                                    currentLocationTracks.push(track.id);
                                }
                            });
                        findNextTracks();
                    }
                });
        }

        /*
        * Updates the current artist's Bio
        */
        function updateArtistBio(artistId){
            var bioDisplayed = false; 
            currentLocationArtists.forEach(function (artist){
                    if (artist.deezerId == artistId) {
                        if (artist.biography != null) {
                            displayBiography(artist);
                            bioDisplayed = true; 
                        } 
                    }
                });

            if (!bioDisplayed){
                document.getElementById('lit-biography').setAttribute("style", "display:none;");
            }
        }

        /*
        * Displays a biography
        */
        function displayBiography(artist) {
            debug(artist);

            // Artist name
            parentElement = document.getElementById("lit-biography-name");
            var artistLinkElement = document.createElement('A');
            artistLinkElement.href = "http://www.deezer.com/artist/" + artist.deezerId;
            artistLinkElement.innerHTML = artist.name;
            clearContents(parentElement);
            parentElement.appendChild(artistLinkElement);

            // biography
            parentElement = document.getElementById("lit-biography-content");
            parentElement.innerHTML = artist.biography.text;

            // bio source
            parentElement = document.getElementById("lit-biography-source");
            var sourceLinkElement = document.createElement('A');
            sourceLinkElement.href = artist.biography.url;
            sourceLinkElement.innerHTML = artist.biography.site;
            clearContents(parentElement);
            parentElement.appendChild(sourceLinkElement);

            // make sure the locations div is not hidden
            document.getElementById("lit-biography").removeAttribute("style");

        }

        //////////////////////////////////////////////////////////////////////////////////////////

        function searchLocationInfo(userLocation) {
            var requestUrl = "http://en.wikipedia.org/w/api.php"
                                        + "?" + "action=query"
                                        + "&" + "format=json"
                                        + "&" + "list=search"
                                        + "&" + "srsearch=" + encodeURIComponent(userLocation)
                                        + "&" + "srprop=timestamp"
                                        + "&" + "continue="
                                        + "&" + "callback=" + "onSearchInfoRetrieved" ;
            debug(requestUrl);

            // Instead of an XMLHttpRequest, we need to use a hack to work with jsonp
            var scriptElement = document.createElement('script');
            scriptElement.src = requestUrl; 
            document.getElementsByTagName('head')[0].appendChild(scriptElement);

        }

        function onSearchInfoRetrieved(data) {
            debug("onSearchInfoRetrieved");
            debug(data);

            if (data.query.search.length > 0){
                var title = data.query.search[0].title;
                getLocationInfo(title);
            } else {
                debug("Unable to find info about this place");
            }

        }

        function getLocationInfo(title){
            var requestUrl = "http://en.wikipedia.org/w/api.php" 
                                        + "?" +  "format=" + "json"
                                        + "&" + "action=" + "query"
                                        + "&" + "prop=" + "revisions"
                                        + "&" + "rvprop=" + "content"
                                        + "&" + "rvsection=" + "0"
                                        + "&" + "rvparse=" + ""
                                        + "&" + "continue="
                                        + "&" + "titles=" + encodeURIComponent(title)
                                        + "&" + "callback=" + "onLocationInfoRetrieved" ;
                                        
            debug(requestUrl);

            // Instead of an XMLHttpRequest, we need to use a hack to work with jsonp
            var scriptElement = document.createElement('script');
            scriptElement.src = requestUrl; 
            document.getElementsByTagName('head')[0].appendChild(scriptElement);
        }

        function onLocationInfoRetrieved(data) {
            debug("onLocationInfoRetrieved");
            debug(data);

            for (var page in  data.query.pages) break; 
            debug(data.query.pages[page]);

            // title 
            document.getElementById("lit-information-title").innerHTML = data.query.pages[page].title;

            // content
            document.getElementById("lit-information-content").innerHTML = data.query.pages[page].revisions[0]["*"];
            
            // Link to wikipedia
            var wikipediaLinkElement = document.createElement('A');
            wikipediaLinkElement.href = "http://en.wikipedia.org/wiki/" + data.query.pages[page].title;
            wikipediaLinkElement.innerHTML = data.query.pages[page].title + " on Wikipedia";

            var wikipediaSource = document.getElementById("lit-information-source");
            clearContents(wikipediaSource);
            wikipediaSource.appendChild(wikipediaLinkElement);
            wikipediaSource.removeAttribute("style");            

            // make sure it's visible
            document.getElementById("lit-information").removeAttribute("style");
        }

        


        //////////////////////////////////////////////////////////////////////////////////////////

        /*
        * Initialises the Deezer SDK
        */
        function initDeezer() {
            DZ.init({
                    appId: '150511',
                    channelUrl: 'http://' + window.location.hostname + '/LostInTranslation/channel.php',
                    player: { 
                        container: 'lit-player',
                        height : 450,
                        format : 'horizontal',
                            onload : function(){}
                        }
                    });
        }

        /*
        * Log in with deezer 
        */
        function onLogInDeezer(){
            DZ.login(function(response) {
                    if (response.authResponse) {
                        debug('Welcome!  Fetching your information.... ');
                        userId = response.userID; 
                        displayUserDeezer();
                        if (savePlaylistAfterLogin) {
                            savePlaylistAfterLogin = false; 
                            onSaveCurrentTrackList();
                        }
                    } else {
                        debug('User cancelled login or did not fully authorize.');
                    }
                }, {perms: 'basic_access,manage_library'});
        }
 
        function onLogOutDeezer() {
            DZ.logout(function() {
                document.getElementById("lit-user-deezer").setAttribute("style", "display:none");
                 document.getElementById("lit-logout-deezer").setAttribute("style", "display:none");
                 document.getElementById("lit-login-deezer").removeAttribute("style");
                 userId = null;
            });
        }


        function displayUserDeezer(){

            DZ.api('/user/me', function(response) {
                    userId = response.id;
                    debug('Good to see you, ' + response.name + '.');
                    document.getElementById("lit-login-deezer").setAttribute("style", "display:none");

                    var userLinkElement = document.createElement('A');
                    userLinkElement.href = response.link;
                    userLinkElement.innerHTML = response.name;

                    var userElement = document.getElementById("lit-user-deezer");                                
                    clearContents(userElement);
                    userElement.appendChild(userLinkElement);
                    userElement.removeAttribute("style");

                    document.getElementById("lit-logout-deezer").removeAttribute("style");
                });
        }

        var savePlaylistAfterLogin = false; 

        function onSaveCurrentTrackList() {
            // check if we're logged in
            if (userId == null) {
                savePlaylistAfterLogin = true; 
                onLogInDeezer();
            } else {
                // create a playlist
                DZ.api('user/me/playlists', 'POST', {title : currentLocationName}, 
                    function(response){
                        debug ("Playlist created");
                        debug(response);

                        DZ.api('playlist/' + response.id + '/tracks', 'POST', {songs : currentLocationTracks}, 
                            function(response){
                                debug ("Playlist updated");
                                debug(response);
                                
                            });
                    });
            }
        }

        var userId = null; 
        /*
        * Set the ready callback
        */
        DZ.ready(function(sdk_options){

                debug('DZ SDK is ready');
                debug(sdk_options);

                // check the user token
                userToken = sdk_options.token.accessToken;
                if (userToken == null){
                    document.getElementById("lit-login-deezer").removeAttribute("style");
                } else {
                    displayUserDeezer();
                }

                // subscribe to player track events
                DZ.Event.subscribe('current_track', function(track, evt_name){
                    debug(track);
                    updateArtistBio(track.track.artist.id);
                });

                // start the location process ! 
                getLocation();
            });

        ////////////////////////

        // Init deezer sdk
        initDeezer();


    </script>

</body>

</html>