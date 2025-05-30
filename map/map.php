
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحديد منطقة صالات الأفراح - OpenStreetMap</title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            direction: rtl;
            text-align: center;
        }
        /* التنسيق لشريط التطبيق */
        #app-bar {
    background: #ff1f5a;
    color: white;
    padding: 10px;
    display: flex;
    justify-content: center;  /* التوسيط */
    align-items: center;
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1;
    height: 50px;
}


        #map {
            height: 700px;
            width: 100%;
            margin-top: 60px;
        }
        #controls {
            margin: 10px;
        }
        #select-area {
            padding: 10px 20px;
            font-size: 16px;
            background: red;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }
        .popup-details {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: white;
            border-top: 2px solid #ddd;
            padding: 20px;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            z-index: 9999;
            transition: all 0.3s ease-in-out;
        }
        .popup-details img {
            width: 100%;
        }
        .popup-details button {
            padding: 8px 16px;
            background-color: #f44336;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            margin-top: 10px;
        }
        .popup-details button:hover {
            background-color: #d32f2f;
        }
        #select-area-container {
    position: absolute;
    top: 150px;
    left: 10px; /* <-- هذا السطر هو المفتاح، بدلاً من right: 10px */
    z-index: 1000;
}

        

       #ap{
        margin:50px
       }
       #detailes{
          background: #ff1f5a;
          font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            direction: rtl;
            text-align: center;
       }

      
      

    #ap {
        display: flex;
        align-items: center;
        gap: 10px; /* مسافة بين الـ input والزر */
    }

    #search-bar {
        padding: 8px;
        width: 200px;
    }

    button {
        padding: 8px 12px;
    }

       
    </style>
</head>
<body>
    <!-- شريط التطبيق -->
    <div id="app-bar">
        <div id="ap">
        <button onclick="searchLocation()">search</button>
            <input type="text" id="search-bar" placeholder="Find an area" />
           
        </div>
    </div>

    <!-- حاوية زر تحديد في يمين الخريطة -->
    <div id="select-area-container">
        <button id="select-area">select</button>
    </div>

    <div id="map"></div>

    <!-- شاشة منبثقة لعرض التفاصيل من الأسفل -->
    <div id="property-details" class="popup-details">
        <h4 id="hall-name"></h4>
        <img id="hall-image" src="" alt="صورة القاعة">
        <p id="hall-price"></p>
        <p id="hall-address"></p>
        <a id="google-maps-link" href="#" target="_blank">Direct to Google Maps</a>
        <button onclick="closePopup()">close</button>
    </div>
    



    <script>
        var map = L.map('map').setView([31.963158, 35.930359], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        let drawnCircle = null;
        let selectingArea = false;
        let markers = [];  // لتخزين علامات صالات الأفراح التي تم إضافتها
        let currentFilter = null;

        function showHallsInArea() {
    if (!drawnCircle) return;

    let center = drawnCircle.getLatLng();
    let radius = drawnCircle.getRadius();

    // مسح العلامات القديمة
    markers.forEach(marker => map.removeLayer(marker));
    markers = [];

    // استخدام fetch لجلب البيانات من PHP
    fetch('get_halls.php')
        .then(response => response.json())
        .then(data => {
            // التحقق من أن البيانات غير فارغة
            if (data && !data.error) {
                data.forEach(hallw => {
                    let hallLatLon = L.latLng(hallw.latitude, hallw.longitude); // استخدام lat, lon من البيانات
                    let distance = center.distanceTo(hallLatLon); // قياس المسافة بين القاعة ومركز الدائرة

                    if (distance <= radius) { // التحقق أن القاعة داخل الدائرة فقط
                        let marker = L.marker([hallw.latitude, hallw.longitude]).addTo(map)
                            .bindPopup(`<b>${hallw.name}</b><br>price: ${hallw.price_per_hour}<br>
                              
                            
                               <div id=detailes > <a href="../halls/view.php?id=${hallw.id}">details</a></div>

`);
                        markers.push(marker);
                    }
                });
            } else {
                console.log("لا توجد قاعات");
            }
        })
        .catch(error => {
            console.error("خطأ في جلب البيانات:", error);
        });
}


        // عند الضغط على زر "تحديد"
        document.getElementById('select-area').addEventListener('click', function () {
            if (drawnCircle) {
                map.removeLayer(drawnCircle);
                drawnCircle = null;
                selectingArea = false;
                document.getElementById('select-area').innerText = 'select';
                markers.forEach(marker => map.removeLayer(marker));
                markers = [];
            } else {
                selectingArea = true;
                document.getElementById('select-area').innerText = 'cancle';
                alert("Please click on the map to select the area!");
            }
        });

        // عند الضغط على الخريطة لرسم الدائرة
        map.on('click', function (e) {
            if (!selectingArea) return;

            let radius = 1000; // يمكن تغيير نصف القطر هنا حسب الحاجة
            if (drawnCircle) {
                map.removeLayer(drawnCircle);
            }

            drawnCircle = L.circle([e.latlng.lat, e.latlng.lng], {
                color: 'red', fillColor: '#f03', fillOpacity: 0.3, radius: radius
            }).addTo(map);

            selectingArea = false;
            showHallsInArea();
            document.getElementById('select-area').innerText = 'cancle';
        });

      

        

        // إغلاق النافذة المنبثقة
        function closePopup() {
            document.getElementById("property-details").style.display = "none";
        }

        // دالة البحث عن منطقة
        function searchLocation() {
            var location = document.getElementById('search-bar').value;
            if (location) {
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${location}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0) {
                            var lat = parseFloat(data[0].lat);
                            var lon = parseFloat(data[0].lon);
                            map.setView([lat, lon], 13);
                        } else {
                            alert("Area not found");
                        }
                    });
            }
        }
       
    </script>
</body>
</html>
