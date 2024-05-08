showJSElements();
cookiesCheck();
function showJSElements() {
    const jsonly = document.getElementsByClassName("jsonly");
    const length = jsonly.length;
    for (let i = 0; i < length; i++) {
        jsonly[i].style.visibility = 'visible';
    }
}
function cookiesAccept() {
    localStorage.setItem('cookiesUsage', true);
    cookiesCheck();
}

function cookiesClose() {
    $('.cookie-message').hide();
}
function cookiesCheck() {
    const agreed = localStorage.getItem('cookiesUsage');
    if (agreed) {
        cookiesClose();
    }
}

function validateKontaktForm(id){
    let valid = true;
    $('.error-message').css('visibility', 'hidden');
    const validEmail = new RegExp(/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$/);
    const form = $(`#${id}`);
    
    const typedEmail = form.find('input[name="e-mail"]').val();
    const typedName = form.find('input[name="name"]').val();
    const typedPhone = form.find('input[name="telefon"]').val();
    const typedMessage = form.find('input[name="zprava"]').val();
    const agreed = form.find('input[type="checkbox"]').prop('checked');

    if (!validEmail.test(typedEmail)) {
        $('#error-email-k').css('visibility', 'visible');
        valid = false;
    }
    if (typedMessage.trim() === '') {
        $('#error-message-k').css('visibility', 'visible');
        valid = false;
    }
    if (typedPhone.trim() === '') {
        $('#error-telefon-k').css('visibility', 'visible');
        valid = false;
    }
    if (typedName.trim() === '') {
        $('#error-name-k').css('visibility', 'visible');
        valid = false;
    }
    if (typedMessage.trim() === '') {
        $('#error-message-k').css('visibility', 'visible');
        valid = false;
    }
    if (!agreed) {
        $('#error-agree-k').css('visibility', 'visible');
        valid = false;
    }

    return !valid ? false : true;
}

function validateForm(id) {
    let valid = true;
    $('.error').css('visibility', 'hidden');
    const validEmail = new RegExp(/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$/);
    const form = $(`#${id}`);
    const typedEmail = form.find('input[name="e-mail"]').val();
    const typedMessage = form.find('textarea').val();

    const agreed = form.find('input[type="checkbox"]').prop('checked');
    if (!validEmail.test(typedEmail)) {
        $('#error-email').css('visibility', 'visible');
        valid = false;
    }
    if (typedMessage.trim() === '') {
        $('#error-message').css('visibility', 'visible');
        valid = false;
    }
    if (!agreed) {

        $('#error-agree').css('visibility', 'visible');
        valid = false;
    }
    return !valid ? false : true;
}


function validateFormCarrer(id) {
    let valid = true;
    $('.error').css('visibility', 'hidden');
    const validEmail = new RegExp(/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$/);
    const form = $(`#${id}`);
    const typedEmail = form.find('input[name="e-mail"]').val();
    const typedName = form.find('input[name="name"]').val();
    const typedPhone = form.find('input[name="phone"]').val();
    const typedSpecialization = form.find('select[name="specialization"]').val();
    const agreed = form.find('input[type="checkbox"]').prop('checked');
    
    const priloha = form.find('#files input').val();
    
    if (!validEmail.test(typedEmail)) {
        valid = false;
    }
    if (typedPhone.trim() === '') {
        valid = false;
    }
    if (typedName.trim() === '') {
        valid = false;
    }
    if(typedSpecialization === 'disabled'){
        valid = false;
    }
    
    if(!priloha || priloha.trim() === ''){
        valid = false;
    }
    
    if (!agreed) {
        valid = false;
    }
    return valid;
}

function validateInternshipForm(id) {
    let valid = true;
    $('.error').css('visibility', 'hidden');
    const validEmail = new RegExp(/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$/);
    const form = $(`#${id}`);
    const typedName = form.find('input[name="name"]').val();
    const typedEmail = form.find('input[name="e-mail"]').val();
    const typedPhone = form.find('input[name="phone"]').val();
    const agreed = form.find('input[type="checkbox"]').prop('checked');

    if (!validEmail.test(typedEmail)) {
        valid = false;
    }
    if (typedPhone.trim() === '') {
        valid = false;
    }
    if (typedName.trim() === '') {
        valid = false;
    }
    if (!agreed) {
        valid = false;
    }

    return valid;
}

$(document).on('click', '#js-menu-icon', function () {
    $(this).toggleClass('change');
    $('#js-uk-navbar-nav').toggleClass('show');
});

$(document).ready(() => {
    // Video
    $('*[data-video-ratio]').each(function () {
        const ratio = $(this).data('video-ratio');
        const width = $(this).width();
        const height = width / ratio;
        $(this).css('height', height);
    });
});
