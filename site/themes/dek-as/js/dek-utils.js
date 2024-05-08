function scrollToElement(selector) {
    var offset = 80;
    var target = document.querySelector(selector).getBoundingClientRect();
    window.scrollTo(0, target.top - offset);
}