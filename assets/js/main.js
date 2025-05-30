document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('location-search')) {
        const input = document.getElementById('location-search');
        const autocomplete = new google.maps.places.Autocomplete(input, {
            componentRestrictions: { country: 'JO' },
            fields: ['address_components', 'geometry', 'name']
        });

        autocomplete.addListener('place_changed', function() {
            const place = autocomplete.getPlace();
            if (place.geometry) {
                searchHalls(place);
            }
        });
    }
});

function searchHalls(place = null) {
    let searchQuery = document.getElementById('location-search').value;
    let url = 'halls/search.php?';

    if (place && place.geometry) {
        url += `lat=${place.geometry.location.lat()}&lng=${place.geometry.location.lng()}&`;
    }
    
    url += `q=${encodeURIComponent(searchQuery)}`;
    window.location.href = url;
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('image-preview').src = e.target.result;
            document.getElementById('image-preview').style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            isValid = false;
        } else {
            field.classList.remove('error');
        }
    });

    return isValid;
}

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
    } else {
        input.type = 'password';
    }
}
