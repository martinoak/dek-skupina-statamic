var DekReCaptcha = function(siteKey) {

	

    this.init = function () {
        var sc = document.createElement('script');
        sc.type = 'text/javascript';
        //sc.async = true;
        sc.src='//www.google.com/recaptcha/api.js?render='+siteKey;
        var s = document.getElementsByTagName('script')[0];
        s.parentNode.insertBefore(sc, s);
    };

    this.createInput = function (action, formId) {
        var hiddenId = formId + '-captcha';
        var form = document.getElementById(formId);
        var input = document.createElement("input");
        input.setAttribute('type', 'hidden');
        input.setAttribute('name', 'captcha');
        input.setAttribute('id', hiddenId);
        form.append(input);

        var execute = function () {
            grecaptcha.execute(siteKey, {action: action}).then(function (token) {
                var recaptchaResponse = document.getElementById(hiddenId);
                recaptchaResponse.value = token;
            });
        };
        grecaptcha.ready(function () {
            execute();
        });
        // token ma zivotnost 2 sec., u formulare chci nastavit pul hodiny. Takze token se bude po dobu 1800s prekreslovat po 100s.
        var executionInterval = setInterval(execute, 100 * 1000);  // po 100s prekreslit (token ma zivotnost 2 min) */
        setTimeout(function () {
            clearInterval(executionInterval);
        }, 1800 * 1000);  // stopnout prekreslovani po pul hodine
    };

};




