window.addEventListener('load', () => {
    getActive();
    //showNext();
    hideButtonIfNoNextArticle();
});

function getActive() {
    const buttons = document
        .getElementById('filters-container')
        .getElementsByClassName('btn');

    const count = buttons.length;
    const location = window.location.href;

    for (let i = 0; i < count; i++) {
        if (buttons[i].href == location) {
            buttons[i].classList.add('red');
            buttons[i].parentNode.classList.add('uk-active');
        }
    }
}

const media = window.matchMedia("(max-width: 650px)");
const mediaBoxs = document
    .getElementById('hack-height')
    .querySelectorAll('a');

function showNext() {
    let a = 0;
    for (let i = 0; i < mediaBoxs.length; i++) {
        if (mediaBoxs[i].className.match(/\bmedia-box-hidden\b/)) {
            mediaBoxs[i].classList.remove("media-box-hidden");
            a++;
        }
        if (a == 9) {
            break;
        }
        hideButtonIfNoNextArticle();
    }
}

function hideButtonIfNoNextArticle() {
    const countBox = document.getElementById('hack-height').getElementsByClassName("media-box-hidden").length;
    if (countBox == 0) {
        document.getElementById('js-showNext').style.display = 'none';
    }
}

const intervalId = window.setInterval(() => {
    UIkit.update(element = document.body, type = 'update');
}, 10);