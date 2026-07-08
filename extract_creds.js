// Copy and paste this script into the Console tab of Developer Tools (F12) 
// while logged into the BharatPe Dashboard (https://merchant.bharatpe.in)

(function () {
    console.clear();
    console.log("%c Starting Credential Extraction...", "color: blue; font-size: 16px; font-weight: bold;");

    function getCookie(name) {
        let match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        if (match) return match[2];
        return null;
    }

    // 1. Extract Token (Try multiple keys)
    let token = localStorage.getItem("token") || sessionStorage.getItem("token");
    if (!token) {
        // Search for jwt or auth token in all keys
        for (let i = 0; i < localStorage.length; i++) {
            let key = localStorage.key(i);
            if (key.toLowerCase().includes("token") || key.toLowerCase().includes("auth")) {
                let val = localStorage.getItem(key);
                if (val.length > 20) { // arbitrary length check
                    token = val;
                    console.log("Found candidate token in key:", key);
                    break;
                }
            }
        }
    }

    // 2. Extract Cookie (XSRF-TOKEN is crucial usually, but we need the full string often)
    let cleanCookie = document.cookie;

    // 3. Extract Merchant ID & VPA (Parsing stored user object)
    let merchantId = "NOT FOUND";
    let vpa = "NOT FOUND";

    // Try finding user object
    let userStr = localStorage.getItem("user") || localStorage.getItem("merchant_user");
    if (userStr) {
        try {
            let user = JSON.parse(userStr);
            if (user.merchantId) merchantId = user.merchantId;
            if (user.vpa) vpa = user.vpa;
            else if (user.upiId) vpa = user.user.upiId;
        } catch (e) { }
    }

    // Output
    console.log("%c Copy these details:", "color: green; font-size: 14px;");
    console.log("---------------------------------------------------");
    console.log("Merchant ID : " + merchantId);
    console.log("Token       : " + (token ? token : "NOT FOUND (Look in Network Tab)"));
    console.log("Cookie      : " + cleanCookie);
    console.log("UPI ID (VPA): " + vpa);
    console.log("---------------------------------------------------");
})();
