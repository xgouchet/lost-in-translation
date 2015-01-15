<!DOCTYPE html>
<html>
<head>

        <title>Lost in Translation</title>
        <link rel="stylesheet" href="default.css" />

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
                        <h3>We can't decide where you are, please help us !</h3>

                        <form>
                                <select id="lit-select-locations"></select><br/><br/>
                                <a onclick="selectLocation()">OK</a>
                        </form>
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

                //  Geo Location
                function getLocation(callback) {
                        if (navigator.geolocation) {
                                navigator.geolocation.getCurrentPosition(callback);
                                return true;
                        } else {
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
                                                        error ("Were are you ? No man's land ?");
                                                } else if (responseJSON.results.length == 1 ){
                                                        displayLocation(responseJSON.results[0].formatted_address);
                                                } else {
                                                        var locations = new Array();
                                                        responseJSON.results.forEach(function(result){
                                                                if (locations.indexOf(result.formatted_address) == -1) {
                                                                        locations.push(result.formatted_address);
                                                                }
                                                        });
                                                        displayLocations(locations);
                                                }
                                                // debug(responseJSON);
                                        } else {
                                                error(httpRequest.statusText);
                                        }
                                }
                        }
                        httpRequest.send();
                }

                /*
                 * Display a list of locations that the user can filter through
                 */
                function displayLocations(locations){
                        debug(locations);

                        var locationsCombo = document.getElementById("lit-select-locations");

                        locations.forEach(function (location) {
                                var option = document.createElement("OPTION");
                                option.setAttribute("value", location);
                                option.innerHTML = location;

                                locationsCombo.appendChild(option);
                        });

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
                                                        + "&" + "bucket=" + "familiarity"
                                                        + "&" + "bucket=" + "hotttnesss"
                                                        + "&" + "bucket=" + "years_active";
                        debug(requestUrl);

                        var httpRequest = new XMLHttpRequest();
                        httpRequest.open("GET", requestUrl, true);
                        httpRequest.onload = function (e) {
                                if (httpRequest.readyState === 4) {
                                        if (httpRequest.status === 200) {
                                                var responseJSON = JSON.parse(httpRequest.responseText); 
                                                
                                                debug(responseJSON);
                                        } else {
                                                error(httpRequest.statusText);
                                        }
                                }
                        }
                        httpRequest.send();
                 }

                ////////////////////////

                getLocation(function (position) {
                        debug("Latitude: " + position.coords.latitude + "<br>Longitude: " + position.coords.longitude);
                        convertCoordsToLocation(position.coords.longitude, position.coords.latitude, null);
                });

        </script>

</body>

</html>