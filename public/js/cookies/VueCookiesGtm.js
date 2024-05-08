(function () {
    window.dataLayer = window.dataLayer || [];
    window.DEK = window.DEK || {};
    window.DEK.Cookies = {};
    function gtag() { dataLayer.push(arguments); }

    /**
     * @returns int[]|null length = 3 with values 0 or 1
     */
    DEK.Cookies.getSettings = () => {
        var cookie = document.cookie.split('; ').find(function (cookie) {
            return cookie.split('=')[0] === 'dek_cookies';
        });

        var settings = null;

        if (cookie) {
            try {
                settings = JSON.parse(window.atob(decodeURIComponent(cookie.split('=')[1])));
                if (!Array.isArray(settings) || settings.length !== 3) {
                    throw new Error('Cookies setttings: not an expected Array');
                }

                var rejected = settings.filter(function (value) { return ![0, 1].includes(value) })
                if (rejected.length > 0) {
                    throw new Error('Cookies setttings: unknown value');
                }
            } catch (error) {
                settings = null;
                console.error(error);
            }
        }

        return settings;
    };

    /**
     * @returns { security_storage: 'granted', personalization_storage: 'granted'|'denied', analytics_storage: 'granted'|'denied', ad_storage: 'granted'|'denied'}
     */
    DEK.Cookies.createConsent = (settings) => {
        var tagsChangeable = ['personalization_storage', 'analytics_storage', 'ad_storage'];
        var eventsTypes = ['cookie_preferences', 'cookie_analytics', 'cookie_marketing'];

        var tags = {
            //static
            security_storage: 'granted', //patří mezi nutné, vždy granded
            //changeable
            personalization_storage: 'denied', //preferenční cookie
            analytics_storage: 'denied', //statistické cookie
            ad_storage: 'denied', //marketingové cookie
            ad_user_data: "denied",//stejně jako ad_storage
            ad_personalization: "denied",//stejně jako ad_storage
        };

        var events = [];

        settings.forEach(function (value, index) {
            tags[tagsChangeable[index]] = value ? 'granted' : 'denied';
            value ? events.push(eventsTypes[index]) : null;
        });

        tags.ad_user_data = tags.ad_storage;
        tags.ad_personalization = tags.ad_storage;
        
        return {
            tags: tags,
            events: events,
        };
    };

    DEK.Cookies.defaultConsent = () => {
        gtag('consent', 'default', {
            personalization_storage: 'denied',
            analytics_storage: 'denied',
            ad_storage: 'denied',
            ad_user_data: "denied",
            ad_personalization: "denied",
            wait_for_update: 2000,
        });

        gtag('set', 'ads_data_redaction', true);
    };

    DEK.Cookies.updateConsent = (consent) => {
        consent.events.forEach((cookieType) => {
            dataLayer.push({event: cookieType});
        });

        gtag('consent', 'update', consent.tags);

        var adsData = consent.tags.ad_storage === 'denied' ? true : false;
        gtag('set', 'ads_data_redaction', adsData);
    };

    /* ----------------------------------------------------------- */

    DEK.Cookies.defaultConsent();

    var settings = DEK.Cookies.getSettings();
    if(settings) {
        var consent = DEK.Cookies.createConsent(settings);
        DEK.Cookies.updateConsent(consent);
    }

})();