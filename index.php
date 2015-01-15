<!DOCTYPE html>
<html>
<head>

        <title>Lost in Translation</title>
        <link rel="stylesheet" href="default.css" />
        <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
        <meta charset="UTF-8">

        <!-- TODO metadata, SEO, ... -->

        <!-- Loading the Deezer SDK -->
        <script type="text/javascript" src="http://cdn-files.deezer.com/js/min/dz.js"></script>
</head>


<body>

        <!-- Div needed by Deezer -->
        <div id="dz-root"></div>


        <!-- Title -->
        <h1>Keep calm and listen to...</h1>
        <h2 id="location"></h2>

        <!-- The content -->
        <div id="lit-content">

                <!-- The selected playlist sort form -->
                <div id="lit-section-locations" style="display:none">
                        <h3 id="lit-select-locations-title"></h3>

                        <form>
                                <select id="lit-select-locations"></select><br/><br/>
                                <a onclick="selectLocation()">OK</a>
                        </form>
                </div>

                <!-- The deezer Player -->
                <div id="lit-player">
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
                function clearContents (parent){
                        while (parent.firstChild) {
                                parent.removeChild(parent.firstChild);
                        }
                };


                //////////////////////////////////////////////////////////////////////////////////////////

                /*
                 * Geo Location (pretty standard stuff)
                 */
                function getLocation() {
                        if (navigator.geolocation) {
                                navigator.geolocation.getCurrentPosition(function (position) {
                                        debug("Latitude: " + position.coords.latitude + "<br>Longitude: " + position.coords.longitude);
                                         convertCoordsToLocation(position.coords.longitude, position.coords.latitude, null);
                                 }, 
                                 function(status) {
                                        displayFakeLocations("Unable to get current location. Nevermind, you can try one of these locations...");
                                 });
                                return true;
                        } else {
                                displayFakeLocations("You're browser can't tell me your current position. Nevermind you can try one of these...");
                                return false;
                        }

                }


                /*
                 * Geocoding  = convert long / lat into a town and country 
                 * Feat. Google Geocoding API
                 * API key : AIzaSyDGsKmD3P0sddPROHKgUkG0VikKJiBNaV0
                 */
                function convertCoordsToLocation(longitude, latitude, callback) {
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
                                                       displayFakeLocations("Seems you're in No Man's land. No one has ever done any music here. Try these other locations instead !");
                                                } else if (responseJSON.results.length == 1 ){
                                                        displayLocation(responseJSON.results[0].formatted_address);
                                                } else {
                                                        var locations = new Array();
                                                        responseJSON.results.forEach(function(result){
                                                                if (locations.indexOf(result.formatted_address) == -1) {
                                                                        locations.push(result.formatted_address);
                                                                }
                                                        });
                                                        displayLocations(locations, "We can't pinpoint your location. Can you help us ?");
                                                }
                                        } else {
                                                error(httpRequest.statusText);
                                        }
                                }
                        }
                        httpRequest.send();
                }

                /*
                 * Display a bunch of fake locations when we can't detect the user's location
                 */
                function displayFakeLocations(message){
                        var fakeLocations = [
                                "La Loupe, France",
                                "Chartres, France",
                                "Nogent le Rotrou, France",
                                "Orléans, France",
                                "Groningen, Netherlands",
                                "Southampton, England, GB",
                                "San Diego, CA, US"
                        ];
                        displayLocations(fakeLocations, message);
                }

                /*
                 * Display a list of locations that the user can filter through
                 */
                function displayLocations(locations, message){
                        debug(locations);

                        document.getElementById("lit-select-locations-title").innerHTML = message;

                        var locationsCombo = document.getElementById("lit-select-locations");

                        // clear previous options
                        clearContents(locationsCombo);

                        // add one option for each  location
                        locations.forEach(function (location) {
                                var option = document.createElement("OPTION");
                                option.setAttribute("value", location);
                                option.innerHTML = location;

                                locationsCombo.appendChild(option);
                        });

                        // make sure the locations div is not hidden
                        document.getElementById("lit-section-locations").removeAttribute("style");
                }

                /*
                 * Read the selected location and display it
                 */
                function selectLocation() {
                        var  locationsCombo = document.getElementById("lit-select-locations");
                        var  selectedLocation = locationsCombo.options[locationsCombo.selectedIndex].value;
                        displayLocation(selectedLocation);
                        document.getElementById("lit-section-locations").setAttribute("style", "display:none;");
                }

                /*
                 * Display the selected location
                 */
                function displayLocation(userLocation) {
                        debug("displayLocation("+userLocation+")");
                        document.getElementById("location").innerHTML = userLocation;
                        searchArtists(userLocation);
                }


                //////////////////////////////////////////////////////////////////////////////////////////

                /*
                 * request a search for artists based on the location
                 * Feat. Echonest
                 * API key : EDCIVASYSJCBWGTMN 
                 */
                 function searchArtists(userLocation) {
                        debug("searchArtists("+userLocation+")");

                        var requestUrl = "http://developer.echonest.com/api/v4/artist/search" 
                                                        + "?" +  "api_key=" + "EDCIVASYSJCBWGTMN"
                                                        + "&" + "format=json"
                                                        + "&" +  "artist_location=" + userLocation
                                                        + "&" + "bucket=" + "id:deezer"
                                                        + "&" + "sort=" + "hotttnesss-desc"
                                                        + "&" + "bucket=" + "genre"
                                                        + "&" + "bucket=" + "artist_location"
                                                        + "&" + "bucket=" + "years_active";
                        debug(requestUrl);

                        var httpRequest = new XMLHttpRequest();
                        httpRequest.open("GET", requestUrl, true);
                        httpRequest.onload = function (e) {
                                if (httpRequest.readyState === 4) {
                                        if (httpRequest.status === 200) {
                                                var responseJSON = JSON.parse(httpRequest.responseText); 
                                                if (responseJSON.response.status.code ===0) {
                                                        extractArtists(responseJSON.response.artists); 
                                                } else {
                                                        error (responseJSON.response.status.message);
                                                }
                                        } else {
                                                error(httpRequest.statusText);
                                        }
                                }
                        }
                        httpRequest.send();
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
                        echonestArtists.forEach(function (echonestArtist) {
                                var artist = {}; 
                                artist.deezerId = extractDeezerId(echonestArtist); 
                                artist.genres = extractGenres(echonestArtist);
                                artist.name = echonestArtist.name; 

                                if (artist.deezerId === null) {
                                        debug ("Ignoring " + artist.name + " since we don't have any Deezer ID for him");
                                } else {
                                        currentLocationArtists.push(artist);
                                        artistsToFetch.push(artist.deezerId);
                                }                                
                        });

                        // make sure that we can have some music... 
                        if (currentLocationArtists.length == 0){
                                displayFakeLocations("No music was ever written here... You can try these locations instead !");
                        } else {
                                findNextTracks();
                        }

                }

                /*
                * Extract the Deezer ID from an artist's echonest object
                */
                function extractDeezerId(echonestArtist){
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
                                return; 
                        }

                        var artist = artistsToFetch.pop()
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

                


                //////////////////////////////////////////////////////////////////////////////////////////

                /*
                 * Initialises the Deezer SDK
                 */
                function initDeezer() {
                        DZ.init({
                                appId: '150511',
                                channelUrl: 'http://www.xgouchet.fr/LostInTranslation/channel.php',
                                player: { 
                                        container: 'lit-player',
                                        width : 800,
                                        height : 300,
                                        onload : function(){}
                                }
                        });
                };
                        

                /*
                 * Set the ready callback
                 */
                DZ.ready(function(sdk_options){

                        debug('DZ SDK is ready', sdk_options);

                        // check the user token
                        userToken = sdk_options.token.accessToken;
                        if (userToken == null){
                                // ??? 
                        } else {
                                DZ.api('/user/me', function(response) {
                                        console.log('Good to see you, ' + response.name + '.');
                                        userId = response.id; 

                                        po_load_playlists();
                                });
                        }

                        // start the location process ! 
                        getLocation();
                });

                ////////////////////////

                // Init deezer sdk
                initDeezer();


        </script>

</body>

</html>