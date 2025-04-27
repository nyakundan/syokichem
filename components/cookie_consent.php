<div id="cookieConsentPopup" style="display:none;position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#222;color:#fff;padding:1.5rem 2.2rem;border-radius:8px;box-shadow:0 4px 24px rgba(0,0,0,0.13);z-index:9999;font-size:1.1rem;max-width:90vw;">
    This website uses cookies to ensure you get the best experience on our website.
    <button id="cookieConsentBtn" style="margin-left:1.4rem;background:#8BC34A;color:#222;border:none;padding:0.6rem 1.3rem;border-radius:6px;font-weight:600;font-size:1rem;cursor:pointer;">Got it!</button>
</div>
<script>
function setCookie(name, value, days) {
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days*24*60*60*1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "")  + expires + "; path=/";
}
function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}
window.addEventListener('DOMContentLoaded', function() {
    if (!getCookie('cookie_consent')) {
        document.getElementById('cookieConsentPopup').style.display = 'block';
    }
    document.getElementById('cookieConsentBtn').onclick = function() {
        setCookie('cookie_consent', '1', 365);
        document.getElementById('cookieConsentPopup').style.display = 'none';
    };
});
</script>
