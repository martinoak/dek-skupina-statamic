const files = [];
let totalSize = 0;
let maxTotalSize = 10;
$('input[type="file"]').change(function () {
    const file = $(this).prop('files')[0];
    if (checksize(file)) {
        const index = files.push(file) - 1;
        totalSize += file.size;
        const displaySize = Math.round((file.size / 1024) * 100) / 100;
        const newInput = $(this).parent('.fakeinput').clone();
        
        newInput.find('input[type="file"]').attr('id', index).attr('name', 'files[]').parent('.fakeinput').find('span').text(`${file.name} (${displaySize}kB)`);
        newInput.append('<span class="delete cross" onclick="remove(this)"></span>').prependTo('#files');
        $(this).val('');

        const element = document.getElementById("js-fakeinput");
        element.classList.add("fakeinputHide");
    }
});
function remove(e) {
    const index = $(e).parent('.fakeinput').children('input[type="file"]').attr('id');
    totalSize -= files[index].size;
    delete files[index];
    $(e).parent('.fakeinput').remove();
    if (totalSize == 0) {
        const element = document.getElementById("js-fakeinput");
        element.classList.remove("fakeinputHide");
    }
}

function checksize(file) {
    
    if (totalSize + file.size > maxTotalSize * 1024 * 1024) {
        alert('Maximální velikost příloh je '+maxTotalSize+'MB.');
        return false;
    }
    return true;
}


$('#candidate button').on('click', (event) => {
    let filled = true;
    const agree = $('input[name="agree"]');
    const email = $('input[name="e-mail"]');
    const validEmail = new RegExp(/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$/);
    
    $('#candidate #upload').attr('name', '');
    $('input.required').each(function () {
        if (!$(this).val() || $(this).val() == '') {
            filled = false;
            $(this).parent().find('span.error-message').css('visibility', 'visible');
        } else {
            $(this).parent().find('span.error-message').css('visibility', 'hidden');
        }
    });
    const form = $('#candidate');
    const priloha = form.find('#files input').val();

    if(!priloha || priloha.trim() === ''){
        filled = false;
        form.find('#js-fakeinput + span.error-message').css('visibility', 'visible');
    } else {
        form.find('#js-fakeinput + span.error-message').css('visibility', 'hidden');
    }
    if (!validEmail.test(email.val())) {
        email.parent().find('span.error-message').css('visibility', 'visible');
        filled = false
    } else {
        email.parent().find('span.error-message').css('visibility', 'hidden');
    }
    if (!agree.prop('checked')) {
        agree.parent().parent().find('span.error-message').css('visibility', 'visible');
        filled = false
    } else {
        agree.parent().parent().find('span.error-message').css('visibility', 'hidden');
    }
    if (filled) {
        $('.shadow,.confirm').show();
    }


    $('.shadow,.confirm').show();
});

$('#intern button').on('click', (event) => {
    let filled = true;
    const agree = $('input[name="agree"]');
    const email = $('input[name="e-mail"]');
    const validEmail = new RegExp(/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$/);

    $('#intern #upload').attr('name', '');
    $('input.required').each(function () {
        if (!$(this).val() || $(this).val() == '') {
            filled = false;
            $(this).parent().find('span.error-message').css('visibility', 'visible');
        } else {
            $(this).parent().find('span.error-message').css('visibility', 'hidden');
        }
    });

    if (!validEmail.test(email.val())) {
        email.parent().find('span.error-message').css('visibility', 'visible');
        filled = false
    } else {
        email.parent().find('span.error-message').css('visibility', 'hidden');
    }
    if (!agree.prop('checked')) {
        agree.parent().parent().find('span.error-message').css('visibility', 'visible');
        filled = false
    } else {
        agree.parent().parent().find('span.error-message').css('visibility', 'hidden');
    }
});

const windowloaded = () => {
    select = document.getElementsByTagName('select');
    
    if (select[0] != undefined) {
        select[0].addEventListener('change', function () {
            if (this.value != "disabled") {
                this.style.color = '#464646';
            }
        });
    }
}

window.onload = windowloaded;